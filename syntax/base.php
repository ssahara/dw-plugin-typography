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

class syntax_plugin_typography_base extends DokuWiki_Syntax_Plugin {

    protected $entry_pattern = '<typo\b.*?>(?=.*?</typo>)';
    protected $exit_pattern  = '</typo>';

    protected $mode, $props, $cond;

    public function __construct() {
        $this->mode = substr(get_class($this), 7); // drop 'syntax_' from class name

        // allowable parameters and relevant css properties
        $this->props = array(
            'ff' => 'font-family',
            'fc' => 'color',
            'bg' => 'background-color',
            'fs' => 'font-size',
            'fw' => 'font-weight',
            'fv' => 'font-variant',
            'lh' => 'line-height',
            'ls' => 'letter-spacing',
            'ws' => 'word-spacing',
            'va' => 'vertical-align',
            'sp' => 'white-space',
        );

        // allowable property pattern for parameters
        $this->conds = array(
            'ff' => '/^((\'[^,]+?\'|[^ ,]+?) *,? *)+$/',
            'fc' => '/(^\#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$)|'
                   .'(^rgb\((\d{1,3}%?,){2}\d{1,3}%?\)$)|'
                   .'(^[a-zA-Z]+$)/',
            'bg' => '/(^\#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$)|'
                   .'(^rgb\((\d{1,3}%?,){2}\d{1,3}%?\)$)|'
                   .'(^rgba\((\d{1,3}%?,){3}[\d.]+\)$)|'
                   .'(^[a-zA-Z]+$)/',
            'font-size' =>
                 '/(^\d+(?:\.\d+)?(?:px|em|ex|pt|%))$|'
                .'^(x{1,2}-small|small|medium|large|x{1,2}-large|smaller|larger)$/',
            'font-weight' =>
                 '/^(?:normal|bold|bolder|lighter|\d00)$/',
            'font-variant' =>
                 '/^(?:normal|small-?caps)$/',
            'line-height' =>
                 '/^\d+(?:\.\d+)?(?:px|em|ex|pt|%)?$/',
            'letter-spacing' =>
                 '/^-?\d+(?:\.\d+)?(?:px|em|ex|pt|%)$/',
            'word-spacing' =>
                 '/^-?\d+(?:\.\d+)?(?:px|em|ex|pt|%)$/',
            'vertical-align' =>
                 '/^-?\d+(?:\.\d+)?(?:px|em|ex|pt|%)$|'
                .'^(?:baseline|sub|super|top|text-top|middle|bottom|text-bottom|inherit)$/',
            'white-space' =>
                 '/^(?:normal|nowrap|pre|pre-line|pre-wrap)$/',
        );
    }

    public function getType() { return 'formatting'; }
    public function getSort() { return 67; } // = Doku_Parser_Mode_formatting:strong -3
    public function getAllowedTypes() { return array('formatting', 'substition', 'disabled'); }
    // override default accepts() method to allow nesting - ie, to get the plugin accepts its own entry syntax
    public function accepts($mode) {
        if ($mode == $this->mode) return true;
        return parent::accepts($mode);
    }

    // Connect pattern to lexer
    public function connectTo($mode) {
        $this->Lexer->addEntryPattern($this->entry_pattern, $mode, $this->mode);
    }
    public function postConnect() {
        $this->Lexer->addExitPattern($this->exit_pattern, $this->mode);
    }

    /*
     * Handle the match
     */
    public function handle($match, $state, $pos, Doku_Handler $handler) {
        switch($state) {
            case DOKU_LEXER_ENTER:
                $markup = substr($this->exit_pattern, 2, -1);

                $params = strtolower(ltrim(substr($match, strlen($markup)+1, -1)));

                if (array_key_exists($markup, $this->props)) {
                    $params = $markup.(($params[0] == ':') ? '' : ':').$params;
                }

                // parse css rule-set
                $css = array();
                $tokens = explode(';', $params);
                foreach ($tokens as $token) {
                    $property = array_map('trim', explode(':', $token, 2));
                    if (!isset($property[1])) continue;

                    // check css property name
                    if (array_key_exists($property[0], $this->props)) {
                        $name  = $this->props[$property[0]];
                    } elseif (in_array($property[0], $this->props)) {
                        $name  = $property[0];
                    } else {
                        continue;
                    }

                    // check css property value
                    if (isset($this->conds[$name])) {
                        if (preg_match($this->conds[$name], $property[1], $matches)) {
                            $value = $property[1];
                        } else {
                            continue;
                        }
                        if (($name == 'font-variant') && ($value == 'smallcaps')) {
                            $value = 'small-caps';
                        }
                    } else {
                        $value = htmlspecialchars($property[1], ENT_COMPAT, 'UTF-8');
                    }
                    //$css[$name] = $value;
                    $css += array($name => $value);
                }
                return array($state, $css);
            case DOKU_LEXER_UNMATCHED:
                $handler->_addCall('cdata', array($match), $pos);
                return false;
            case DOKU_LEXER_EXIT:
                return array($state, '');
        }
        return array();
    }

    /*
     * Create output
     */
    public function render($format, Doku_Renderer $renderer, $indata) {
        if (empty($indata)) return false;
        switch ($format) {
            case 'xhtml':
                return $this->render_xhtml($renderer, $indata);
            case 'odt':
                // ODT export;
                $odt = $this->loadHelper('typography_odt');
                return $odt->render($renderer, $indata);
            default:
                return false;
        }
    }

    protected function render_xhtml(Doku_Renderer $renderer, $indata) {
        list($state, $data) = $indata;
        switch ($state) {
            case DOKU_LEXER_ENTER:
                // build css rule-set
                $css = array();
                foreach ($data as $name => $value) {
                    $css[] = $name.':'.$value.';';
                }
                $style = implode(' ', $css);
                $attr = $style ? ' style="'.$style.'"' : '';
                $renderer->doc .= '<span'.$attr.'>';
                break;

            case DOKU_LEXER_EXIT:
                $renderer->doc .= '</span>';
                break;
        }
        return true;
    }

}
