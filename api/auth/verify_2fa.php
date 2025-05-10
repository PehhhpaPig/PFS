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
/* Verify (±1 × 30 s window) */
if (!Totp::verify($decSecret, $code)) {
    echo json_encode(['error' => 'Invalid 6‑digit code.']); exit;
}

/* Promote session to fully authenticated */
unset($_SESSION['pre_2fa']);
$_SESSION['user_id'] = $uid;
$_SESSION['role']    = $row['role'];
session_regenerate_id(true);

echo json_encode(['ok' => true]);
