<?php
/**
 * DokuWiki plugin Typography; helper component
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author Satoshi Sahara <sahara.satoshi@gmail.com>
 */
// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

class helper_plugin_typography_parser extends DokuWiki_Plugin
{
    protected $properties, $specifications;

    function __construct() {
        // allowable parameters and relevant CSS properties
        $this->properties = array(
            'wf' => 'wf',           // exceptional class="wf-webfont"
            'ff' => 'font-family',
            'fc' => 'color',
            'bg' => 'background-color',
            'fs' => 'font-size',
            'fw' => 'font-weight',
            'fv' => 'font-variant',
            'lh' => 'line-height',
            'ls' => 'letter-spacing',
            'ws' => 'word-spacing',
            'va' => 'vertical-align',
            'sp' => 'white-space',
              0  => 'text-shadow',
              1  => 'text-transform',
        );

        // valid patterns of css properties
        $this->specifications = array(
            'wf' => '/^[a-zA-Z_-]+$/',
            'ff' => '/^((\'[^,]+?\'|[^ ,]+?) *,? *)+$/',
            'fc' => '/(^\#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$)|'
                   .'(^rgb\((\d{1,3}%?,){2}\d{1,3}%?\)$)|'
                   .'(^[a-zA-Z]+$)/',
            'bg' => '/(^\#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$)|'
                   .'(^rgb\((\d{1,3}%?,){2}\d{1,3}%?\)$)|'
                   .'(^rgba\((\d{1,3}%?,){3}[\d.]+\)$)|'
                   .'(^[a-zA-Z]+$)/',
            'font-size' =>
                 '/^(?:\d+(?:\.\d+)?(?:px|em|ex|pt|%)'
                .'|(?:x{1,2}-)?small|medium|(?:x{1,2}-)?large|smaller|larger)$/',
            'font-weight' =>
                 '/^(?:\d00|normal|bold|bolder|lighter)$/',
            'font-variant' =>
                 '/^(?:normal|small-?caps)$/',
            'line-height' =>
                 '/^\d+(?:\.\d+)?(?:px|em|ex|pt|%)?$/',
            'letter-spacing' =>
                 '/^-?\d+(?:\.\d+)?(?:px|em|ex|pt|%)$/',
            'word-spacing' =>
                 '/^-?\d+(?:\.\d+)?(?:px|em|ex|pt|%)$/',
            'vertical-align' =>
                 '/^-?\d+(?:\.\d+)?(?:px|em|ex|pt|%)$|'
                .'^(?:baseline|sub|super|top|text-top|middle|bottom|text-bottom|inherit)$/',
            'white-space' =>
                 '/^(?:normal|nowrap|pre|pre-line|pre-wrap)$/',
        );
    }

    /**
     * Get allowed CSS properties
     *
     * @return  array
     */
    public function getAllowedProperties()
    {
        return $this->properties;
    }

    /**
     * Set allowed CSS properties
     *
     * @param array $props  allowable CSS property name
     * @return  bool
     */
    public function setAllowedProperties(array $properties)
    {
        $this->properties = $properties;
        return true;
    }

    /**
     * validation of CSS property short name
     *
     * @param   string $name  short name of CSS property
     * @return  bool  true if defined
     */
    public function is_short_property($name)
    {
        return isset($this->properties[$name]);
    }

    /**
     * parse style attribute of an element
     *
     * @param   string $style  style attribute of an element
     * @param   bool $filter   allow only CSS properties defined in $this->props
     * @return  array  an associative array holds 'declarations' and 'classes'
     */
    public function parse_inlineCSS($style, $filter=true)
    {
        if (empty($style)) return array();

        $elem = array(
          //'tag'          => 'span',
            'declarations' => array(), // css property:value pairs of style attribute
            'classes'      => array(), // splitted class attribute
        );

        $tokens = explode(';', $style);

        foreach ($tokens as $declaration) {
            $item = array_map('trim', explode(':', $declaration, 2));
            if (!isset($item[1])) continue;

            // check CSS property name
            if (isset($this->properties[$item[0]])) {
                $name = $this->properties[$item[0]];
            } elseif (in_array($item[0], $this->properties)) {
                $name = $item[0];
            } elseif ($filter === false) {
                $name = $item[0];  // assume as CSS property
            } else {
                continue;          // ignore unknown property
            }

            // check CSS property value
            if (isset($this->specifications[$name])) {
                if (preg_match($this->specifications[$name], $item[1], $matches)) {
                    $value = $item[1];
                } else {
                    continue; // ignore invalid property value
                }
                if (($name == 'font-variant') && ($value == 'smallcaps')) {
                    $value = 'small-caps';
                }
            } else {
                $value = htmlspecialchars($item[1], ENT_COMPAT, 'UTF-8');
            }

            if ($name == 'wf') {
                // webfont : wf: webfont_class_without_prefix;
                $elem['classes'] += array('webfont' => 'wf-'.$value);
            } else {
                // declaration : CSS property-value pairs
                $elem['declarations'] += array($name => $value);
            }
        }

        // unset empty attributes of an element
        foreach (array_keys($elem) as $key) {
           if (empty($elem[$key])) unset($elem[$key]);
        }
        return $elem;
    }

    /**
     * build inline CSS for style attribute of an element
     *
     * @param   array $declarations  CSS property-value pairs
     * @return  string  inline CSS for style attribute
     */
    public function build_inlineCSS(array $declarations)
    {
        $css = array();
        foreach ($declarations as $name => $value) {
            $css[] = $name.':'.$value.';';
        }
        return implode(' ', $css);
    }

    /**
     * build style and class attribute of an element
     *
     * @param   array $elem  holds 'declarations' and 'classes'
     * @param   array $addClasses  class items to be added
     * @return  string  attributes of an element
     */
    public function build_attributes(array $elem, array $addClasses=array())
    {
        $attr = $css = $item = array();

        if (isset($elem['declarations'])) {
            foreach ($elem['declarations'] as $name => $value) {
                $css[] = $name.':'.$value.';';
            }
            $attr['style'] = implode(' ', $css);
        }

        if (!empty($addClasses)) {
            $elem['classes'] = isset($elem['classes']) ?: array();
            $elem['classes'] = array_unique($elem['classes'] + $addClasses);
        }

        if (isset($elem['classes'])) {
            $attr['class'] = implode(' ', $elem['classes']);
        }

        foreach ($attr as $key => $value) {
            $item[] = $key.'="'.$value.'"';
        }
        $out = empty($item) ? '' : ' '.implode(' ', $item);
        return $out;
    }

}
