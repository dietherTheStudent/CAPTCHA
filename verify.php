<?php
session_start();

$mode = $_POST['image_captcha_passed'] ?? '0';

if ($mode === '1') {
    echo "CAPTCHA Correct (Image)";
} else {
    $userCaptcha = $_POST['captcha'] ?? '';
    if ($userCaptcha == $_SESSION['captcha']) {
        echo "CAPTCHA Correct (Text)";
    } else {
        echo "CAPTCHA Incorrect";
    }
}
?>