<?php
declare(strict_types=1);
require_once __DIR__.'/../db.php';
require_once __DIR__.'/../lib/Totp.php';
require_once dirname(__DIR__,2) . '/vendor/autoload.php';


start_secure_session();
header('Content-Type: application/json; charset=utf-8');

/* STEP 2 – verify first TOTP code */
if(isset($_POST['code'],$_SESSION['pending_uid'],$_SESSION['pending_secret'])){
    if(!Totp::verify($_SESSION['pending_secret'],trim($_POST['code']))){
        echo json_encode(['error'=>'Invalid 6‑digit code.']); exit;
    }
    $pdo->prepare('UPDATE users SET totp_enabled=1 WHERE id=?')
        ->execute([$_SESSION['pending_uid']]);
    unset($_SESSION['pending_uid'],$_SESSION['pending_secret']);
    echo json_encode(['ok'=>true]); exit;
}

/* STEP 1 – create user, return otpauth URI */
$u=trim($_POST['username']??''); $p=$_POST['password']??''; $c=$_POST['confirm']??'';
if($u==''||strlen($p)<8||$p!==$c){echo json_encode(['error'=>'Invalid input.']);exit;}
$ex=$pdo->prepare('SELECT 1 FROM users WHERE username=?');$ex->execute([$u]);
if($ex->fetch()){echo json_encode(['error'=>'Username taken.']);exit;}

$hash=password_hash($p, PASSWORD_BCRYPT, ['cost' => 12]);
$secret = Totp::generateRandomSecret();  
$encSecret = totp_encrypt($secret);
$pdo->prepare('INSERT INTO users(username,pw_hash,role,totp_secret,totp_enabled)
               VALUES(?,?,?,?,0)')
    ->execute([$u,$hash,'viewer',$encSecret]);
$_SESSION['pending_uid']=$pdo->lastInsertId();
$_SESSION['pending_secret']=$secret;

$uri=Totp::getUri($secret,$u,'NuTracker');
echo json_encode(['uri'=>$uri]);
