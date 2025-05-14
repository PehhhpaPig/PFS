<?php

/** PDO connection + shared helpers (strict types, hardened session). */
declare(strict_types=1);
require_once dirname(__DIR__,1) . '/vendor/autoload.php';
$config = parse_ini_file(__DIR__ . '/../config.ini', false, INI_SCANNER_TYPED);
if ($config === false) {
    http_response_code(500);
    exit('Configuration file missing.');
}

$dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $config['DB_HOST'], $config['DB_NAME']);
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $config['DB_USER'], $config['DB_PASS'], $options);
} catch (PDOException $e) {
    http_response_code(500);
    exit('Database connection failed.');
}

function json_response(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_THROW_ON_ERROR);
    exit;
}

function start_secure_session(): void
{
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

function require_auth(): int
{
    start_secure_session();
    if (empty($_SESSION['user_id'])) {
        json_response(['error' => 'Unauthenticated'], 401);
    }
    return (int) $_SESSION['user_id'];
}
(function () {
    $envFile = __DIR__ . '/../.env';        // root/PFS/.env
    if (!is_readable($envFile)) return;

    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$k, $v] = array_map('trim', explode('=', $line, 2));
        if (!getenv($k)) {                  // don’t override SetEnv / export
            putenv("$k=$v");
            $_ENV[$k] = $v;
            $_SERVER[$k] = $v;
        }
    }
})();
/* ───────────────────────────────────────────────────────────────
   TOTP‑secret “encrypt at rest” helpers  (libsodium secretbox)
   – set a 32‑byte hex key in the environment:  TOTP_KEY=...
   – each secret is encrypted with a fresh 24‑byte nonce
   – stored value = base64(nonce ‖ ciphertext)
   – libsodium (ext/sodium) is bundled with PHP 7.2+
   ─────────────────────────────────────────────────────────────── */
if (!function_exists('totp_encrypt')) {
    /**
     * Encrypt a raw TOTP secret with libsodium and return a safe string
     * suitable for the `totp_secret` column.
     *
     * @param string $secret 16‑32 char Base‑32 secret
     * @return string        base64‑encoded (nonce‖ciphertext)
     */
    function totp_encrypt(string $secret): string
    {
        $keyHex = getenv('TOTP_KEY');
        if ($keyHex === false) {
            throw new RuntimeException('Missing TOTP_KEY env var');
        }
        if (strlen($keyHex) !== 64) {
            throw new RuntimeException('TOTP_KEY env var must be 32‑byte hex');
        }
        $key   = sodium_hex2bin($keyHex);       // 32 bytes binary
        $nonce = random_bytes(24); // 24 bytes
        $cipher = sodium_crypto_secretbox($secret, $nonce, $key);
        return base64_encode($nonce . $cipher);
    }

    /**
     * Decrypt the DB value back to the raw TOTP secret.
     *
     * @param string $b64 base64(nonce‖ciphertext) from DB
     * @return string     original Base‑32 secret
     */
    function totp_decrypt(string $b64): string
    {
        $keyHex = getenv('TOTP_KEY');
        if (strlen($keyHex) !== 64) {
            throw new RuntimeException('TOTP_KEY env var must be 32‑byte hex');
        }
        $key = sodium_hex2bin($keyHex);

        $raw   = base64_decode($b64, true);
        $nonce = substr($raw, 0, 24);
        $cipher= substr($raw, 24);

        $plain = sodium_crypto_secretbox_open($cipher, $nonce, $key);
        if ($plain === false) {
            throw new RuntimeException('TOTP secret decryption failed');
        }
        return $plain;
    }
}

