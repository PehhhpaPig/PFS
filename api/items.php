<?php
require_once __DIR__.'/db.php';
$userId=require_auth();
$role=$_SESSION['role']??'viewer';

switch($_SERVER['REQUEST_METHOD']){
 case 'GET':
   if(isset($_GET['id'])){
       $st=$pdo->prepare('SELECT * FROM items WHERE id=:i');
       $st->execute(['i'=>(int)$_GET['id']]);
       $it=$st->fetch();
       $it?json_response($it):json_response(['error'=>'Not found'],404);
   }
   json_response($pdo->query('SELECT * FROM items ORDER BY id DESC')->fetchAll());

 case 'POST':
   if($role!=='admin') json_response(['error'=>'Forbidden'],403);
   $d=json_decode(file_get_contents('php://input'),true,512,JSON_THROW_ON_ERROR);
   $name=trim($d['name']??'');
   if (strlen($name) > 100 || !preg_match('/^[\p{L}\p{N}\s\.\-\'"]+$/u', $name)) { //sanitise name input
    json_response(['error' => 'Invalid item name'], 422);
  }
   $stock=(int)($d['stock']??0);
   if ($stock < 0 || $stock > 1000000) { //Prevent overflows
    json_response(['error' => 'Invalid stock count'], 422);
}
   $loc=trim($d['location']??'');
   if (strlen($loc) > 100 || !preg_match('/^[\p{L}\p{N}\s\-\(\)\/\.]*$/u', $loc)) { //sanitise location input
    json_response(['error' => 'Invalid location format'], 422);
  }
   $tag=isset($d['rfid_tag'])?strtoupper(trim($d['rfid_tag'])):null;
   if($name===''||$stock<0) json_response(['error'=>'Invalid payload'],422);
   if($tag!==null&&!preg_match('/^[A-F0-9]{8,32}$/',$tag)) json_response(['error'=>'Invalid tag'],422);
   $stmt=$pdo->prepare('INSERT INTO items(name,stock,location,rfid_tag) VALUES(:n,:s,:l,:t)');
   $stmt->execute(['n'=>$name,'s'=>$stock,'l'=>$loc,'t'=>$tag]);
   json_response(['status'=>'created','id'=>$pdo->lastInsertId()],201);

 case 'PUT':
   if($role!=='admin') json_response(['error'=>'Forbidden'],403);
   parse_str(file_get_contents('php://input'),$v);
   $id=(int)($v['id']??0);
   $updates=[];$params=['id'=>$id];
   if(isset($v['stock'])){$updates[]='stock=:stock';$params['stock']=(int)$v['stock'];}
   if(isset($v['rfid_tag'])){
       $tag=strtoupper(trim($v['rfid_tag']));
       if($tag!==''&&!preg_match('/^[A-F0-9]{8,32}$/',$tag)) json_response(['error'=>'Invalid tag'],422);
       $updates[]='rfid_tag=:tag';$params['tag']=$tag!==''?$tag:null;
   }
   if(!$id||!$updates) json_response(['error'=>'Nothing to update'],422);
   $pdo->prepare('UPDATE items SET '.implode(',',$updates).' WHERE id=:id')->execute($params);
   json_response(['status'=>'updated']);
 default: json_response(['error'=>'Method not allowed'],405);
}