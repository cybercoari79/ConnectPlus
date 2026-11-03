<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/functions.php';
$u=require_auth();
if(!is_admin($u)) json_response(['error'=>'Acesso restrito ao administrador.'],403);
if(PANEL_UPDATE_MANIFEST_URL==='') json_response(['error'=>'PANEL_UPDATE_MANIFEST_URL não está configurado no .env.'],503);
function fetch_remote_json(string $url): array {
  $raw=false;
  if(function_exists('curl_init')){
    $ch=curl_init($url);
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_FOLLOWLOCATION=>true,CURLOPT_MAXREDIRS=>3,CURLOPT_CONNECTTIMEOUT=>10,CURLOPT_TIMEOUT=>15,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_HTTPHEADER=>['Accept: application/json'],CURLOPT_USERAGENT=>'ConnectPlus-Updater/1']);
    $raw=curl_exec($ch); $code=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE); $err=curl_error($ch); curl_close($ch);
    if($raw===false || $code<200 || $code>=300) throw new RuntimeException('Não foi possível acessar o servidor de atualizações'.($err!==''?': '.$err:'').' (HTTP '.$code.').');
  } else {
    $ctx=stream_context_create(['http'=>['method'=>'GET','timeout'=>15,'header'=>"Accept: application/json\r\nUser-Agent: ConnectPlus-Updater/1\r\n"],'ssl'=>['verify_peer'=>true,'verify_peer_name'=>true]]);
    $raw=@file_get_contents($url,false,$ctx);
    if($raw===false) throw new RuntimeException('Não foi possível acessar o servidor de atualizações. Ative cURL ou allow_url_fopen no PHP.');
  }
  $d=json_decode((string)$raw,true); if(!is_array($d)) throw new RuntimeException('Manifesto de atualização inválido.'); return $d;
}
try{$m=fetch_remote_json(PANEL_UPDATE_MANIFEST_URL);$version=trim((string)($m['version']??''));$url=trim((string)($m['download_url']??''));$sha=strtolower(trim((string)($m['sha256']??'')));if($version===''||$url===''||!preg_match('/^[a-f0-9]{64}$/',$sha))throw new RuntimeException('Manifesto sem versão, download_url ou SHA-256 válido.');if(!preg_match('~^https://~i',$url))throw new RuntimeException('O download da atualização deve usar HTTPS.');json_response(['updateAvailable'=>version_compare(PANEL_VERSION,$version,'<'),'currentVersion'=>PANEL_VERSION,'release'=>['version'=>$version,'download_url'=>$url,'sha256'=>$sha,'changelog'=>(string)($m['changelog']??'')]]);}catch(Throwable $e){json_response(['error'=>$e->getMessage()],502);}
