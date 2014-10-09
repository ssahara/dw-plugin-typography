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
    protected $odt_style_count = 0;
    protected $closing_stack = NULL;

    public function __construct() {
        $this->pluginMode = implode('_', array('plugin',$this->getPluginName(),$this->getPluginComponent(),));

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

        $this->closing_stack = new SplStack();
    }

    public function getType(){ return 'formatting'; }
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

                $css = '';
                $odt_use_span = true;
                $odt_sub_on = false;
                $odt_super_on = false;
                $odt_style_name = 'plugin_typography_'.$this->odt_style_count;
                $this->odt_style_count++;
                $odt_style = '<style:style style:name="'.$odt_style_name.'" style:family="text" style:vertical-align="auto"><style:text-properties';
                $odt_match = false;
                foreach($tokens as $token) {
                    if (empty($token)) continue;
                    list($type, $val) = explode(':', trim($token));
                    if (!array_key_exists($type, $this->props)) continue;
                    if (preg_match($this->conds[$type], $val)) {
                        if ($val == 'smallcaps') { $val = 'small-caps'; }
                        $css .= $this->props[$type].$val.'; ';
                    }

                    if (($type == 'bg' || $type == 'fc') && strstr($val,'#') == false) {
                        require_once (DOKU_PLUGIN.'typography/syntax/csscolors.php');
                        $val = CSSColors::getColorValue($val);
                    }
                    switch ($type) {
                        case 'ff':
                            $odt_match = true;
                            $odt_style .= ' fo:font-family="'.$val.'"';
                            break;
                        case 'bg':
                            $odt_match = true;
                            $odt_style .= ' fo:background-color="'.$val.'"';
                            break;
                        case 'fc':
                            $odt_match = true;
                            $odt_style .= ' fo:color="'.$val.'"';
                            break;
                        case 'fw':
                            $odt_match = true;
                            $odt_style .= ' fo:font-weight="'.$val.'"';
                            break;
                        case 'fs':
                            // This will currently not work because font-size does not work in autostyles.
                            $odt_match = true;
                            $odt_style .= ' fo:font-size="'.$val.'"';
                            break;
                        case 'fv':
                            $odt_match = true;
                            $odt_style .= ' fo:font-variant="'.$val.'"';
                            break;
                        case 'lh':
                            // Line-Height in ODT only works with pharagraphs. Switch off span.
                            $odt_match = true;
                            $odt_use_span = false;
                            $odt_style .= ' fo:line-height="400%"';
                            break;
                        case 'ls':
                            // Not all CSS units are supported by ODT!
                            $odt_match = true;
                            $odt_style .= ' fo:letter-spacing="'.$val.'"';
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
                                $odt_match = true;
                                $odt_style = str_replace('style:vertical-align="auto"', 'style:vertical-align="'.$val.'"', $odt_style);
                            }

                            // ...but the ODT renderer has build-in styles for sub and super.
                            if ($val == 'sub') {
                                $odt_sub_on = true;
                                $odt_super_on = false;
                            }
                            if ($val == 'super')
                                $odt_sub_on = false;
                                $odt_super_on = true;
                            break;
                    }
                }

                if ($odt_match == true) {
                    // If the style still includes 'style:vertical-align="auto"' then we can delete it
                    // for brevity because it is the default.
                    $odt_style = str_replace('style:vertical-align="auto"', '', $odt_style);

                    $odt_style .= '/></style:style>';
                    if ($odt_use_span == false) {
                        // If we use a paragraph, then the style family has to be paragraph.
                        $odt_style = str_replace('style:family="text"', 'style:family="paragraph"', $odt_style);
                        $odt_style = str_replace('style:text-properties', 'style:paragraph-properties', $odt_style);
                    }
                } else {
                    // Nothing matched for ODT style. Clear it. Prevents empty styles.
                    $odt_style = NULL;
                    $odt_style_name = NULL;
                }

                return array($state, $css, $odt_style_name, $odt_style, $odt_use_span, $odt_sub_on, $odt_super_on);
                break;
            case DOKU_LEXER_UNMATCHED: return array($state, $match);
            case DOKU_LEXER_EXIT:      return array($state, '');
        }
        return array();
    }

    /*
     * Create output
     */
    public function render($format, Doku_Renderer $renderer, $data) {
        if ($format == 'xhtml') {
            list($state, $match) = $data;
            switch ($state) {
                case DOKU_LEXER_ENTER:
                    $renderer->doc .= '<span style="'.$match.'">';
                    break;
                case DOKU_LEXER_UNMATCHED:
                    $renderer->doc .= $renderer->_xmlEntities($match);
                    break;
                case DOKU_LEXER_EXIT:
                    $renderer->doc .= '</span>';
                    break;
            }
            return true;
        } else if ($format == 'odt') {
            if ($this->closing_stack == NULL) {
                // Stack not setup???
                return (true);
            }

            list($state, $match, $odt_style_name, $odt_style, $odt_use_span, $odt_sub_on, $odt_super_on) = $data;
            switch ($state) {
                case DOKU_LEXER_ENTER:
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
                    $renderer->doc .= $renderer->_xmlEntities($match);
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
        return false;
    }
}
