<?php
/**
 * DokuWiki plugin Typography; StyleDiv syntax component
 *  - modified version of divwrap component of WRAP plugin
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Anika Henke <anika@selfthinker.org>
 * @modified by Satoshi Sahara <sahara.satoshi@gmail.com>
 */

if(!defined('DOKU_INC')) die();

class syntax_plugin_typography_stylediv1 extends DokuWiki_Syntax_Plugin {

    protected $mode;
    protected $pattern = array(
        1 => '<STYLE\b.*?>(?=.*?</STYLE>)', // entry
        4 => '</STYLE>',                    // exit
        5 => '<STYLE\b[^>\r\n]*?/>',        // special
    );

    protected $wrap = null; // helper 
    protected $css  = null; // helper typography

    function __construct() {
        $this->mode = substr(get_class($this), 7);

        $this->wrap = $this->loadHelper('typography_wrap');
        $this->css  = $this->loadHelper('typography_parser');
    }

    function getType(){ return 'formatting';}
    function getPType(){ return 'stack';}
    function getSort(){ return 195; }
    function getAllowedTypes() {
        return array('container', 'formatting', 'substition', 'protected', 'disabled', 'paragraphs');
    }

    // override default accepts() method to allow nesting
    function accepts($mode) {
        if ($mode == $this->mode) return true;
        return parent::accepts($mode);
    }

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern($this->pattern[5], $mode, $this->mode);
        $this->Lexer->addEntryPattern($this->pattern[1], $mode, $this->mode);
    }

    function postConnect() {
        $this->Lexer->addExitPattern($this->pattern[4], $this->mode);
        $this->Lexer->addPattern('[ \t]*={2,}[^\n]+={2,}[ \t]*(?=\n)', $this->mode);
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, Doku_Handler $handler){
        global $conf;
        switch ($state) {
            case DOKU_LEXER_ENTER:
            case DOKU_LEXER_SPECIAL:
                $param = strtolower(rtrim(substr($match, strlen($this->pattern[4])-2, -1)," \t\n/"));

                $attributes = $this->wrap->getAttributes($param);

                // width on spans normally doesn't make much sense, 
                // but in the case of floating elements it could be used
                if ($attributes['width']) {
                    if (strpos($attributes['width'],'%') !== false) {
                        $style = 'width: '.hsc($attributes['width']).';';
                    } else {
                        // anything but % should be 100% when the screen gets smaller
                        $style = 'width: '.hsc($attributes['width']).'; max-width: 100%;';
                    }
                    $attributes['style'] .= ($attributes['style'] ? ' ' : '').$style;
                }
                unset($attributes['width']);

                // parse css declarations, recognize abbreviated property name
                if (isset($attributes['style'])) {
                    $tag_data = $this->css->parse_inlineCSS($attributes['style'], false);
                    $attributes['declarations'] = $tag_data['declarations'];
                    unset($attributes['style']);
                }
                if (isset($tag_data['classes'])) {
                    $attributes['classes'] = array_merge($attributes['classes'],$tag_data['classes']);
                }

                // only write lang if it's a language in lang2dir.conf
                if (isset($attributes['dir'])) {
                    $lang = $attributes['lang'];
                    $attributes['xml:lang'] = $lang;
                } else {
                    unset($attributes['lang']);
                }

                return array($state, $attributes);

            case DOKU_LEXER_UNMATCHED:
                $handler->_addCall('cdata', array($match), $pos);
                return false;

            case DOKU_LEXER_MATCHED:
                // we have a == header ==, use the core header() renderer
                // (copied from core header() in inc/parser/handler.php)
                $title = trim($match);
                $level = 7 - strspn($title,'=');
                if($level < 1) $level = 1;
                $title = trim($title,'=');
                $title = trim($title);

                $handler->_addCall('header',array($title,$level,$pos), $pos);
                // close the section edit the header could open
                if ($title && $level <= $conf['maxseclevel']) {
                    $handler->addPluginCall('wrap_closesection', array(), DOKU_LEXER_SPECIAL, $pos, '');
                }
                break;

            case DOKU_LEXER_EXIT:
                return array($state, '');
        }
        return array();
    }

    /**
     * Create output
     */
    function render($format, Doku_Renderer $renderer, $data) {
        if (empty($data)) return false;
        list($state, $attributes) = $data;

        if ($format == 'xhtml'){
            switch ($state) {
                case DOKU_LEXER_ENTER:
                    $sectionEditStartData = ['target' => 'plugin_wrap_start'];
                    $sectionEditEndData   = ['target' => 'plugin_wrap_end'];
                    if (!defined('SEC_EDIT_PATTERN')) {
                        // backwards-compatibility for Frusterick Manners (2017-02-19)
                        $sectionEditStartData = 'plugin_wrap_start';
                        $sectionEditEndData   = 'plugin_wrap_end';
                    }

                    // add a section edit right at the beginning of the wrap output
                    $renderer->startSectionEdit(0, $sectionEditStartData);
                    $renderer->finishSectionEdit();
                    // add a section edit for the end of the wrap output. This prevents the renderer
                    // from closing the last section edit so the next section button after the wrap syntax will
                    // include the whole wrap syntax
                    $renderer->startSectionEdit(0, $sectionEditEndData);

                case DOKU_LEXER_SPECIAL:
                    $attr = $this->css->build_attributes($attributes);
                    $renderer->doc .= '<div'.$attr.'>';
                    if ($state == DOKU_LEXER_SPECIAL) $renderer->doc .= '</div>';
                    break;

                case DOKU_LEXER_EXIT:
                    $renderer->doc .= '</div>';
                    $renderer->finishSectionEdit();
                    break;
            }
            return true;
        } elseif ($format == 'odt') {
          /*
            $odt = $this->loadHelper('wrap_odt');
            return $odt->render($renderer, 'div', $data);
          */
        }
        return false;
    }
}
