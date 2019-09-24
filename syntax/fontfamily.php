<?php
/**
 * DokuWiki Plugin Typography; Syntax typography fontfamily
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 *
 * provide family plugin syntax compatibility
 * @see also https://www.dokuwiki.org/plugin:fontsize2
 */

require_once(dirname(__FILE__).'/base.php');

class syntax_plugin_typography_fontfamily extends syntax_plugin_typography_base
{
    protected $pattern = array(
        1 => '<ff\b.*?>(?=.*?</ff>)',
        4 => '</ff>',
    );

    // Connect pattern to lexer
    public function connectTo($mode)
    {
        if (plugin_isdisabled('fontfamily')) {
            $this->Lexer->addEntryPattern($this->pattern[1], $mode, $this->mode);
        }
    }

    public function postConnect()
    {
        if (plugin_isdisabled('fontfamily')) {
            $this->Lexer->addExitPattern($this->pattern[4], $this->mode);
        }
    }

}
