<?php
require_once __DIR__.'/../db.php';
require_once __DIR__.'/../lib/Totp.php';

if ($_SERVER['REQUEST_METHOD']!=='POST') json_response(['error'=>'Method not allowed'],405);
$in=json_decode(file_get_contents('php://input'),true,512,JSON_THROW_ON_ERROR);
$u=trim($in['username']??'');$p=$in['password']??'';
if($u===''||$p==='') json_response(['error'=>'Missing creds'],422);

$stmt=$pdo->prepare('SELECT * FROM users WHERE username=:u');$stmt->execute(['u'=>$u]);
$user=$stmt->fetch();
if(!$user||!hash_equals($user['pw_hash'],hash('sha256',$user['salt'].$p)))
    json_response(['error'=>'Invalid creds'],401);

start_secure_session();
if($user['totp_enabled']){
    $_SESSION['pre_2fa']=$user['id'];
    json_response(['need_2fa'=>true]);
}
// else complete login
session_regenerate_id(true);
$_SESSION['user_id']=$user['id'];
$_SESSION['role']=$user['role'];
json_response(['status'=>'OK']);