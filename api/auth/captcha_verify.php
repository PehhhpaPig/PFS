<?php

require_once  dirname(__DIR__,2) . '/securimage/StorageAdapter/AdapterInterface.php';
require_once  dirname(__DIR__,2) . '/securimage/StorageAdapter/Session.php';
require_once  dirname(__DIR__,2) . '/securimage/CaptchaObject.php';
require_once  dirname(__DIR__,2) . '/securimage/securimage.php';
require_once  dirname(__DIR__,1) . '/db.php';

start_secure_session();

header('Content-Type: application/json');

function json_out(array $arr, int $http = 200): never {
    http_response_code($http);
    echo json_encode($arr); exit;
}
$securimage = new Securimage();
$securimage->captchaId = $_SESSION['captchaID'];
// Optionally: use a captchaId if you used `sid=...` in the image
// $securimage->captchaId = $_POST['captcha_sid'] ?? null;

$code = $_POST['captcha_code'] ?? '';

try{
if (!$securimage->check($code, $_SESSION['captchaID'], false)) {
    json_out([
        'status' => 'error',
        'code'=>$code,
        'message' => 'Incorrect CAPTCHA',
        'captchaID'=> $_SESSION['captchaID']
    ], 422);
}


$_SESSION['captcha_required']=false;

echo json_encode([
    'status' => 'ok',
    'message' => 'CAPTCHA verification successful. You may now log in.',
    'nextStep' => 'enableLogin'
]);
}
catch (Exception $e){
    echo 'Message: ' .$e->getMessage();
    echo $_SESSION['captchaID'];
    json_out(['errorID'=>$_SESSION['captchaID']]);
}
/*
$securimage = new Securimage();

// Optionally: use a captchaId if you used `sid=...` in the image
// $securimage->captchaId = $_POST['captcha_sid'] ?? null;

$code = $_POST['captcha_code'] ?? '';

if (!$securimage->check($code)) {
    json_out([
        'status' => 'error',
        'message' => 'Incorrect CAPTCHA'
    ], 422);
}
*/