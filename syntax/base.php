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

    protected $entry_pattern = '<typo(?: .+?)?>(?=.+?</typo>)';
    protected $exit_pattern  = '</typo>';

    protected $pluginMode, $props, $cond;
    
    // ODT (Open Document format) support
    protected $closing_stack = NULL;                     // used in odt_render()
    protected $odt_style_prefix = 'plugin_typography_';  // used in _get_odt_params()
    protected $odt_style_count  = 0;                     // used in _get_odt_params()

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

        if (!plugin_isdisabled('odt')) {
            $this->closing_stack = new SplStack(); //require PHP 5 >= 5.3.0
        }
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
             * ODT support; call separate function odt_render($renderer, $indata);
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
        list($state, $data) = $indata;
        switch ($state) {
            case DOKU_LEXER_ENTER:
                list($odt_style_name, $odt_style, $odt_use_span, $odt_sub_on, $odt_super_on) = $this->_get_odt_params ($data);
                $tags = 0;
                if ($odt_style_name != NULL) {
                    $renderer->autostyles[$odt_style_name] = $odt_style;
                    if ($odt_use_span == false) {
                        $renderer->p_close ();
                        $renderer->p_open ($odt_style_name);
                        $this->closing_stack->push('</text:p>');
                        $tags++;
                    } else {
                        $renderer->doc .= '<text:span text:style-name="'.$odt_style_name.'">';
                        $this->closing_stack->push('</text:span>');
                        $tags++;
                    }
                }

                if ($odt_sub_on == true) {
                    $renderer->subscript_open();
                    $this->closing_stack->push('</text:span>');
                    $tags++;
                }
                if ($odt_super_on == true) {
                    $renderer->superscript_open();
                    $this->closing_stack->push('</text:span>');
                    $tags++;
                }
                $this->closing_stack->push($tags);
                break;
            case DOKU_LEXER_UNMATCHED:
                $renderer->doc .= $renderer->_xmlEntities($data);
                break;
            case DOKU_LEXER_EXIT:
                try {
                    $tags = $this->closing_stack->pop();
                    for ($i = 0; $i < $tags; $i++) {
                        $content = $this->closing_stack->pop();
                        if ($content == '</text:p>') {
                            // For closing paragraphs use the renderer's function otherwise the internal
                            // counter in the ODT renderer is corrupted and so would be the ODT file.
                            $renderer->p_close ();
                            $renderer->p_open ();
                        } else {
                            $renderer->doc .= $content;
                        }
                    }
                } catch (Exception $e) {
                    // May be included for debugging purposes.
                    //$renderer->doc .= $e->getMessage();
                }
                break;
        }
        return true;
    }

    /**
     * _get_odt_params
     * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
     * @author     Lars (LarsDW223)
     */
    protected function _get_odt_params($attrs) {
        // load helper component of ODT export plugin, which convert the color name to it's value
        $odt_colors = plugin_load('helper', 'odt_csscolors');

        $use_span = true;
        $sub_on = false;
        $super_on = false;
        $style_name = $this->odt_style_prefix.$this->odt_style_count;
        $this->odt_style_count++;
        $style = '<style:style style:name="'.$style_name.'" style:family="text" style:vertical-align="auto"><style:text-properties';
        $match = false;
        foreach($attrs as $type => $val) {
            switch ($type) {
                case 'ff':
                    $match = true;
                    $style .= ' fo:font-family="'.$val.'"';
                    break;
                case 'bg':
                    $match = true;
                    if (strstr($val,'#') == false) {
                       // Convert the color name to it's value, if possible... (default white)
                        $val = ($odt_colors != NULL) ? $odt_colors->getColorValue($val) : '#ffffff';
                    }
                    $style .= ' fo:background-color="'.$val.'"';
                    break;
                case 'fc':
                    $match = true;
                    if (strstr($val,'#') == false) {
                       // Convert the color name to it's value, if possible... (default black)
                        $val = ($odt_colors != NULL) ? $odt_colors->getColorValue($val) : '#000000';
                    }
                    $style .= ' fo:color="'.$val.'"';
                    break;
                case 'fw':
                    $match = true;
                    $style .= ' fo:font-weight="'.$val.'"';
                    break;
                case 'fs':
                    // This will currently not work because font-size does not work in autostyles.
                    $match = true;
                    $style .= ' fo:font-size="'.$val.'"';
                    break;
                case 'fv':
                    $match = true;
                    $style .= ' fo:font-variant="'.$val.'"';
                    break;
                case 'lh':
                    // Line-Height in ODT only works with pharagraphs. Switch off span.
                    $match = true;
                    $use_span = false;
                    $style .= ' fo:line-height="400%"';
                    break;
                case 'ls':
                    // Not all CSS units are supported by ODT!
                    $match = true;
                    $style .= ' fo:letter-spacing="'.$val.'"';
                    break;
                case 'ws':
                    // Not supported by ODT!
                    break;
                case 'sp':
                    // Not supported right now!
                    break;
                case 'va':
                    // Vertical alignment: ODT only supports top, middle, bottom and auto...
                    if ( $val == 'top' || $val == 'middle' || $val == 'bottom' || $val == 'auto' ) {
                        $match = true;
                        $style = str_replace('style:vertical-align="auto"', 'style:vertical-align="'.$val.'"', $style);
                    }

                    // ...but the ODT renderer has build-in styles for sub and super.
                    if ($val == 'sub') {
                        $sub_on = true;
                        $super_on = false;
                    }
                    if ($val == 'super') {
                        $sub_on = false;
                        $super_on = true;
                    }
                    break;
            }
        }

        if ($match == true) {
            // If the style still includes 'style:vertical-align="auto"' then we can delete it
            // for brevity because it is the default.
            $style = str_replace('style:vertical-align="auto"', '', $style);

            $style .= '/></style:style>';
            if ($use_span == false) {
                // If we use a paragraph, then the style family has to be paragraph.
                $style = str_replace('style:family="text"', 'style:family="paragraph"', $style);
                $style = str_replace('style:text-properties', 'style:paragraph-properties', $style);
            }
        } else {
            // Nothing matched for ODT style. Clear it. Prevents empty styles.
            $style = NULL;
            $style_name = NULL;
        }
        return array ($style_name, $style, $use_span, $sub_on, $super_on);
    }
}
