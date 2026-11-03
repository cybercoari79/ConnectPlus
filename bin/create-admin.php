<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/functions.php';
if(PHP_SAPI!=='cli'){fwrite(STDERR,"Somente CLI.\n");exit(1);}
$username=$argv[1]??'';$email=$argv[2]??'';
if(!preg_match('/^[a-zA-Z0-9_.-]{3,40}$/',$username)||!filter_var($email,FILTER_VALIDATE_EMAIL)){fwrite(STDERR,"Uso: php bin/create-admin.php usuario email\n");exit(1);}
if(find_login($username)||find_login($email)){fwrite(STDERR,"Usuário ou e-mail já existe.\n");exit(1);}
if(function_exists('readline')){$pass=readline('Senha do administrador: ');}else{$pass=trim(fgets(STDIN));}
if(strlen($pass)<10){fwrite(STDERR,"Use uma senha com pelo menos 10 caracteres.\n");exit(1);}
$u=['username'=>$username,'email'=>$email,'password'=>password_hash($pass,PASSWORD_DEFAULT),'role'=>'admin','isAdmin'=>true,'plan'=>'admin','expiresAt'=>null,'active'=>true,'status'=>'active','updateUuid'=>uuidv4()];
save_user($u);$u=get_user($username);save_config($u,default_config());echo "Administrador criado: {$username}\n";
