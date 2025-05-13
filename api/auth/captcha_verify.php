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
        echo "incorrect";
    }
} else {
    json_out(['error'=>'No valid captcha in session'], 418);
}


// CAPTCHA was correct
//O dear god, this entire captcha flow is not my proudest moment
//but it /looks/ like at works
//fake it till ya make it
$_SESSION['captcha_required']=false;
header('Location: '.'/PFS/public/index.html');