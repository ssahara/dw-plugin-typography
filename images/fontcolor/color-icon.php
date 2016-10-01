<?php
error_reporting(0);
if (isset($_SERVER['HTTP_REFERER'])) {
    $isSameHost = strcasecmp($_SERVER['HTTP_HOST'], parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST)) === 0;
} else {
    // assume same host if HTTP_REFERER is not available.
    $isSameHost = True;
}
$isColorSet = array_key_exists('color', $_GET);
if ($isSameHost && $isColorSet) {

    list($red, $green, $blue) = str_split($_GET['color'], 2);

    $img = @imagecreatetruecolor(16, 16) or die("Cannot Initialize new GD image stream");
    imagefill($img, 0, 0, imagecolorallocate($img, hexdec($red), hexdec($green), hexdec($blue)));
    header('Content-type: image/png');
    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 60*60*24) . ' GMT');
    imagepng($img);
    imagedestroy($img);
}
