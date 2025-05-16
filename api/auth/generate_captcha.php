<?php
declare(strict_types=1);

require_once __DIR__ . '/../db.php';     // loads PDO + start_secure_session()
require_once  dirname(__DIR__,2) . '/securimage/securimage.php';
start_secure_session();
header('Content-Type: application/json; charset=utf-8');


$Id = $_SESSION['captchaID'];
/* ---------------- helper ---------------- */
function json_out(array $arr, int $http = 200): never {
    http_response_code($http);
    echo json_encode($arr); exit;
}

$raw = $_POST ?: json_decode(file_get_contents('php://input'), true) ?: [];
$captcha_required = trim($raw['generate_captcha'] ?? '');
    $img = new Securimage();
    $img->captchaId = $_SESSION['captchaID'];
    $img2 = $img->getCaptchaHtml();
    $code = $_SESSION['captchaID']; // Replace with your desired value

$saved = null;

$updatedHtml = preg_replace_callback(
    '/(?<=id=|value=)([\'"])([a-f0-9]{40})\\1/',
    function ($matches) use ($code) {
        $_SESSION['saved']=$matches[2];
        return $matches[1] . $code . $matches[1];
    },
    $img2
);
$offerings = 1;
$html = str_replace(
  'onclick="securimageRefreshCaptcha(\'captcha_image\', \'captcha_image_audioObj\');"',
  'onclick="generateCaptcha('.$offerings.$offerings.'); return false"',
  $img2
);

$_SESSION['captchaID'] = $_SESSION['saved'];
    json_out(['godisdeadandthiscodekilledhim'=>$html, 'captchaID'=>$_SESSION['captchaID']], 200);
