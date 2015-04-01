<?php
/**
 * DokuWiki plugin Typography; Syntax typography base component
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Paweł Piekarski <qentinson@gmail.com>
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

class syntax_plugin_typography_base extends DokuWiki_Syntax_Plugin {

    protected $entry_pattern = '<typo\b(?: .+?)?>(?=.+?</typo>)';
    protected $exit_pattern  = '</typo>';

    protected $pluginMode, $props, $cond;
    
    // ODT (Open Document format) export
    protected $closing_stack = NULL;                     // used in odt_render()

    public function __construct() {
        $this->pluginMode = substr(get_class($this), 7); // drop 'syntax_' from class name

        $this->props = array(
            'ff' => 'font-family:',
            'fc' => 'color:',
            'bg' => 'background-color:',
            'fs' => 'font-size:',
            'fw' => 'font-weight:',
            'fv' => 'font-variant:',
            'lh' => 'line-height:',
            'ls' => 'letter-spacing:',
            'ws' => 'word-spacing:',
            'va' => 'vertical-align:',
            'sp' => 'white-space:',
        );

        $this->conds = array(
            'ff' => '/^((\'[^,]+?\'|[^ ,]+?) *,? *)+$/',
            'fc' => '/(^\#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$)|'
                   .'(^rgb\((\d{1,3}%?,){2}\d{1,3}%?\)$)|'
                   .'(^[a-zA-Z]+$)/',
            'bg' => '/(^\#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$)|'
                   .'(^rgb\((\d{1,3}%?,){2}\d{1,3}%?\)$)|'
                   .'(^rgba\((\d{1,3}%?,){3}[\d.]+\)$)|'
                   .'(^[a-zA-Z]+$)/',
            'fs' => '/(^\d+(?:\.\d+)?(px|em|ex|pt|%))$|'
                   .'^(xx-small|x-small|small|medium|large|x-large|xx-large|smaller|larger)$/',
            'fw' => '/^(normal|bold|bolder|lighter|\d00)$/',
            'fv' => '/^(normal|small-?caps)$/',
            'lh' => '/^\d+(?:\.\d+)?(px|em|ex|pt|%)?$/',
            'ls' => '/^-?\d+(?:\.\d+)?(px|em|ex|pt|%)$/',
            'ws' => '/^-?\d+(?:\.\d+)?(px|em|ex|pt|%)$/',
            'va' => '/^-?\d+(?:\.\d+)?(px|em|ex|pt|%)$|'
                   .'^(baseline|sub|super|top|text-top|middle|bottom|text-bottom|inherit)$/',
            'sp' => '/^(normal|nowrap|pre|pre-line|pre-wrap)$/',
        );
    }

    public function getType() { return 'formatting'; }
    public function getSort() { return 67; } // = Doku_Parser_Mode_formatting:strong -3
    public function getAllowedTypes() { return array('formatting', 'substition', 'disabled'); }
    // override default accepts() method to allow nesting - ie, to get the plugin accepts its own entry syntax
    public function accepts($mode) {
        if ($mode == substr(get_class($this), 7)) return true;
        return parent::accepts($mode);
    }

    // Connect pattern to lexer
    public function connectTo($mode) {
        $this->Lexer->addEntryPattern($this->entry_pattern, $mode, $this->pluginMode);
    }
    public function postConnect() {
        $this->Lexer->addExitPattern($this->exit_pattern, $this->pluginMode);
    }

    /*
     * Handle the match
     */
    public function handle($match, $state, $pos, Doku_Handler $handler) {
        switch($state) {
            case DOKU_LEXER_ENTER:
                $markup = substr($this->exit_pattern, 2, -1);
                $params = trim(strstr(substr($match, 1, -1), ' '));

                if ($params == false) return array($state, '');
                $tokens = explode(';', $params);
                if ((count($tokens) == 1) && array_key_exists($markup, $this->props)) {
                    // for inherited syntax class usage: <fs small>...</fs>
                    $tokens[0] = $markup.':'.$tokens[0];
                }
                //msg('markup:'.$markup.' tokens='.var_export($tokens, true), 0);

                $attrs = array();
                foreach($tokens as $token) {
                    if (empty($token)) continue;
                    list($type, $val) = explode(':', trim($token));
                    if (!array_key_exists($type, $this->props)) continue;
                    if (preg_match($this->conds[$type], $val)) {
                        if ($val == 'smallcaps') { $val = 'small-caps'; }
                        $attrs = array_merge($attrs, array($type => $val));
                    }
                }
                return array($state, $attrs);
                break;
            case DOKU_LEXER_UNMATCHED: return array($state, $match);
            case DOKU_LEXER_EXIT:      return array($state, '');
        }
        return array();
    }

    /*
     * Create output
     */
    public function render($format, Doku_Renderer $renderer, $indata) {

        if ($format == 'xhtml') {
            list($state, $data) = $indata;
            switch ($state) {
                case DOKU_LEXER_ENTER:
                    $css = '';
                    foreach ($data as $type => $val) {
                        $css .= $this->props[$type].$val.'; ';
                    }
                    $renderer->doc .= '<span style="'.$css.'">';
                    break;
                case DOKU_LEXER_UNMATCHED:
                    $renderer->doc .= $renderer->_xmlEntities($data);
                    break;
                case DOKU_LEXER_EXIT:
                    $renderer->doc .= '</span>';
                    break;
            }
            return true;
        } else if ($format == 'odt') {
            /*
             * ODT export; call separate function odt_render($renderer, $indata);
             */
            $success = $this->odt_render($renderer, $indata);
            return $success;
        }
        return false;
    }

    /**
     * odt_renderer
     * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
     * @author     Lars (LarsDW223)
     */
    protected function odt_render($renderer, $indata) {

        if (is_null($this->closing_stack)) {
            $this->closing_stack = new SplStack(); //require PHP 5 >= 5.3.0
        }

        list($state, $data) = $indata;
        switch ($state) {
            case DOKU_LEXER_ENTER:
                $css = '';
                foreach ($data as $type => $val) {
                    $css .= $this->props[$type].$val.'; ';
                }
                if ( empty($data ['lh']) === true ) {
                    $renderer->_odtSpanOpenUseCSSStyle ($css);
                    $this->closing_stack->push('span');
                } else {
                    $renderer->p_close ();
                    $renderer->_odtParagraphOpenUseCSSStyle ($css);
                    $this->closing_stack->push('p');
                }
                break;
            case DOKU_LEXER_UNMATCHED:
                $renderer->doc .= $renderer->_xmlEntities($data);
                break;
            case DOKU_LEXER_EXIT:
                try {
                    $content = $this->closing_stack->pop();
                    if ($content == 'p') {
                        // For closing paragraphs use the renderer's function otherwise the internal
                        // counter in the ODT renderer is corrupted and so would be the ODT file.
                        $renderer->p_close ();
                        $renderer->p_open ();
                    } else {
                        // Close the span.
                        $renderer->_odtSpanClose ();
                    }
                } catch (Exception $e) {
                    // May be included for debugging purposes.
                    //$renderer->doc .= $e->getMessage();
                }
                break;
        }
        return true;
    }
}
