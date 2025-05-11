<?php
/** CLI usage: php init_db.php
 *  - creates the database & tables
 *  - (re)creates the application MySQL user and grants it rights
 */

declare(strict_types=1);

$cfg = parse_ini_file(__DIR__ . '/../PFS/config.ini', false, INI_SCANNER_TYPED);
if ($cfg === false) die("No config.ini found
");

$rootUser = $cfg['ROOT_USER'] ?? 'root';
$rootPass = $cfg['ROOT_PASS'] ?? '';
$appUser  = $cfg['DB_USER'];
$appPass  = $cfg['DB_PASS'];
$dbName   = $cfg['DB_NAME'];

$pdo = new PDO(
    sprintf('mysql:host=%s;charset=utf8mb4', $cfg['DB_HOST'] ?? 'localhost'),
    $rootUser, $rootPass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$quotedPass = $pdo->quote($appPass);   // safe SQL literal e.g. 'secret'

// 1. Create database if missing
$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

// 2. Create / alter application user (must embed literal password, cannot bind)
$pdo->exec("CREATE USER IF NOT EXISTS '{$appUser}'@'localhost' IDENTIFIED BY {$quotedPass}");
$pdo->exec("ALTER USER '{$appUser}'@'localhost' IDENTIFIED BY {$quotedPass}");

// 3. Grant privileges limited to this database
$pdo->exec("GRANT ALL PRIVILEGES ON `{$dbName}`.* TO '{$appUser}'@'localhost'");
$pdo->exec('FLUSH PRIVILEGES');

// 4. Switch to the new DB for schema creation
$pdo->exec("USE `{$dbName}`");

// 5. Schema & seed rows
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

-- seed admin user
INSERT IGNORE INTO users (username, pw_hash, role) VALUES (
  'admin',
  '$2y$12$.Kv800e8X62giJzbrJxFv.pNx/pyNaoUowuIbwsBywYHNKbsdH.jK',
  'admin'
);
SQL;

foreach (explode(";
", $sql) as $stmt) {
    $t = trim($stmt);
    if ($t !== '') $pdo->exec($stmt);
}

$pdo->exec("INSERT IGNORE INTO users (username, pw_hash, role) VALUES
  ('viewer', '$2y$12$.Kv800e8X62giJzbrJxFv.pNx/pyNaoUowuIbwsBywYHNKbsdH.jK', 'viewer'),
  ('manager', '$2y$12$.Kv800e8X62giJzbrJxFv.pNx/pyNaoUowuIbwsBywYHNKbsdH.jK', 'admin')");

$pdo->exec("INSERT IGNORE INTO items (name, stock, location, rfid_tag) VALUES
 ('Raspberry Pi 4 Model B', 15, 'Aisle 1, Shelf 2', '1A2B3C4D5E6F7890'),
 ('USB NFC Reader',          7, 'Aisle 1, Shelf 5', '0F1E2D3C4B5A6978'),
 ('Arduino Uno Rev3',       20, 'Aisle 2, Shelf 1', NULL),
 ('Cat6 Patch Cable 1m',   150, 'Aisle 3, Bin 4',  'F0E1D2C3B4A59687'),
 ('Lithium‑Ion 18650',      35, 'HazMat Cabinet',  NULL);");

$pdo->exec("INSERT IGNORE INTO scans (tag_id, user_id, scanned_at) VALUES
  ('1A2B3C4D5E6F7890', (SELECT id FROM users WHERE username='admin'),   NOW() - INTERVAL 2 DAY),
  ('0F1E2D3C4B5A6978', (SELECT id FROM users WHERE username='manager'), NOW() - INTERVAL 1 DAY),
  ('F0E1D2C3B4A59687', (SELECT id FROM users WHERE username='viewer'),  NOW())");
$pdo->exec("ALTER TABLE users
  ADD COLUMN totp_secret CHAR(200)   DEFAULT NULL,   -- base32‑encoded key
  ADD COLUMN totp_enabled TINYINT(1) DEFAULT 0;     -- 0 = off, 1 = active");

$pdo->exec("CREATE TABLE login_throttle (
  username   VARCHAR(64)  NOT NULL,
  ip_addr    VARCHAR(45)  NOT NULL,
  fails      SMALLINT     NOT NULL DEFAULT 0,
  lock_until DATETIME     DEFAULT NULL,
  last_fail  DATETIME     DEFAULT NULL,
  PRIMARY KEY (username, ip_addr)
);
");

$pdo->exec("ALTER TABLE login_throttle
  ADD COLUMN phase ENUM('pwd','2fa') NOT NULL DEFAULT 'pwd',
  DROP   PRIMARY KEY,
  ADD PRIMARY KEY (username, ip_addr, phase);");
  
echo "Schema imported and sample data seeded ✔
";

echo "Database and user initialised ✔
";
