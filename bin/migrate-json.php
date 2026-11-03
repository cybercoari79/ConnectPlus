<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/functions.php';
if(PHP_SAPI!=='cli'){exit("Somente CLI.\n");}
$dir=$argv[1]??(__DIR__.'/../data/users');
$files=glob(rtrim($dir,'/').'/*.json')?:[];
if(!$files){echo "Nenhum JSON encontrado em {$dir}\n";exit(0);}
foreach($files as $file){$u=json_decode((string)file_get_contents($file),true);if(!is_array($u)||empty($u['username']))continue;try{$u['password']=(string)($u['password']??$u['password_hash']??'');if($u['password']==='')throw new RuntimeException('sem senha');if(empty($u['updateUuid']))$u['updateUuid']=uuidv4();save_user($u);$dbu=get_user($u['username']);$c=json_decode((string)($u['config_json']??''),true);if(!is_array($c))$c=default_config();save_config($dbu,$c);echo "Migrado: {$u['username']}\n";}catch(Throwable $e){fwrite(STDERR,"Falha {$file}: {$e->getMessage()}\n");}}
