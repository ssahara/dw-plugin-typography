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

    protected $mode;
    protected $styler = null;

    public function __construct() {
        $this->mode = substr(get_class($this), 7); // drop 'syntax_' from class name
    }

    public function getType() { return 'formatting'; }
    public function getSort() { return 67; } // = Doku_Parser_Mode_formatting:strong -3
    public function getAllowedTypes() {
        return array('formatting', 'substition', 'disabled');
    }
    // plugin accepts its own entry syntax
    public function accepts($mode) {
        if ($mode == $this->mode) return true;
        return parent::accepts($mode);
    }

    /**
     * Connect pattern to lexer
     */
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
                // load prameter parser utility
                if (is_null($this->styler)) {
                    $this->styler = $this->loadHelper('typography_parser');
                }

                // identify markup keyword
                $markup = substr($this->exit_pattern, 2, -1);

                // get inline CSS parameter
                $params = strtolower(ltrim(substr($match, strlen($markup)+1, -1)));
                if ($this->styler->is_short_property($markup)) {
                    $params = $markup.(($params[0] == ':') ? '' : ':').$params;
                }

                // get css property:value pairs as an associative array
                $css = $this->styler->parse_inlineCSS($params);

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
                // load prameter parser utility
                if (is_null($this->styler)) {
                    $this->styler = $this->loadHelper('typography_parser');
                }
                // build inline CSS
                $style = $this->styler->build_inlineCSS($data);
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
