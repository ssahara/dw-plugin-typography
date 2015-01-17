<?php
/**
 * DokuWiki Plugin Typography; Syntax typography fontsize
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 *
 * provide fontsize2 plugin syntax compatibility
 * @see also https://www.dokuwiki.org/plugin:fontsize2
 */

require_once(dirname(__FILE__).'/base.php');

class syntax_plugin_typography_fontsize extends syntax_plugin_typography_base {

    protected $entry_pattern = '<fs\b(?: .+?)?>(?=.+?</fs>)';
    protected $exit_pattern  = '</fs>';

    // Connect pattern to lexer
    public function connectTo($mode) {
        if (plugin_isdisabled('fontsize2')) {
            $this->Lexer->addEntryPattern($this->entry_pattern, $mode, $this->pluginMode);
        }
    }

}
