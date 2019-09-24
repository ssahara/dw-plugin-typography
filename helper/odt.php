<?php
/**
 * ODT (Open Document format) export for Typography plugin
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Lars (LarsDW223)
 */

if (!defined('DOKU_INC')) die();

class helper_plugin_typography_odt extends DokuWiki_Plugin
{
    protected $closing_stack = NULL; // used in odt_render()

    public function render(Doku_Renderer $renderer, $data)
    {
        list($state, $tag_data) = $data;

        if (is_null($this->closing_stack)) {
            $this->closing_stack = new SplStack(); //require PHP 5 >= 5.3.0
        }

        switch ($state) {
            case DOKU_LEXER_ENTER:
                // build inline css
                $css = array();
                foreach ($tag_data['declarations'] as $name => $value) {
                    $css[] = $name.':'.$value.';';
                }
                $style = implode(' ', $css);

                if (isset($data['line-height'])) {
                    $renderer->p_close();
                    if (method_exists ($renderer, '_odtParagraphOpenUseCSSStyle')) {
                        $renderer->_odtParagraphOpenUseCSSStyle($style);
                    } else {
                        $renderer->_odtParagraphOpenUseCSS('p', 'style="'.$style.'"');
                    }
                    $this->closing_stack->push('p');
                } else {
                    if (method_exists ($renderer, '_odtSpanOpenUseCSSStyle')) {
                        $renderer->_odtSpanOpenUseCSSStyle($style);
                    } else {
                        $renderer->_odtSpanOpenUseCSS('span', 'style="'.$style.'"');
                    }
                    $this->closing_stack->push('span');
                }
                break;

            case DOKU_LEXER_EXIT:
                try {
                    $content = $this->closing_stack->pop();
                    if ($content == 'p') {
                        // For closing paragraphs use the renderer's function otherwise the internal
                        // counter in the ODT renderer is corrupted and so would be the ODT file.
                        $renderer->p_close();
                        $renderer->p_open();
                    } else {
                        // Close the span.
                        $renderer->_odtSpanClose();
                    }
                } catch (Exception $e) {
                    // May be included for debugging purposes.
                    //$renderer->doc .= $e->getMessage();
                }
                break;
        }
        return true;
    }
}
