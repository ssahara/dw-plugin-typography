<?php
/**
 * DokuWiki Plugin Typography; Syntax typography bgcolor (background-color)
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 *
 */

require_once(__DIR__.'/base.php');

class syntax_plugin_typography_bgcolor extends syntax_plugin_typography_base
{
    /**
     * Connect pattern to lexer
     */
    public function preConnect()
    {
        // drop 'syntax_' from class name
        $this->mode = substr(static::class, 7);

        // syntax pattern
        $this->pattern[1] = '<bg\b.*?>(?=.*?</bg>)';
        $this->pattern[4] = '</bg>';
    }

}
