<?php
require_once __DIR__ . '/includes/functions.php';

$token  = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$error  = '';
$ok     = '';
$target = null;

foreach (list_users() as $x) {
    if (
        hash_equals((string)($x['passwordResetTokenHash'] ?? ''), hash('sha256', $token))
        && !empty($x['passwordResetExpiresAt'])
        && strtotime((string)$x['passwordResetExpiresAt']) > time()
    ) {
        $target = $x;
        break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$target) {
        $error = 'Token inválido ou expirado.';
    } elseif (strlen((string)($_POST['password'] ?? '')) < 4) {
        $error = 'A senha deve ter pelo menos 4 caracteres.';
    } elseif ($_POST['password'] !== $_POST['confirm']) {
        $error = 'As senhas não coincidem.';
    } else {
        $target['password'] = password_hash((string)$_POST['password'], PASSWORD_DEFAULT);
        unset($target['passwordResetTokenHash'], $target['passwordResetExpiresAt']);
        save_user($target);
        $ok = 'Senha alterada com sucesso.';
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Nova senha · ConnectPlus</title>
    <link rel="stylesheet" href="/assets/css/style.css?v=1">
</head>
<body class="auth-body">
    <div class="glass-container auth-box">
        <div class="logo-area">
            <div class="auth-mark">CP</div>
            <div class="auth-eyebrow">CONNECTPLUS PANEL</div>
            <h2>Nova senha</h2>
        </div>

        <?php if ($error): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($ok): ?>
            <div class="alert"><?= e($ok) ?></div>
            <p><a href="/login.php">Ir para o login</a></p>
        <?php else: ?>
            <form method="post">
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <div class="form-group">
                    <label>Nova senha</label>
                    <input type="password" name="password" minlength="4" required>
                </div>
                <div class="form-group">
                    <label>Confirmar senha</label>
                    <input type="password" name="confirm" minlength="4" required>
                </div>
                <button class="btn primary full-width">Salvar senha</button>
            </form>
        <?php endif; ?>

        <div class="auth-footer">
            <a href="/login.php">Voltar</a>
        </div>
    </div>
</body>
</html>