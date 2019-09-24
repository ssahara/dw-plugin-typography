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
    protected $pattern = array(
        1 => '<fc\b.*?>(?=.*?</fc>)',
        4 => '</fc>',
    );

    // Connect pattern to lexer
    function connectTo($mode) {
        if (plugin_isdisabled('fontcolor')) {
            $this->Lexer->addEntryPattern($this->pattern[1], $mode, $this->mode);
        }
    }
    function postConnect() {
        if (plugin_isdisabled('fontcolor')) {
            $this->Lexer->addExitPattern($this->pattern[4], $this->mode);
        }
    }

}
