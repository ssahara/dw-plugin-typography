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
        // render PNG image using PHP GD library
        list($red, $green, $blue) = str_split($_GET['color'], 2);
        $img = imagecreatetruecolor(16, 16);
        imagefill($img, 0, 0, imagecolorallocate($img, hexdec($red), hexdec($green), hexdec($blue)));
        header('Content-type: image/png');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 60*60*24) . ' GMT');
        imagepng($img);
        imagedestroy($img);
    } else {
        // render SVG image
        $color = '#'.strtolower($_GET['color']);
        header('Content-type: image/svg+xml');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 60*60*24) . ' GMT');
        echo '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16">';
        echo '<rect width="100%" height="100%" fill="'.$color.'"/>';
        echo '</svg>';
    }
}
