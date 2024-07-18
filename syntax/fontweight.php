<?php
/**
 * DokuWiki Plugin Typography; Syntax typography fontweight
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 *
 */

require_once(__DIR__.'/base.php');

class syntax_plugin_typography_fontweight extends syntax_plugin_typography_base
{
    /**
     * Connect pattern to lexer
     */
    public function preConnect()
    {
        // drop 'syntax_' from class name
        $this->mode = substr(static::class, 7);

        // syntax pattern
        $this->pattern[1] = '<fw\b.*?>(?=.*?</fw>)';
        $this->pattern[4] = '</fw>';
    }

}
