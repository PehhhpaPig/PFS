<?php
require_once dirname(__DIR__,2) . '/vendor/autoload.php';
require_once __DIR__.'/../db.php';
require_once __DIR__.'/../lib/Totp.php';
$userId=require_auth();

if($_SERVER['REQUEST_METHOD']==='GET'){
    $secret=Totp::generateRandomSecret();
    $_SESSION['setup_secret']=$secret;
    json_response(['secret'=>$secret,'uri'=>Totp::getUri($secret,'user'.$userId,'NuTracker')]);
}
if($_SERVER['REQUEST_METHOD']==='POST'){
   $sec=$_SESSION['setup_secret']??null;
   if(!$sec) json_response(['error'=>'Session expired'],400);
   $encSecret = totp_encrypt($sec);
   $code=trim($_POST['code']??'');
   if(!Totp::verify($sec,$code)) json_response(['error'=>'Invalid code'],401);
   //$secEncrypted = 
   $pdo->prepare('UPDATE users SET totp_secret=:s, totp_enabled=1 WHERE id=:id')
       ->execute(['s'=>$encSecret,'id'=>$userId]);
   unset($_SESSION['setup_secret']);
   json_response(['status'=>'2fa_enabled']);
}
json_response(['error'=>'Method'],405);