<?php
require_once __DIR__ . '/db.php';
$userId = require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(['error'=>'Method not allowed'],405);

$in = json_decode(file_get_contents('php://input'),true,512,JSON_THROW_ON_ERROR);
$tag = strtoupper(trim($in['tagId'] ?? ''));
if(!preg_match('/^[A-F0-9]{8,32}$/',$tag)) json_response(['error'=>'Invalid tag'],422);

// Record the scan regardless of item match
$pdo->prepare('INSERT INTO scans(tag_id,user_id,scanned_at) VALUES(:t,:u,NOW())')
    ->execute(['t'=>$tag,'u'=>$userId]);

$itemStmt = $pdo->prepare('SELECT * FROM items WHERE rfid_tag = :t LIMIT 1');
$itemStmt->execute(['t'=>$tag]);
$item = $itemStmt->fetch();

json_response(['status'=>'OK','item'=>$item]);