<?php
 /**
  * Color icon generator
  */
error_reporting(0);
if (isset($_SERVER['HTTP_REFERER'])) {
    // eliminate external use
    $isSameHost = strcasecmp($_SERVER['HTTP_HOST'], parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST)) === 0;
} else {
    // assume same host if HTTP_REFERER is not available.
    $isSameHost = True;
}
$isColorSet = array_key_exists('color', $_GET);

if ($isSameHost && $isColorSet) {

    if (function_exists('imagecreatetruecolor')) {
        list($red, $green, $blue) = str_split($_GET['color'], 2);
        $img = imagecreatetruecolor(16, 16);
        imagefill($img, 0, 0, imagecolorallocate($img, hexdec($red), hexdec($green), hexdec($blue)));
        header('Content-type: image/png');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 60*60*24) . ' GMT');
        imagepng($img);
        imagedestroy($img);
    } else {
        $icon = strtolower($_GET['color']) . '.png';
        $img = file_get_contents( dirname(__FILE__). '/'. $icon);
        if ($img === false) die("Icon file not found");
        header('Content-type: image/png');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 60*60*24) . ' GMT');
        echo $img;
    }
}
