<?php
/**
 * DokuWiki plugin Typography; Helper Component
 * implement extended getAttributes method that identifies style (inline css)
 * as well as classes, width, lang and dir from WRAP plugin syntax.
 * The style parameter starts "=" letter and follows quoted css declarations
 *   eg. <STYLE ="background-color: #ccc;">...</STYLE>
 *
 * Identified classes are returned as array, but other attributes as string
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Anika Henke <anika@selfthinker.org>
 * @modified by Satoshi Sahara <sahara.satoshi@gmail.com>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

class helper_plugin_typography_wrap extends DokuWiki_Plugin {

    // default settings of WRAP plugin
    protected $config = array(
        'noPrefix'          => 'tabs, group',
        'restrictionType'   => 0,
        'restrictedClasses' => '',
        'darkTpl'           => 0,
    );

    protected $noPrefix = '';   // '/^(?:tabs|group|wf-.*)$/'
    protected $restrictedClasses = '';

    function __construct() {
        // retrieve settings of WRAP plugin
        if ($wrap = $this->loadHelper('wrap', true)) {
            $this->config['noPrefix'] = $wrap->getConf('noPrefix');
            $this->config['restrictionType']   = $wrap->getConf('restrictionType');
            $this->config['restrictedClasses'] = $wrap->getConf('restrictedClasses');
            $this->config['darkTpl']           = $wrap->getConf('darkTpl');
        }

        // noPrefix: comma separated class names that should be excluded from
        //   being prefixed with "wrap_",
        //   each item may contain wildcard (*, ?)
        $search  = ['?', '*' ];
        $replace = ['.', '.*'];
        if ($this->config['noPrefix']) {
            $csv = str_replace($search, $replace, $this->config['noPrefix']);
            $items = array_map('trim', explode(',', $csv));
            $this->noPrefix = '/^(?:'. implode('|', $items) .')$/';
        }

        // restrictedClasses : comma separated class names that should be checked
        //   based on restriction type (white or black list)
        //   each item may contain wildcard (*, ?)
        if ($this->config['restrictedClasses']) {
            $csv = str_replace($search, $replace, $this->config['restrictedClasses']);
            $items = array_map('trim', explode(',', $csv));
            $this->restrictedClasses = '/^(?:'. implode('|', $items) .')$/';
        }
    }


    /**
     * adjust wrap_ class name
     *
     * @param string $className
     * @return string
     */
    private function prefixhood($className) {
        $prefix = preg_match($this->noPrefix, $className) ? '' : 'wrap_';
        return $prefix.$className;
    }


    /**
     * get attributes (pull apart the string between '<wrap' and '>')
     *  and identify classes, width, lang and dir
     *
     * @author Anika Henke <anika@selfthinker.org>
     * @author Christopher Smith <chris@jalakai.co.uk>
     *   (parts taken from http://www.dokuwiki.org/plugin:box)
     *
     * @param string $data
     * @return array
     */
    function getAttributes($data) {

        $attr = array();
        $classes = array();

        //get style (css declarations)
        $pattern = '/\B=([\'"`])([^\'"`]*)\g{-2}\B/';
        if (preg_match($pattern, $data, $matches)) {
            $attr['style'] = $matches[2];
            $data = str_replace($matches[0], '', $data); // remove parsed substring
        }

        $tokens = preg_split('/\s+/', $data, 9);
        foreach ($tokens as $token) {
            if (empty($token)) continue;

            //get width
            if (preg_match('/^\d*\.?\d+(%|px|em|ex|pt|pc|cm|mm|in)$/', $token)) {
                $attr['width'] = $token;
                continue;
            }

            //get lang
            if (preg_match('/\:([a-z\-]+)/', $token)) {
                $attr['lang'] = trim($token,':');
                continue;
            }

            //get id
            if (preg_match('/^#([A-Za-z0-9_-]+)/', $token)) {
                $attr['id'] = trim($token,'#');
                continue;
            }

            //get classes
            //restrict token (class names) characters to prevent any malicious data
            if (preg_match('/[^A-Za-z0-9_-]/',$token)) continue;

            // class name restriction - two types
            //   0: exclude restricted classes,
            //   1: include restricted classes and exclude all others
            if ($this->restrictedClasses) {
                $classIsInList = preg_match($this->restrictedClasses, $tokene);
                if ($this->config['restrictionType'] xor $classIsInList) continue;
                    // 1 xor 1 = false  allow
                    // 1 xor 0 = true   not allow
                    // 0 xor 1 = true   not allow
                    // 0 xor 0 = false  allow
            }

            // prefix ajustment of class name
            $classes[] = $this->prefixhood($token);

        } // end of switch

        // class for Dark Tenplate support
        if ($this->config['darkTpl']) {
            $classes[] = 'wrap__dark';
        }

        // classes collected
        if ($classes) {
            $attr['classes'] = $classes;
            //$attr['class'] = implode(' ', $classes);
        }

        //get dir
        if ($attr['lang']) {
            $lang2dirFile = DOKU_PLUGIN.'/wrap/conf/lang2dir.conf';
            if (@file_exists($lang2dirFile)) {
                $lang2dir = confToHash($lang2dirFile);
                $attr['dir'] = strtr($attr['lang'],$lang2dir);
            }
        }

        return $attr;
    }

}
