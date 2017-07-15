<?php
/**
 * DokuWiki Plugin Typography; Syntax typography webfont
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 *
 */

require_once(dirname(__FILE__).'/base.php');

class syntax_plugin_typography_webfont extends syntax_plugin_typography_base {

    protected $entry_pattern = '<wf\b.*?>(?=.*?</wf>)';
    protected $exit_pattern  = '</wf>';

}
