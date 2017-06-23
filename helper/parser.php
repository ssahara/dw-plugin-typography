<?php
/**
 * DokuWiki plugin Typography; helper component
 *
 * @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author Satoshi Sahara <sahara.satoshi@gmail.com>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class helper_plugin_typography_parser extends DokuWiki_Plugin {

    protected $props, $cond;

    function __construct() {
        // allowable parameters and relevant CSS properties
        $this->props = array(
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

        // allowable property pattern for parameters
        $this->conds = array(
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
    function getAllowedProps() {
        return $this->props;
    }

    /**
     * Set allowed CSS properties
     *
     * @param array $props  allowable CSS property name
     * @return  bool
     */
    function setAllowedProps(array $props) {
        $this->props = $props;
        return true;
    }

    /**
     * validation of CSS property short name
     *
     * @param   string $name  short name of CSS property
     * @return  bool  true if defined
     */
    function is_short_property($name) {
        return isset($this->props[$name]);
    }

    /**
     * parse style attribute of an element
     *
     * @param   string $style  style attribute of an element
     * @param   bool $filter   allow only CSS properties defined in $this->props
     * @return  array  an associative array containing CSS property-value pairs
     */
     function parse_inlineCSS($style, $filter=true) {
       $css = array();
        if (empty($style)) return $css;

        $declarations = explode(';', $style);

        foreach ($declarations as $declaration) {
            $property = array_map('trim', explode(':', $declaration, 2));
            if (!isset($property[1])) continue;

            // check CSS property name
            if (isset($this->props[$property[0]])) {
                $name = $this->props[$property[0]];
            } elseif (in_array($property[0], $this->props)) {
                $name = $property[0];
            } elseif ($filter === false) {
                $name = $property[0];  // assume as CSS property
            } else {
                continue;              // ignore unknown property
            }

            // check CSS property value
            if (isset($this->conds[$name])) {
                if (preg_match($this->conds[$name], $property[1], $matches)) {
                    $value = $property[1];
                } else {
                    continue;
                }
                if (($name == 'font-variant') && ($value == 'smallcaps')) {
                    $value = 'small-caps';
                }
            } else {
                $value = htmlspecialchars($property[1], ENT_COMPAT, 'UTF-8');
            }
            //$css[$name] = $value;
            $css += array($name => $value);
        }
        return $css;
    }

    /**
     * build inline CSS for style attribute of an element
     *
     * @param   array $declarations  CSS property-value pairs
     * @return  string  inline CSS for style attribute
     */
    function build_inlineCSS(array $declarations) {
        $css = array();
        foreach ($declarations as $name => $value) {
            $css[] = $name.':'.$value.';';
        }
        return implode(' ', $css);
    }

}
