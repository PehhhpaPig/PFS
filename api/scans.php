<?php
require_once __DIR__ . '/db.php';
// Any authenticated user may view scan history
require_auth();

// --- Optional query parameters ---
//  * limit  (1‑1000, default 100)
//  * tag    exact tag_id match
//  * user   username match
$limit = isset($_GET['limit']) ? max(1, min((int)$_GET['limit'], 1000)) : 100;
$where = [];
$params = [];

if (!empty($_GET['tag'])) {
    $where[]        = 's.tag_id = :tag';
    $params[':tag'] = trim($_GET['tag']);
}
if (!empty($_GET['user'])) {
    $where[]          = 'u.username = :user';
    $params[':user']  = trim($_GET['user']);
}

$sql = 'SELECT s.id, s.tag_id, u.username, s.scanned_at
          FROM scans s JOIN users u ON s.user_id = u.id';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY s.scanned_at DESC LIMIT :lim';

$stmt = $pdo->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
$stmt->execute();

json_response($stmt->fetchAll());