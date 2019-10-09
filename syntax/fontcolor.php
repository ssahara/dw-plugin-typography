<?php
/**
 * DokuWiki Plugin Typography; Syntax typography fontcolor
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 *
 * provide fontcolor plugin syntax compatibility
 * @see also https://www.dokuwiki.org/plugin:fontcolor
 */

require_once(dirname(__FILE__).'/base.php');

class syntax_plugin_typography_fontcolor extends syntax_plugin_typography_base
{
    /**
     * Connect pattern to lexer
     */
    public function preConnect()
    {
        // drop 'syntax_' from class name
        $this->mode = substr(get_class($this), 7);

        // syntax pattern
        $this->pattern[1] = '<fc\b.*?>(?=.*?</fc>)';
        $this->pattern[4] = '</fc>';
    }

    public function connectTo($mode)
    {
        if (plugin_isdisabled('fontcolor')) {
            $this->Lexer->addEntryPattern($this->pattern[1], $mode, $this->mode);
        }
    }

    public function postConnect()
    {
        if (plugin_isdisabled('fontcolor')) {
            $this->Lexer->addExitPattern($this->pattern[4], $this->mode);
        }
    }

}
