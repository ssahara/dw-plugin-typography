<?php
/**
 * DokuWiki Plugin Typography; Action component
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Satoshi Sahara <sahara.satoshi@gmail.com>
 */

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'action.php');

class action_plugin_typography extends DokuWiki_Action_Plugin {

    /**
     * register the event handlers
     */
    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, 'deleteObsoletedSingleClass');
        if (plugin_isdisabled('fontcolor')) {
            $controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'fontColorToolbar', array());
        }
        if (plugin_isdisabled('fontsize2')) {
            $controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'fontSizeToolbar', array());
        }
        if (plugin_isdisabled('fontfamily')) {
            $controller->register_hook('TOOLBAR_DEFINE', 'AFTER', $this, 'fontFamilyToolbar', array());
        }
    }

    /**
     * Delete syntax.php which is obsoleted since multi-components syntax structure
     */
    public function deleteObsoletedSingleClass(Doku_Event &$event) {
        $legacyFile = dirname(__FILE__).'/syntax.php';
        if (file_exists($legacyFile)) { unlink($legacyFile); }
    }


    /**
     * Adds FontColor toolbar button
     * @see https://www.dokuwiki.org/plugin:fontcolor
     */
    public function fontColorToolbar(Doku_Event $event, $param) {
        $palettePath = array(
            'user'    => DOKU_PLUGIN.$this->getPluginName().'/palette.user.conf',
            'default' => DOKU_PLUGIN.$this->getPluginName().'/palette.conf',
            'local'   => DOKU_PLUGIN.$this->getPluginName().'/palette.local.conf',
        );
        $colors = array();
        foreach ($palettePath as $type => $file) {
            if ($type == 'user') {
                $colors = $this->_loadConfig($file);
                if ($colors) break;
            } else {
                $colors = array_merge($colors, $this->_loadConfig($file));
            }
        }
        if (empty($colors)) {
            $colors = array( // Basic 16 colors
              'black'   => '#000000',
              'gray'    => '#808080',
              'silver'  => '#c0c0c0',
              'white'   => '#ffffff',
              'blue'    => '#0000ff',
              'navy'    => '#000080',
              'teal'    => '#008080',
              'green'   => '#008000',
              'lime'    => '#00ff00',
              'aqua'    => '#00ffff',
              'yellow'  => '#ffff00',
              'red'     => '#ff0000',
              'fuchsia' => '#ff00ff',
              'olive'   => '#808000',
              'purple'  => '#800080',
              'maroon'  => '#800000',
            );
        }
        $button = array(
                'type'  => 'picker',
                'title' => $this->getLang('fc_picker'),
                'icon'  => DOKU_BASE.'lib/plugins/typography/images/fontcolor/picker.png',
                'list'  => array()
        );
        foreach ($colors as $colorName => $colorValue) {
            $button['list'][] = array(
                'type'  => 'format',
                'title' => $colorName,
                'icon'  => DOKU_BASE.'lib/plugins/typography/images/fontcolor/color-icon.php?color='
                           .substr($colorValue, 1),
                'open'  => '<fc ' . $colorValue . '>',
                'close' => '</fc>'
            );
        }
        $event->data[] = $button;
    }

    /**
     * Adds FontFamily toolbar button
     * @see https://www.dokuwiki.org/plugin:fontcfamily
     */
    public function fontFamilyToolbar(Doku_Event $event, $param) {
        $options = array(
            'serif'       => 'serif',
            'sans-serif'  => 'sans-serif',
            //'cursive'     => 'cursive',
            //'fantasy'     => 'fantasy',
        );
        $button = array(
                'type' => 'picker',
                'title' => $this->getLang('ff_picker'),
                'icon' => DOKU_BASE.'lib/plugins/typography/images/fontfamily/picker.png',
                'list' => array()
        );
        foreach ($options as $familyName => $familyValue) {
            $button['list'][] = array(
                'type'  => 'format',
                'title'  => $this->getLang('ff_'.$familyName),
                'sample' => $this->getLang('ff_'.$familyName.'_sample'),
                'icon'   => DOKU_BASE.'lib/plugins/typography/images/fontfamily/'.$familyName.'.png',
                'open'   => '<ff '.$familyValue.'>',
                'close'  => '</ff>',
            );
        }
        $event->data[] = $button;
    }

    /**
     * Adds FontSize toolbar button
     * @see https://www.dokuwiki.org/plugin:fontsize2
     */
    public function fontSizeToolbar(Doku_Event $event, $param) {
        $options = array(
            'xxs'     => 'xx-small',
            'xs'      =>  'x-small',
            's'       =>    'small',
            'm'       =>   'medium',
            'l'       =>    'large',
            'xl'      =>  'x-large',
            'xxl'     => 'xx-large',
            'smaller' =>  'smaller',
            'larger'  =>   'larger'
        );
        $button = array(
                'type' => 'picker',
                'title' => $this->getLang('fs_picker'),
                'icon' => '../../plugins/typography/images/fontsize/picker.png',
                'list' => array()
        );
        foreach ($options as $sizeName => $sizeValue) {
            $button['list'][] = array(
                'type'  => 'format',
                'title'  => $this->getLang('fs_'.$sizeName),
                'sample' => $this->getLang('fs_'.$sizeName.'_sample'),
                'icon'   => DOKU_BASE.'lib/plugins/typography/images/fontsize/'.$sizeName.'.png',
                'open'   => '<fs '.$sizeValue.'>',
                'close'  => '</fs>',
            );
        }
        $event->data[] = $button;
    }

    /**
     * read two colums type config file (data, description)
     */
    protected function _loadConfig($file) {
        $conf = array();
        if (!file_exists($file)) return $conf;

        $lines = @file($file);
        if (!$lines) return false;
        foreach ($lines as $line) {
            $line = preg_replace('@(^//|\s//).*$@','', $line); // one-line comment
            $line = trim($line);
            if (empty($line)) continue;
            $token = preg_split('/(?!,)\s+/', $line, 2);
            $conf[trim($token[1])] = trim($token[0]);
        }
        return $conf;
    }
}
