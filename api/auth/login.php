<?php
declare(strict_types=1);

/*
 |------------------------------------------------------------------
 |  /api/auth/login.php
 |------------------------------------------------------------------
 |  POST  username + password         (form‑data OR JSON)
 |  → { need_2fa:true }               first step OK, ask for code
 |  → { status:"OK", role:"admin" }   logged in (no 2FA)
 |  → { error:"msg" } HTTP 401/429    errors
 */

require_once __DIR__ . '/../db.php';     // loads PDO + start_secure_session()
require_once  dirname(__DIR__,2) . '/securimage/securimage.php';
start_secure_session();
header('Content-Type: application/json; charset=utf-8');

/* ---------------- helper ---------------- */
function json_out(array $arr, int $http = 200): never {
    http_response_code($http);
    echo json_encode($arr); exit;
}

/* ---------------- read input ---------------- */
$raw = $_POST ?: json_decode(file_get_contents('php://input'), true) ?: [];
$user = trim($raw['username'] ?? '');
$pass =        $raw['password'] ?? '';
if ($user === '' || $pass === '') json_out(['error'=>'Missing fields'], 422);

/* ---------------- fetch user ---------------- */
$stm = $pdo->prepare('SELECT id, pw_hash, role, totp_enabled FROM users WHERE username=?');
$stm->execute([$user]);
$usr = $stm->fetch(PDO::FETCH_ASSOC);
if (!$usr){
    json_out(['error'=>'Invalid username or password'], 401);
}
/* ---------------- throttle look‑up ---------------- */
$ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$now = date('Y-m-d H:i:s');

$thr = $pdo->prepare(
    'SELECT fails, lock_until, last_fail
       FROM login_throttle
      WHERE username=? AND ip_addr=?');
$thr->execute([$user, $ip]);
$rec = $thr->fetch(PDO::FETCH_ASSOC) ?: ['fails'=>0,'lock_until'=>null,'last_fail'=>null];

/* still locked? */
if ($rec['lock_until'] && $now < $rec['lock_until']) {
    json_out(['error'=>'Too many attempts. Try again later.'], 429);
}
if ($_SESSION['captcha_required'] == true){
    json_out(['error'=>'Captcha Required!'], 418);
}

/* ---------------- password check ---------------- */
if (!password_verify($pass, $usr['pw_hash'])) {

    /* update failure counters */
    $fails = $rec['fails'] + 1;
    $_SESSION['login_failures'] = ($_SESSION['login_failures'] ?? 0) + 1;

    /* reset 15‑min window */
    if ($rec['last_fail'] && strtotime($rec['last_fail']) < time() - 900) {
        $fails = 1;
    }

    /* lock every 5th fail : 30s, 60s, 120s, 240s … max 1h */
    $lock = null;
    if ($fails % 5 === 0) {
        $exp  = ($fails / 5) - 1;               // 0,1,2,3…
        $secs = 30 * (2 ** $exp);
        $secs = min($secs, 3600);
        $lock = date('Y-m-d H:i:s', time() + $secs);
    }

    $pdo->prepare(
        'REPLACE INTO login_throttle
         (username, ip_addr, fails, lock_until, last_fail)
         VALUES (?,?,?,?,?)')
        ->execute([$user, $ip, $fails, $lock, $now]);

    json_out(['error'=>'Invalid username or password', 'captcha_required' => $_SESSION['login_failures'] >= 1], 401);
    $_SESSION['captcha_required']=true;
}

/* ---------------- success: clear throttle ---------------- */
$pdo->prepare(
    'DELETE FROM login_throttle WHERE username=? AND ip_addr=?')
    ->execute([$user, $ip]);
//Clear login failures
$_SESSION['login_failures'] = 0;
/* ---------------- 2‑factor step ---------------- */
if ((int)$usr['totp_enabled'] === 1) {
    $_SESSION['pre_2fa'] = $usr['id'];          // will be promoted in verify_2fa.php
    json_out(['need_2fa'=>true]);
}

/* ---------------- establish full session ---------------- */
$_SESSION['user_id'] = $usr['id'];
$_SESSION['role']    = $usr['role'];
session_regenerate_id(true);

json_out(['status'=>'OK', 'role'=>$usr['role']]);
