<?php
require_once  dirname(__DIR__,1) . '/securimage/securimage.php';
require_once dirname(__DIR__,1) . '/securimage/CaptchaObject.php'; 
require_once  dirname(__DIR__,1) . '/api/db.php';

start_secure_session();

print_r($_SESSION);

$stored = $_SESSION['securimage_data'][''] ?? null;

if ($stored instanceof \Securimage\CaptchaObject) {
    echo "Expected CAPTCHA code: " . $stored->code_display; // or $stored->code (case-insensitive)
} else {
    echo "No valid CAPTCHA stored in session.";
}