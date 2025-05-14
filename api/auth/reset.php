<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once  dirname(__DIR__,2) . '/securimage/securimage.php';
require_once __DIR__ . '/../lib/Totp.php';
require_once dirname(__DIR__,2) . '/vendor/autoload.php';      // loads bcrypt + totp helpers
start_secure_session();
header('Content-Type: application/json; charset=utf-8');
/* handy JSON responder */
function json_out(array $arr, int $http = 200): never {
    http_response_code($http);
    echo json_encode($arr); exit;
}

/* expire reset session after 10 min */
//if (isset($_SESSION['reset_start']) &&
   // time() - $_SESSION['reset_start'] > 600) {
   // session_unset();
//}

/* ---------- STEP 1 – username ---------- */
if (isset($_POST['username'])) {
    $u = trim($_POST['username']);
    $st = $pdo->prepare('SELECT id FROM users WHERE username = ?');
    $st->execute([$u]);
    if (!$row = $st->fetch()) { json_out(['error'=>'User not found'], 404); }
    $_SESSION['reset_uid']   = (int) $row['id'];
    $uid   = (int) $_SESSION['reset_uid'] ;
    $_SESSION['reset_start'] = time();
    json_out(['ok'=>true]);                           // goto step 2
}

/* ---------- guard: uid must exist ---------- */
if (!isset($_SESSION['reset_uid'])) {
    json_out(['error'=>'Reset not initiated'], 400);
}

/* ---------- STEP 2 – 6‑digit code ---------- */
if (isset($_POST['code']) && !isset($_SESSION['reset_verified'])) {
    $code = preg_replace('/\D/','',$_POST['code']??'');
    if (strlen($code)!==6) out(['error'=>'6 digits required'],422);

    $st=$pdo->prepare('SELECT totp_secret FROM users WHERE id=?');
    $st->execute([$_SESSION['reset_uid']]);
    $secret = totp_decrypt($st->fetchColumn());

    if (!Totp::verify($secret,$code)) { 
        $codeWasValid = /* result of Totp::verify() */ false;   // replace with real check

        if (!$codeWasValid){
            json_out(['error'=>'Invalid 6 digit code', 'captcha_required' => true], 401);
            $_SESSION['captcha_required']=true;
            }
    }
    else{
        $_SESSION['captcha_required']=false;
    }

    $_SESSION['reset_verified'] = true;
    json_out(['ok'=>true]);                           // goto step 3
}

/* ---------- STEP 3 – new password ---------- */
if (isset($_POST['password']) && $_SESSION['reset_verified']===true) {
    $pass = $_POST['password'];
    $pass2 = $_POST['password2'];
    if (strlen($pass)<8) out(['error'=>'Password too short'],422);
    if ($pass!==$pass2) {echo json_encode(['error'=>'Passwords must match.']);exit;}
    $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost'=>12]);
    $pdo->prepare('UPDATE users SET pw_hash=? WHERE id=?')
        ->execute([$hash, $_SESSION['reset_uid']]);

    session_unset(); session_destroy();          // clean slate
    json_out(['ok'=>true]);
}

/* anything else */
json_out(['error'=>'Bad request'],400);
