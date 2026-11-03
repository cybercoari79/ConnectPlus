<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

// Compatibilidade com o ConnectPlus RE 2026.1.1006-14.
// Endpoint público e somente leitura: GET /pudim/sms
// A mensagem global vem exclusivamente do usuário administrador.
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    json_response(['error' => 'Método não permitido'], 405);
}

$admin = get_user('admin');
if (!$admin || !is_admin($admin)) {
    json_response(['error' => 'Administrador principal não encontrado'], 503);
}

$config = user_config($admin);
$sms = $config['Sms'] ?? [];

json_response([
    'Version' => (int)($sms['Version'] ?? 0),
    'Notes'   => (string)($sms['Notes'] ?? ''),
]);