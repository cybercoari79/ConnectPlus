<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/includes/functions.php';

echo "PHP: " . PHP_VERSION . "<br>";

$funcs = ['current_user','find_login','get_user','save_user','list_users',
          'default_config','save_config','uuidv4','e'];

foreach ($funcs as $f) {
    echo $f . ": " . (function_exists($f) ? "OK" : "❌ NÃO EXISTE") . "<br>";
}

// Testa escrita
$dir = __DIR__ . '/data';
echo "Dir data existe: " . (is_dir($dir) ? "sim" : "não") . "<br>";
echo "Dir data gravável: " . (is_writable($dir) ? "sim" : "não") . "<br>";