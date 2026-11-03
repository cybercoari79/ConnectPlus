<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/functions.php';
$u=require_auth();
function body_json(): array { $raw=file_get_contents('php://input'); $d=json_decode($raw?:'{}',true); return is_array($d)?$d:[]; }
function api_admin_only(array $u): void { if(!is_admin($u)) json_response(['error'=>'Acesso administrativo necessário'],403); }
function dispatch_api(string $route,array $u): never {
 switch($route){
 case 'profile':
  if($_SERVER['REQUEST_METHOD']==='GET'){ $c=user_config($u); if(empty($u['updateUuid'])) { $u['updateUuid']=uuidv4(); save_user($u); } json_response(['username'=>$u['username'],'email'=>$u['email']??'','created_at'=>$u['createdAt']??$u['created_at']??'','updateUuid'=>$u['updateUuid'],'updateUrl'=>update_resource($u['username'],'config'),'createdAt'=>$u['createdAt']??$u['created_at']??'','plan'=>$u['plan']??'Gratuito','expiresAt'=>$u['expiresAt']??'','active'=>$u['active']??true,'isAdmin'=>is_admin($u),'config'=>$c]); }
  check_csrf_header_optional(); $b=body_json(); if(isset($b['email']))$u['email']=trim((string)$b['email']); if(isset($b['currentPassword'],$b['newPassword'])){if(!password_verify((string)$b['currentPassword'],(string)$u['password']))json_response(['error'=>'Senha atual inválida'],400);if(strlen((string)$b['newPassword'])<6)json_response(['error'=>'Nova senha muito curta'],400);$u['password']=password_hash((string)$b['newPassword'],PASSWORD_DEFAULT);} save_user($u);audit($u,'Atualização','Perfil','Perfil/senha atualizado');json_response(['ok'=>true]);
 case 'regenerate-uuid':
  $u['updateUuid']=uuidv4();save_user($u);audit($u,'Regeneração','UUID','Novo UUID de atualização');json_response(['ok'=>true,'updateUuid'=>$u['updateUuid'],'updateUrl'=>update_resource($u['username'],'config')]);
 case 'save':
  $b=body_json();$raw='';if(isset($b['config_b64'])){$decoded=base64_decode((string)$b['config_b64'],true);if($decoded===false)json_response(['error'=>'Payload de configuração codificado inválido'],400);$raw=$decoded;}else{$raw=(string)($b['config_json']??'');}$incoming=json_decode($raw,true);if(!is_array($incoming))json_response(['error'=>'Formato JSON inválido'],400);$old=user_config($u);$new=normalize_config($incoming);$new=apply_versions($old,$new);save_config($u,$new);$new['Update']=update_resource((string)$u['username'],'config');audit($u,'Salvamento','Configuração','Configuração geral e servidores');json_response(['ok'=>true]+$new);
 case 'sms':
  $b=body_json();$c=user_config($u);$old=$c['Sms'];$next=['Version'=>(string)($b['Version']??$old['Version']),'Update'=>(string)($b['Update']??''),'Notes'=>(string)($b['Notes']??'')];if(stable(['Update'=>$old['Update'],'Notes'=>$old['Notes']])!==stable(['Update'=>$next['Update'],'Notes'=>$next['Notes']]) && (string)$next['Version']===(string)$old['Version'])$next['Version']=(string)((int)$old['Version']+1);$c['Sms']=$next;save_config($u,$c);audit($u,'Salvamento','SMS','Mensagem atualizada');json_response(['ok'=>true,'Sms'=>$next]);
 case 'theme':
  $b=body_json();$c=user_config($u);$old=$c['Theme'];$next=array_merge($old,$b);$next['AppName']=trim((string)($next['AppName']??''));if($next['AppName']==='')$next['AppName']='ConnectPlus';if(stable(array_diff_key($old,['Version'=>1]))!==stable(array_diff_key($next,['Version'=>1])) && (string)($b['Version']??$old['Version'])===(string)$old['Version'])$next['Version']=(string)((int)$old['Version']+1);$c['Theme']=$next;save_config($u,$c);audit($u,'Salvamento','Tema','Tema atualizado');json_response(['ok'=>true,'Theme'=>$next]);
 case 'export':
  $c=user_config($u);header('Content-Type: application/json; charset=utf-8');header('Content-Disposition: attachment; filename="connectplus-config-'.preg_replace('/[^a-zA-Z0-9_.-]/','_',$u['username']).'.json"');echo json_encode($c,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);exit;
 case 'import':
  $b=body_json();$incoming=$b['config_json']??$b;if(is_string($incoming))$incoming=json_decode($incoming,true);if(!is_array($incoming))json_response(['error'=>'JSON inválido'],400);$c=normalize_config($incoming);$old=user_config($u);$c=apply_versions($old,$c);save_config($u,$c);audit($u,'Importação','Configuração','Configuração JSON importada');json_response(['ok'=>true,'config'=>$c,'importSummary'=>['source'=>'json','imported'=>count($c['Servers'])]]);
 case 'cdn-get': json_response(['urls'=>cdn_list($u),'results'=>$u['cdn_pool_results']??[],'active'=>$u['cdn_pool_active']??[],'testedAt'=>$u['cdn_pool_tested_at']??null]);
 case 'cdn-save': $b=body_json();$vals=is_array($b['urls']??null)?$b['urls']:preg_split('/[\n,#]+/',(string)($b['urls']??''));$urls=[];foreach($vals as $v){$x=cdn_normalize($v);if($x)$urls[]=$x;}$urls=array_values(array_unique(array_slice($urls,0,100)));save_cdn($u,$urls);json_response(['urls'=>$urls]);
 case 'cdn-test': $urls=cdn_list($u);$results=[];$active=[];foreach($urls as $url){$ok=false;$code=0;$start=microtime(true);$ch=curl_init($url);curl_setopt_array($ch,[CURLOPT_NOBODY=>true,CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_TIMEOUT=>8,CURLOPT_CONNECTTIMEOUT=>4, CURLOPT_USERAGENT=>'ConnectPlus CDN checker']);curl_exec($ch);$code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$err=curl_errno($ch);curl_close($ch);$ok=$err===0 && $code>=200 && $code<500;$item=['url'=>$url,'ok'=>$ok,'status'=>$code,'ms'=>round((microtime(true)-$start)*1000)];$results[]=$item;if($ok)$active[]=$url;}json_response(['results'=>$results,'active'=>$active,'testedAt'=>date('c')]);
 case 'cdn-delete': $b=body_json();$target=cdn_normalize($b['url']??'');$urls=array_values(array_filter(cdn_list($u),fn($x)=>$x!==$target));save_cdn($u,$urls);json_response(['urls'=>$urls]);
 case 'apk-upload':
  if($_SERVER['REQUEST_METHOD']!=='POST'||empty($_FILES['apk']))json_response(['error'=>'Envie um APK'],400);$f=$_FILES['apk'];if($f['error']!==UPLOAD_ERR_OK)json_response(['error'=>'Falha no upload'],400);$ext=strtolower(pathinfo($f['name'],PATHINFO_EXTENSION));if($ext!=='apk')json_response(['error'=>'Somente .apk'],400);$dir=APK_DIR.'/'.preg_replace('/[^a-zA-Z0-9_.-]/','_',$u['username']);if(!is_dir($dir))mkdir($dir,0775,true);$dest=$dir.'/app.apk';if(!move_uploaded_file($f['tmp_name'],$dest))json_response(['error'=>'Não foi possível salvar o APK'],500);$c=user_config($u);$c['UpdateApk']=base_url().'/uploads/apk/'.rawurlencode($u['username']).'/app.apk';if(isset($_POST['AppVersion']))$c['AppVersion']=(int)$_POST['AppVersion'];if(isset($_POST['VersionName']))$c['VersionName']=(string)$_POST['VersionName'];save_config($u,$c);audit($u,'Upload','APK','Novo APK enviado');json_response(['ok'=>true,'apkUrl'=>$c['UpdateApk'],'UpdateApk'=>$c['UpdateApk'],'AppVersion'=>$c['AppVersion'],'VersionName'=>$c['VersionName']]);
 case 'apk-delete': $f=APK_DIR.'/'.preg_replace('/[^a-zA-Z0-9_.-]/','_',$u['username']).'/app.apk';if(is_file($f))unlink($f);$c=user_config($u);$c['UpdateApk']='';save_config($u,$c);json_response(['ok'=>true]);
 case 'payment-settings':
  api_admin_only($u);
  require_once __DIR__.'/../services/MercadoPago.php';
  if($_SERVER['REQUEST_METHOD']==='GET'){
    $token=trim((string)setting('mpAccessToken',''));
    $prices=setting('planPrices',[]);
    if(!is_array($prices))$prices=[];
    json_response([
      'mpConfigured'=>$token!=='',
      'mpAccessTokenMasked'=>$token!==''?(substr($token,0,4).'••••••'.substr($token,-4)):'',
      'pixKey'=>(string)setting('pixKey',''),
      'planPrices'=>[
        'monthly'=>(float)($prices['monthly']??29.9),
        'quarterly'=>(float)($prices['quarterly']??79.9),
        'yearly'=>(float)($prices['yearly']??249.9)
      ]
    ]);
  }
  $b=body_json();
  $next=[];
  if(!empty($b['clearMpToken']))$next['mpAccessToken']='';
  elseif(array_key_exists('mpAccessToken',$b)&&trim((string)$b['mpAccessToken'])!=='')$next['mpAccessToken']=trim((string)$b['mpAccessToken']);
  if(array_key_exists('pixKey',$b))$next['pixKey']=trim((string)$b['pixKey']);
  if(isset($b['planPrices'])&&is_array($b['planPrices'])){
    $next['planPrices']=[
      'monthly'=>max(0,round((float)($b['planPrices']['monthly']??29.9),2)),
      'quarterly'=>max(0,round((float)($b['planPrices']['quarterly']??79.9),2)),
      'yearly'=>max(0,round((float)($b['planPrices']['yearly']??249.9),2))
    ];
  }
  if($next)save_settings($next);
  $token=trim((string)setting('mpAccessToken',''));
  $prices=setting('planPrices',[]);
  if(!is_array($prices))$prices=[];
  json_response([
    'ok'=>true,
    'mpConfigured'=>$token!=='',
    'mpAccessTokenMasked'=>$token!==''?(substr($token,0,4).'••••••'.substr($token,-4)):'',
    'pixKey'=>(string)setting('pixKey',''),
    'planPrices'=>[
      'monthly'=>(float)($prices['monthly']??29.9),
      'quarterly'=>(float)($prices['quarterly']??79.9),
      'yearly'=>(float)($prices['yearly']??249.9)
    ]
  ]);
 case 'admin-orders': api_admin_only($u);json_response(['orders'=>[]]);
 case 'admin-extend': api_admin_only($u);$b=body_json();$target=get_user((string)($b['username']??''));if(!$target)json_response(['error'=>'Usuário não encontrado'],404);$days=max(1,(int)($b['days']??0));$cur=!empty($target['expiresAt'])?strtotime((string)$target['expiresAt']):time();$base=max($cur,time());$target['expiresAt']=date('c',$base+$days*86400);$target['active']=true;save_user($target);audit($u,'Renovação','Admin','Acesso estendido para '.$target['username']);json_response(['ok'=>true,'username'=>$target['username'],'expiresAt'=>$target['expiresAt']]);
 default: json_response(['error'=>'Endpoint não encontrado'],404);
 }
}
function check_csrf_header_optional(): void { /* Sessão + same-origin; mutações podem usar cookie de sessão. */ }
$route=basename($_SERVER['SCRIPT_NAME']);
$map=['profile.php'=>'profile','uuid-regenerate.php'=>'regenerate-uuid','dashboard-save.php'=>'save','sms.php'=>'sms','theme.php'=>'theme','config-export.php'=>'export','config-import.php'=>'import','cdn.php'=>'cdn-get','cdn-save.php'=>'cdn-save','cdn-test.php'=>'cdn-test','cdn-delete.php'=>'cdn-delete','apk-upload.php'=>'apk-upload','apk-delete.php'=>'apk-delete','payment-settings.php'=>'payment-settings','orders-pending.php'=>'admin-orders','admin-extend.php'=>'admin-extend'];
try {
    dispatch_api($map[$route]??'', $u);
} catch (Throwable $e) {
    error_log('[ConnectPlus] API '.$route.': '.$e->getMessage());
    json_response(['ok'=>false,'error'=>$e->getMessage()],500);
}
