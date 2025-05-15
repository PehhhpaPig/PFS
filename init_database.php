#!/usr/bin/env php
<?php
declare(strict_types=1);

// 1) Load composer + .env
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// 2) Read env vars
$rootUser = $_ENV['DB_ROOT_USER']    ?? 'root';
$rootPass = $_ENV['DB_ROOT_PASS']    ?? '';
$host     = $_ENV['DB_HOST']         ?? '127.0.0.1';
$port     = $_ENV['DB_PORT']         ?? '3306';
$socket   = $_ENV['DB_SOCKET']       ?? '';
$dbName   = $_ENV['DB_NAME']         ?? 'NuTracker_PFS';
$appUser  = $_ENV['DB_APP_USER']     ?? 'demo_user';
$appPass  = $_ENV['DB_APP_PASS']     ?? 'demo_pass';

$host   = $_ENV['DB_HOST']   ?? '127.0.0.1';
$port   = $_ENV['DB_PORT']   ?? '3306';
$socket = $_ENV['DB_SOCKET'] ?? '';

if ($socket !== '' && file_exists($socket)) {
    // use the Unix socket if it actually exists
    $dsnBase = "mysql:unix_socket={$socket};charset=utf8mb4";
} else {
    // otherwise connect over TCP
    $dsnBase = "mysql:host={$host};port={$port};charset=utf8mb4";
}

// connect without dbname first (to create the DB)
$pdo = new PDO($dsnBase, $rootUser, $rootPass, $options);

// then reconnect with the dbname to build schema
$dsnDb = $dsnBase . ";dbname={$dbName}";
$pdo = new PDO($dsnDb, $rootUser, $rootPass, $options);
// 5) Create database
$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` "
         . "CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

// 6) Reconnect **with** dbname for schema
if ($socket !== '') {
    $dsnDb = "mysql:unix_socket={$socket};dbname={$dbName};charset=utf8mb4";
} else {
    $dsnDb = "mysql:host={$host};port={$port};dbname={$dbName};charset=utf8mb4";
}
$pdo = new PDO($dsnDb, $rootUser, $rootPass, $options);

// 7) Create / update application user
$quoted = $pdo->quote($appPass);
$pdo->exec("CREATE USER IF NOT EXISTS '{$appUser}'@'localhost' IDENTIFIED BY {$quoted}");
$pdo->exec("ALTER USER       '{$appUser}'@'localhost' IDENTIFIED BY {$quoted}");
$pdo->exec("GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$appUser}'@'localhost'");
$pdo->exec("FLUSH PRIVILEGES");

// 8) Schema + seed (exactly as before) :contentReference[oaicite:0]{index=0}:contentReference[oaicite:1]{index=1}
$sql = <<<'SQL'
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE NOT NULL,
  pw_hash CHAR(60) NOT NULL,
  role ENUM('viewer','admin') DEFAULT 'viewer',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  stock INT UNSIGNED NOT NULL DEFAULT 0,
  location VARCHAR(100),
  rfid_tag CHAR(32) UNIQUE DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS scans (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  tag_id VARCHAR(32) NOT NULL,
  user_id INT NOT NULL,
  scanned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_scan_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

ALTER TABLE users
  ADD COLUMN totp_secret CHAR(200)   DEFAULT NULL,
  ADD COLUMN totp_enabled TINYINT(1) DEFAULT 0;

CREATE TABLE IF NOT EXISTS login_throttle (
  username   VARCHAR(64)  NOT NULL,
  ip_addr    VARCHAR(45)  NOT NULL,
  phase      ENUM('pwd','2fa') NOT NULL DEFAULT 'pwd',
  fails      SMALLINT     NOT NULL DEFAULT 0,
  lock_until DATETIME     DEFAULT NULL,
  last_fail  DATETIME     DEFAULT NULL,
  PRIMARY KEY (username, ip_addr, phase)
);
SQL;

foreach (explode(";\n", $sql) as $stmt) {
    $stmt = trim($stmt);
    if ($stmt !== '') {
        $pdo->exec($stmt);
    }
}

// 9) Seed default data :contentReference[oaicite:2]{index=2}:contentReference[oaicite:3]{index=3}
$pdo->exec("INSERT IGNORE INTO users (username, pw_hash, role) VALUES
  ('admin',   '$2y\$12\$.Kv800e8X62giJzbrJxFv.pNx/pyNaoUowuIbwsBywYHNKbsdH.jK', 'admin'),
  ('viewer',  '$2y\$12\$.Kv800e8X62giJzbrJxFv.pNx/pyNaoUowuIbwsBywYHNKbsdH.jK', 'viewer'),
  ('manager', '$2y\$12\$.Kv800e8X62giJzbrJxFv.pNx/pyNaoUowuIbwsBywYHNKbsdH.jK', 'admin')
");
$pdo->exec("INSERT IGNORE INTO items (name, stock, location, rfid_tag) VALUES
 ('Raspberry Pi 4 Model B', 15, 'Aisle 1, Shelf 2', '1A2B3C4D5E6F7890'),
 ('USB NFC Reader',          7,  'Aisle 1, Shelf 5', '0F1E2D3C4B5A6978'),
 ('Arduino Uno Rev3',       20, 'Aisle 2, Shelf 1', NULL),
 ('Cat6 Patch Cable 1m',   150, 'Aisle 3, Bin 4',  'F0E1D2C3B4A59687'),
 ('Lithium-Ion 18650',      35, 'HazMat Cabinet',   NULL)
");
$pdo->exec("INSERT IGNORE INTO scans (tag_id, user_id, scanned_at) VALUES
  ('1A2B3C4D5E6F7890', (SELECT id FROM users WHERE username='admin'),   NOW() - INTERVAL 2 DAY),
  ('0F1E2D3C4B5A6978', (SELECT id FROM users WHERE username='manager'), NOW() - INTERVAL 1 DAY),
  ('F0E1D2C3B4A59687', (SELECT id FROM users WHERE username='viewer'),  NOW())
");

echo "✅ Database initialized and sample data seeded.\n";
