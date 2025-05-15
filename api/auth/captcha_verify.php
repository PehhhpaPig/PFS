<?php
require_once  dirname(__DIR__,2) . '/securimage/securimage.php';
require_once dirname(__DIR__,2) . '/securimage/CaptchaObject.php'; 
require_once  dirname(__DIR__,1) . '/db.php';

start_secure_session();

header('Content-Type: application/json');


$stored = $_SESSION['securimage_data'][''] ?? null;

if (empty($_POST['captcha_code'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please enter the CAPTCHA code.'
    ]);
    exit;
}

if ($stored instanceof \Securimage\CaptchaObject) {
    if(!(($stored->code_display)===$_POST['captcha_code'])){
            echo json_encode([
        'status' => 'error',
        'message' => 'Incorrect CAPTCHA. Please try again.',
        'reload' => true,
        'hint' => 'Check if letters are uppercase or lowercase.'
    ]);
    exit;
    }
} else {
    json_out(['error'=>'No valid captcha in session'], 418);
}

$_SESSION['captcha_required']=false;

echo json_encode([
    'status' => 'ok',
    'message' => 'CAPTCHA verification successful. You may now log in.',
    'nextStep' => 'enableLogin'
]);