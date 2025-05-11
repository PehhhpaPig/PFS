<?php

declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/Totp.php';
require_once dirname(__DIR__,2) . '/vendor/autoload.php';      // loads bcrypt + totp helpers
start_secure_session();
header('Content-Type: application/json; charset=utf-8');

/* handy JSON responder */
function out($arr, int $code=200): never {
    http_response_code($code);
    echo json_encode($arr);
    exit;
}

/* expire reset session after 10 min */
if (isset($_SESSION['reset_start']) &&
    time() - $_SESSION['reset_start'] > 600) {
    session_unset();
}

/* ---------- STEP 1 – username ---------- */
if (isset($_POST['username'])) {
    $u = trim($_POST['username']);
    $st = $pdo->prepare('SELECT id FROM users WHERE username = ?');
    $st->execute([$u]);
    if (!$row = $st->fetch()) { out(['error'=>'User not found'], 404); }
    $_SESSION['reset_uid']   = (int) $row['id'];
    $_SESSION['reset_start'] = time();
    out(['ok'=>true]);                           // goto step 2
}

/* ---------- guard: uid must exist ---------- */
if (!isset($_SESSION['reset_uid'])) {
    out(['error'=>'Reset not initiated'], 400);
}

/* ---------- STEP 2 – 6‑digit code ---------- */
if (isset($_POST['code']) && !isset($_SESSION['reset_verified'])) {
    $code = preg_replace('/\D/','',$_POST['code']??'');
    if (strlen($code)!==6) out(['error'=>'6 digits required'],422);

    $st=$pdo->prepare('SELECT totp_secret FROM users WHERE id=?');
    $st->execute([$_SESSION['reset_uid']]);
    $secret = totp_decrypt($st->fetchColumn());

    if (!Totp::verify($secret,$code,3)) { out(['error'=>'Invalid code'],401); }

    $_SESSION['reset_verified'] = true;
    out(['ok'=>true]);                           // goto step 3
}

/* ---------- STEP 3 – new password ---------- */
if (isset($_POST['password']) && $_SESSION['reset_verified']===true) {
    $pass = $_POST['password'];
    if (strlen($pass)<8) out(['error'=>'Password too short'],422);

    $hash = password_hash($pass, PASSWORD_BCRYPT, ['cost'=>12]);
    $pdo->prepare('UPDATE users SET pw_hash=? WHERE id=?')
        ->execute([$hash, $_SESSION['reset_uid']]);

    session_unset(); session_destroy();          // clean slate
    out(['ok'=>true]);
}

/* anything else */
out(['error'=>'Bad request'],400);
