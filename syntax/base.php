<?php
/**
 * DokuWiki plugin Typography; Syntax typography base component
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Paweł Piekarski <qentinson@gmail.com>
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

class syntax_plugin_typography_base extends DokuWiki_Syntax_Plugin
{
    public function getType()
    {   // Syntax Type
        return 'formatting';
    }

    public function getAllowedTypes()
    {   // Allowed Mode Types
        return array('formatting', 'substition', 'disabled');
    }

    /**
     * Connect pattern to lexer
     */
    protected $mode, $pattern;

    public function preConnect()
    {
        // drop 'syntax_' from class name
        $this->mode = substr(get_class($this), 7);

        // syntax pattern
        $this->pattern[1] = '<typo\b.*?>(?=.*?</typo>)';
        $this->pattern[4] = '</typo>';
    }

    public function connectTo($mode)
    {
        $this->Lexer->addEntryPattern($this->pattern[1], $mode, $this->mode);
    }

    public function postConnect()
    {
        $this->Lexer->addExitPattern($this->pattern[4], $this->mode);
    }

    public function getSort()
    {   // sort number used to determine priority of this mode
        return 67; // = Doku_Parser_Mode_formatting:strong -3
    }

    // plugin accepts its own entry syntax
    public function accepts($mode)
    {
        if ($mode == $this->mode) return true;
        return parent::accepts($mode);
    }


    /**
     * Plugin features
     */
    protected $styler = null;


    /*
     * Handle the match
     */
    public function handle($match, $state, $pos, Doku_Handler $handler)
    {
        switch($state) {
            case DOKU_LEXER_ENTER:
                // load prameter parser utility
                if (is_null($this->styler)) {
                    $this->styler = $this->loadHelper('typography_parser');
                }

                // identify markup keyword of this syntax class
                $markup = substr($this->pattern[4], 2, -1);

                // get inline CSS parameter
                $params = strtolower(ltrim(substr($match, strlen($markup)+1, -1)));
                if ($this->styler->is_short_property($markup)) {
                    $params = $markup.(($params[0] == ':') ? '' : ':').$params;
                }

                // get css property:value pairs as an associative array
                $tag_data = $this->styler->parse_inlineCSS($params);

                return $data = array($state, $tag_data);

            case DOKU_LEXER_UNMATCHED:
                $handler->base($match, $state, $pos);
                return false;

            case DOKU_LEXER_EXIT:
                return $data = array($state, '');
        }
        return array();
    }

    /*
     * Create output
     */
    public function render($format, Doku_Renderer $renderer, $data)
    {
        if (empty($data)) return false;
        switch ($format) {
            case 'xhtml':
                return $this->render_xhtml($renderer, $data);
            case 'odt':
                // ODT export;
                $odt = $this->loadHelper('typography_odt');
                return $odt->render($renderer, $data);
            default:
                return false;
        }
    }

    protected function render_xhtml(Doku_Renderer $renderer, $data)
    {
        list($state, $tag_data) = $data;
        switch ($state) {
            case DOKU_LEXER_ENTER:
                // load prameter parser utility
                if (is_null($this->styler)) {
                    $this->styler = $this->loadHelper('typography_parser');
                }
                // build attributes (style and class)
                $renderer->doc .= '<span'.$this->styler->build_attributes($tag_data).'>';
                break;

            case DOKU_LEXER_EXIT:
                $renderer->doc .= '</span>';
                break;
        }
        return true;
    }

}
