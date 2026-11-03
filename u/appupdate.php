<?php require_once __DIR__.'/../includes/functions.php';
$uuid=(string)($_GET['uuid']??'');
$user=null;
foreach(list_users() as $x){if(strtolower((string)($x['updateUuid']??''))===strtolower($uuid)){$user=$x;break;}}
if(!$user)json_response(['error'=>'UUID não encontrado'],404);
$c=user_config($user);
json_response(['Version'=>(string)$c['AppVersion'],'Update'=>(string)($c['UpdateApk']??''),'VersionName'=>(string)($c['VersionName']??$c['AppVersion']),'UpdateApk'=>(string)($c['UpdateApk']??''),'Actualization'=>(string)($c['Actualization']??'false')]);
