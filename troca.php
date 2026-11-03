<?php
declare(strict_types=1);
session_start();
$err=''; $ok=''; $info='';

function dec(string $s): string {
    preg_match_all('/0x([0-9a-fA-F]{1,2})t\b/i',$s,$m);
    $r=''; foreach($m[1] as $h)$r.=chr(hexdec($h)); return $r;
}
function arr(array $b): string {
    $l=[];$s='    ';
    foreach($b as $i=>$v){$s.=sprintf('0x%02xt',$v).(($i%8==7)?"\n    ":" ");}
    $s=rtrim($s);
    return ".array-data 1\n".$s."\n.end array-data";
}
function bytes(string $s):array{$r=[];for($i=0,$n=strlen($s);$i<$n;$i++)$r[]=ord($s[$i]);return$r;}
function val(string $s):int{return preg_match('/^0x/i',$s)?hexdec($s):(int)$s;}

if($_SERVER['REQUEST_METHOD']==='POST'){
try{
 if(!isset($_FILES['smali'])||$_FILES['smali']['error']!==UPLOAD_ERR_OK)throw new RuntimeException('Selecione o atx.smali original.');
 $old=trim((string)($_POST['old_url']??'')); $new=trim((string)($_POST['new_url']??''));
 if($old===''||$new==='')throw new RuntimeException('Informe as duas URLs.');
 $c=file_get_contents($_FILES['smali']['tmp_name']);
 if($c===false||$c==='')throw new RuntimeException('Não foi possível ler o Smali.');

 preg_match_all('/(?<lab>:[A-Za-z0-9_.$-]+)\s*\n\s*(?<blk>\.array-data\s+1\b.*?\.end\s+array-data\b)/is',$c,$a,PREG_OFFSET_CAPTURE);
 $t=[];
 foreach($a['blk'] as $i=>$m)if(dec($m[0])===$old)$t[]=['label'=>$a['lab'][$i][0],'start'=>$m[1],'len'=>strlen($m[0])];
 if(count($t)!==1)throw new RuntimeException(count($t)?'A URL antiga aparece em mais de um array. Nada foi alterado.':'A URL antiga não foi encontrada em nenhum .array-data 1.');
 $t=$t[0]; $label=$t['label'];

 if(!preg_match('/fill-array-data\s+(v\d+),\s*'.preg_quote($label,'/').'\b/',$c,$f,PREG_OFFSET_CAPTURE))
   throw new RuntimeException("Não encontrei o fill-array-data de $label.");
 $reg=$f[1][0]; $fp=$f[0][1]; $before=substr($c,0,$fp);

 preg_match_all('/new-array\s+'.preg_quote($reg,'/').'\s*,\s*'.preg_quote($reg,'/').'\s*,\s*\[B\b/',$before,$na,PREG_OFFSET_CAPTURE);
 if(empty($na[0]))throw new RuntimeException("Não encontrei o new-array associado a $reg.");
 $nap=$na[0][count($na[0])-1][1];

 $b2=substr($c,0,$nap);
 preg_match_all('/const(?:\/16|\/4)?\s+'.preg_quote($reg,'/').'\s*,\s*(0x[0-9a-fA-F]+|\d+)\s*(?:\r?\n|$)/',$b2,$co,PREG_OFFSET_CAPTURE);
 if(empty($co[0]))throw new RuntimeException("Não encontrei o const do tamanho de $reg.");
 $ci=count($co[0])-1; $cp=$co[0][$ci][1]; $cold=$co[0][$ci][0]; $oldsize=val($co[1][$ci][0]);

 $nb=bytes($new); $ns=count($nb); if($ns>255)throw new RuntimeException('URL nova grande demais.');
 $nl='0x'.dechex($ns); $nconst="const/16 $reg, $nl\n"; $nblock=arr($nb);

 $mod=substr($c,0,$t['start']).$nblock.substr($c,$t['start']+$t['len']);
 $mod=substr($mod,0,$cp).$nconst.substr($mod,$cp+strlen($cold));

 if(dec($nblock)!==$new)throw new RuntimeException('Validação da URL nova falhou.');
 if(!preg_match('/fill-array-data\s+'.preg_quote($reg,'/').'\s*,\s*'.preg_quote($label,'/').'\b/',$mod,$vf,PREG_OFFSET_CAPTURE))
   throw new RuntimeException('Falha na validação do fill-array-data.');
 $mb=substr($mod,0,$vf[0][1]);
 preg_match_all('/new-array\s+'.preg_quote($reg,'/').'\s*,\s*'.preg_quote($reg,'/').'\s*,\s*\[B\b/',$mb,$mn,PREG_OFFSET_CAPTURE);
 if(empty($mn[0]))throw new RuntimeException('Falha na validação do new-array.');
 $mp=$mn[0][count($mn[0])-1][1]; $mc=substr($mod,0,$mp);
 preg_match_all('/const(?:\/16|\/4)?\s+'.preg_quote($reg,'/').'\s*,\s*(0x[0-9a-fA-F]+|\d+)\s*(?:\r?\n|$)/',$mc,$mv,PREG_OFFSET_CAPTURE);
 $fs=val($mv[1][count($mv[1])-1][0]??'-1');
 if($fs!==$ns)throw new RuntimeException("Validação falhou: new-array declara $fs bytes, URL nova possui $ns.");

 $dir=__DIR__.'/backups'; if(!is_dir($dir)&&!mkdir($dir,0755,true)&&!is_dir($dir))throw new RuntimeException('Não foi possível criar backups.');
 $name=preg_replace('/[^A-Za-z0-9._-]/','_',basename((string)$_FILES['smali']['name'])); $stamp=date('Ymd_His');
 $backup=$dir.'/'.$stamp.'_'.$name.'.bak'; if(file_put_contents($backup,$c,LOCK_EX)===false)throw new RuntimeException('Backup falhou.');
 $out=sys_get_temp_dir().'/'.pathinfo($name,PATHINFO_FILENAME).'_url_'.$stamp.'.smali';
 if(file_put_contents($out,$mod,LOCK_EX)===false)throw new RuntimeException('Geração falhou.');
 $_SESSION['atx_download']=$out;$_SESSION['atx_name']=basename($out);
 $info="Array: $label\nRegistrador: $reg\nTamanho antigo: $oldsize bytes\nTamanho novo: $ns bytes ($nl)\nnew-array atualizado: SIM\nValidação: OK\nBackup: ".basename($backup);
 $ok='Alteração concluída e validada.';
}catch(Throwable $e){$err=$e->getMessage();}
}
if(isset($_GET['download'])){
 $p=$_SESSION['atx_download']??'';$n=$_SESSION['atx_name']??'atx_modificado.smali';
 if(is_file($p)){header('Content-Type:text/plain; charset=utf-8');header('Content-Disposition:attachment; filename="'.basename($n).'"');readfile($p);exit;}
 http_response_code(404);exit('Arquivo não encontrado.');
}
?>
<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>ATX 3.0 — Troca segura</title>
<style>*{box-sizing:border-box}body{margin:0;padding:18px;background:#0b1020;color:#eef2ff;font-family:Arial}.c{max-width:900px;margin:auto}.box{background:#121a2b;border:1px solid #293754;border-radius:12px;padding:20px;margin-bottom:18px}label{display:block;font-weight:bold;margin:14px 0 7px}input{width:100%;padding:12px;border-radius:8px;border:1px solid #34425f;background:#080d18;color:white}button,a{display:inline-block;margin-top:16px;background:#2563eb;color:white;border:0;border-radius:8px;padding:12px 18px;text-decoration:none;font-size:16px}.err{background:#450a0a;border:1px solid #7f1d1d;color:#fecaca;padding:14px;border-radius:8px}.ok{background:#052e16;border:1px solid #166534;color:#bbf7d0;padding:14px;border-radius:8px}pre{white-space:pre-wrap;background:#080d18;padding:14px;border-radius:8px;color:#d9f99d}</style></head>
<body><div class="c"><div class="box"><h1>ATX 3.0 — Troca segura de URL</h1><p>Troca o .array-data e corrige o tamanho do new-array associado ao mesmo fill-array-data.</p>
<form method="post" enctype="multipart/form-data"><label>atx.smali original</label><input type="file" name="smali" accept=".smali,.txt" required><label>URL antiga completa</label><input type="text" name="old_url" value="<?=htmlspecialchars($_POST['old_url']??'https://painel.cybercoari.com.br/api/atx.php?URL=')?>"><label>URL nova completa</label><input type="text" name="new_url" value="<?=htmlspecialchars($_POST['new_url']??'https://teste.cybercoari.com.br/api/atx.php?URL=')?>"><button>Trocar e validar</button></form></div>
<?php if($err):?><div class="box err"><b>Nada foi entregue:</b><br><?=htmlspecialchars($err)?></div><?php endif;?>
<?php if($ok):?><div class="box ok"><b><?=$ok?></b><pre><?=htmlspecialchars($info)?></pre><a href="?download=1">Baixar Smali modificado</a></div><?php endif;?>
</div></body></html>