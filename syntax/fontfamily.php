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

class syntax_plugin_typography_fontfamily extends syntax_plugin_typography_base {

    protected $entry_pattern = '<ff\b(?: .+?)?>(?=.+?</ff>)';
    protected $exit_pattern  = '</ff>';

    // Connect pattern to lexer
    public function connectTo($mode) {
        if (plugin_isdisabled('fontfamily')) {
            $this->Lexer->addEntryPattern($this->entry_pattern, $mode, $this->pluginMode);
        }
    }

}
