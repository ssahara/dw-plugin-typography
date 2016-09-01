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

class syntax_plugin_typography_fontcolor extends syntax_plugin_typography_base {

    protected $entry_pattern = '<fc\b(?: .+?)?>(?=.+?</fc>)';
    protected $exit_pattern  = '</fc>';

    // Connect pattern to lexer
    public function connectTo($mode) {
        if (plugin_isdisabled('fontcolor')) {
            $this->Lexer->addEntryPattern($this->entry_pattern, $mode, $this->mode);
        }
    }

}
