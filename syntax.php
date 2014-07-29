<?php
/**
 * Plugin Color: Sets new colors for text and background.
 * Plugin Block: Allows to set width and float alignment for page elements.
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Paweł Piekarski <qentinson@gmail.com>
 */
 
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
 
class syntax_plugin_typography extends DokuWiki_Syntax_Plugin {

	function getInfo(){
		return array(
            'author' => 'Paweł Piekarski',
            'email'  => 'qentinson@gmail.com',
            'date'   => '2011-01-18',
            'name'   => 'Typography',
            'desc'   => 'Empower dokuwiki with html typographic abilities.',
            'url'    => 'http://piekarnia.edl.pl/projects/typography.html',
		);
	}

    function getType(){ return 'formatting'; }
	function getSort() { return 667; }
	function getAllowedTypes() { return array('formatting', 'substition', 'disabled'); }
	
    function connectTo($mode) {
		$this->Lexer->addEntryPattern('<typo (?:(?:ff:|fs:|lh:|ls:-?|ws:-?|va:-?|fv:){1}(?:[0-9]+px|[0-9]+(?:\.[0-9]+)?em|[0-9]+(?:\.[0-9]+)?ex|[0-9]+(?:\.[0-9]+)?pt|[0-9]{1,3}%|smallcaps{1}|baseline{1}|sub{1}|super{1}|top{1}|text-top{1}|middle{1}|bottom{1}|text-bottom{1}|inherit{1}|[a-zA-Z0-9]{2}[a-zA-Z0-9\ ,]*[a-zA-Z0-9]{1}){1};\ ?){1,6}>(?=.*?</typo>)', $mode,'plugin_typography');
	}
	function postConnect() {
		$this->Lexer->addExitPattern('</typo>', 'plugin_typography');
	}
 
	function handle($match, $state, $pos, &$handler) {
		switch($state) {
			case DOKU_LEXER_ENTER:
				$conds = array(
					'ff' => '/^[a-zA-Z0-9\ ,]{2,}[a-zA-Z0-9]$/',
					'fs' => '/^[0-9]+(?:\.[0-9]+)?(px|em|ex|pt|%)$/',
					'lh' => '/^[0-9]+(?:\.[0-9]+)?(px|em|ex|pt|%)$/',
					'ls' => '/^-?[0-9]+(?:\.[0-9]+)?(px|em|ex|pt|%)$/',
					'ws' => '/^-?[0-9]+(?:\.[0-9]+)?(px|em|ex|pt|%)$/',
					'va' => '/(-?[0-9]+(?:\.[0-9]+)?(px|em|ex|pt|%)|baseline|sub|super|top|text-top|middle|bottom|text-bottom|inherit)/',
					'fv' => '/^smallcaps$/',
				);
				$props = array(
					'ff' => 'font-family:',
					'fs' => 'font-size:',
					'lh' => 'line-height:',
					'ls' => 'letter-spacing:',
					'ws' => 'word-spacing:',
					'va' => 'vertical-align:',
					'fv' => 'font-variant:',
				);
				$typo = trim(substr($match, 5, -1));
				$tokens = explode(';', $typo);
				$css = '';

				foreach($tokens as $t) {
					if ($t == '') { continue; }
					list($type, $val) = explode(':', trim($t));
					if (!isset($conds[$type])) {
						$css='';
						break;
					}
					if (preg_match($conds[$type], $val)) {
						if ($val == 'smallcaps') { $val = 'small-caps'; }
						$css .= $props[$type].$val.' !important; ';
					}
					unset($conds[$type]); // do not use styling twice
				}
				if (!preg_match('/vertical-align:/', $css)) {
					$css .= ' vertical-align: top; ';
				}

				if ($css != '') {
					return array($state, $css);
				}

				return array($state, $match);
			case DOKU_LEXER_UNMATCHED: return array($state, $match);
			case DOKU_LEXER_EXIT: return array($state, '');
		}
		return array();
	}
	
	function render($mode, &$renderer, $data) {
		if ($mode != 'xhtml') return false;

		list($state, $match) = $data;
		$render->doc .= $match;
		switch ($state) {
			case DOKU_LEXER_ENTER:
				if ($match[0] == '<') {
					$renderer->doc .= "<span>&lt;".substr($match, 1, -1).'&gt;';
				} else {
					$renderer->doc .= "<span style=\"$match\">";
				}
				break;
			case DOKU_LEXER_UNMATCHED:
				$renderer->doc .= $renderer->_xmlEntities($match);
				break;
			case DOKU_LEXER_EXIT:
				$renderer->doc .= "</span>";
				break;
		}
		return true;
	}
}

?>
