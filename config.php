<?php
declare(strict_types=1);

// Carrega .env local sem depender de biblioteca externa.
$envFile=__DIR__.'/.env';
if(is_file($envFile) && is_readable($envFile)){
    foreach(file($envFile, FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES) ?: [] as $line){
        $line=trim($line);
        if($line==='' || $line[0]==='#' || !str_contains($line,'=')) continue;
        [$k,$v]=explode('=',$line,2);
        $k=trim($k); $v=trim($v);
        if($v!=='' && (($v[0]??'')==='"' || ($v[0]??'')==="'")) $v=trim($v, "\"'");
        if(getenv($k)===false) putenv($k.'='.$v);
    }
}

define('APP_NAME','ConnectPlus');
define('PANEL_VERSION', (string)(getenv('PANEL_VERSION') ?: '1.0.0'));
define('PANEL_UPDATE_MANIFEST_URL', rtrim((string)(getenv('PANEL_UPDATE_MANIFEST_URL') ?: ''),'/'));
define('PANEL_UPDATE_DIR', __DIR__.'/data/panel-updates');
define('PANEL_BACKUP_DIR', __DIR__.'/data/panel-backups');
if(!is_dir(PANEL_UPDATE_DIR))@mkdir(PANEL_UPDATE_DIR,0750,true);
if(!is_dir(PANEL_BACKUP_DIR))@mkdir(PANEL_BACKUP_DIR,0750,true);
define('APK_DIR',__DIR__.'/uploads/apk');
define('UPDATE_DIR',__DIR__.'/update');
define('RESET_TTL',(int)(getenv('RESET_TOKEN_TTL_MINUTES')?:30)*60);
define('APP_BASE_URL',rtrim((string)(getenv('APP_BASE_URL')?:''),'/'));
if(!is_dir(APK_DIR))@mkdir(APK_DIR,0755,true);
if(!is_dir(UPDATE_DIR))@mkdir(UPDATE_DIR,0755,true);
if(session_status()!==PHP_SESSION_ACTIVE){session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')]);session_start();}
