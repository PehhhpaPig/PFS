<?php
declare(strict_types=1);

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../lib/Totp.php';
require_once dirname(__DIR__,2) . '/vendor/autoload.php';
start_secure_session();
header('Content-Type: application/json; charset=utf-8');

/* Make sure the first factor succeeded */
if (empty($_SESSION['pre_2fa'])) {
    echo json_encode(['error' => 'No 2‑factor challenge pending.']);  // 400
    exit;
}

$uid   = (int) $_SESSION['pre_2fa'];
$raw   = null;

/* ─── 1 ‑ FormData / application‑x‑www‑form‑urlencoded ─── */
if (isset($_POST['code'])) {
    $raw = $_POST['code'];
}

/* ─── 2 ‑ Raw JSON fallback ─── */
if ($raw === null) {
    $json = json_decode(file_get_contents('php://input'), true);
    if (is_array($json) && isset($json['code'])) {
        $raw = $json['code'];
    }
}

/* At this point $raw may still be null (missing param) */
$code = preg_replace('/\D/', '', $raw ?? '');   // keep digits only

if (strlen($code) !== 6) {
    echo json_encode(['error' => 'Code must be 6 digits.']);  // 422
    exit;
}

/* Fetch secret + role */
$st = $pdo->prepare('SELECT totp_secret, role FROM users WHERE id = ?');
$st->execute([$uid]);
$row = $st->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['error' => 'User not found.']); exit;
}
$decSecret = totp_decrypt($row['totp_secret']);
/* ==============================================================
   2‑factor brute‑force throttle  (phase = '2fa')
   ============================================================== */

   function json_out(array $arr, int $code = 200): never
   {
       http_response_code($code);
       header('Content-Type: application/json; charset=utf-8');
       echo json_encode($arr);
       exit;
   }
   
   $ip   = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
   $uid  = (int) $_SESSION['pre_2fa'];
   $now  = date('Y-m-d H:i:s');
   
   /* ---------- load current record ---------- */
   $sth = $pdo->prepare(
       'SELECT fails, lock_until, last_fail
          FROM login_throttle
         WHERE username = ? AND ip_addr = ? AND phase = "2fa"'
   );
   $sth->execute([$uid, $ip]);
   $rec = $sth->fetch(PDO::FETCH_ASSOC) ?: ['fails'=>0,'lock_until'=>null,'last_fail'=>null];
   
   /* ---------- still locked? ---------- */
   if ($rec['lock_until'] && $now < $rec['lock_until']) {
       json_out(['error'=>'Too many codes. Wait and try again.'], 429);
   }
/* Verify (±1 × 30 s window) */
if (!Totp::verify($decSecret, $code)) {
    $codeWasValid = /* result of Totp::verify() */ false;   // replace with real check

if (!$codeWasValid)
{
    /* step‑1: bump fail counter */
    $fails = $rec['fails'] + 1;

    /* reset window if last fail older than 15 min */
    if ($rec['last_fail'] && strtotime($rec['last_fail']) < time() - 900) {
        $fails = 1;
    }

    /* step‑2: compute lock duration every 3rd fail */
    $lock = null;
    if ($fails % 3 === 0) {
        $n    = ($fails / 3) - 1;        // 0,1,2,3…
        $secs = 15 * (2 ** $n);          // 15,30,60,120…
        $secs = min($secs, 900);         // cap at 15 min
        $lock = date('Y-m-d H:i:s', time() + $secs);
    }

    /* step‑3: upsert row */
    $pdo->prepare(
        'REPLACE INTO login_throttle
         (username, ip_addr, phase, fails, lock_until, last_fail)
         VALUES (?, ?, "2fa", ?, ?, ?)'
    )->execute([$uid, $ip, $fails, $lock, $now]);

    json_out(['error'=>'Invalid 6‑digit code.'], 401);
}
}
else{
    $pdo->prepare(
        'DELETE FROM login_throttle
         WHERE username = ? AND ip_addr = ? AND phase = "2fa"'
    )->execute([$uid, $ip]);
}
/* Promote session to fully authenticated */
unset($_SESSION['pre_2fa']);
$_SESSION['user_id'] = $uid;
$_SESSION['role']    = $row['role'];
session_regenerate_id(true);

echo json_encode(['ok' => true]);
