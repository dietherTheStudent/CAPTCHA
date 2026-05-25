<?php
session_start();

$code = substr(str_shuffle("ABCDEFGHJKLMNPQRSTUVWXYZ23456789"), 0, 6);
$_SESSION['captcha'] = $code;

$image = imagecreate(150, 50);

$bg = imagecolorallocate($image, 255, 255, 255);
$textcolor = imagecolorallocate($image, 0, 0, 0);

imagestring($image, 5, 40, 15, $code, $textcolor);

header("Content-type: image/png");
imagepng($image);
imagedestroy($image);
?>