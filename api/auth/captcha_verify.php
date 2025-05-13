<?php
require_once  dirname(__DIR__,2) . '/securimage/securimage.php';
require_once dirname(__DIR__,2) . '/securimage/CaptchaObject.php'; 
require_once  dirname(__DIR__,1) . '/db.php';

start_secure_session();

header('Content-Type: application/json');


$stored = $_SESSION['securimage_data'][''] ?? null;

if (empty($_POST['captcha_code'])) {
    json_out(['error'=>'Enter a Captcha Code'], 401);
    exit;
}

if ($stored instanceof \Securimage\CaptchaObject) {
    if(!(($stored->code_display)===$_POST['captcha_code'])){
        json_out(['error'=>'Enter a Captcha Code'], 401);
    }
} else {
    json_out(['error'=>'No valid captcha in session'], 418);
}


// CAPTCHA was correct
$_SESSION['captcha_required']=true;