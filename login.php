<?php
require_once __DIR__ . '/includes/functions.php';

if (current_user()) {
    header('Location: /dashboard.php');
    exit;
}

$error   = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim((string)($_POST['username'] ?? ''));
    $pass  = (string)($_POST['password'] ?? '');

    $u = find_login($login);

    if ($u && password_verify($pass, (string)($u['password'] ?? ''))) {
        session_regenerate_id(true);
        $_SESSION['username'] = $u['username'];
        $_SESSION['csrf']     = bin2hex(random_bytes(24));
        header('Location: /dashboard.php');
        exit;
    }

    $error = 'Usuário/e-mail ou senha inválidos.';
}
?>

<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Entrar · ConnectPlus</title>
    <link rel="stylesheet" href="/assets/css/style.css?v=1">
</head>
<body class="auth-body">
    <div class="glass-container auth-box">
        <div class="logo-area">
          
            <div class="auth-mark">CP</div>
            <div class="auth-eyebrow">CONNECTPLUS PANEL</div>
            <h2>Bem-vindo de volta</h2>
            <p>Entre para gerenciar suas configurações.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="alert"><?= e($message) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label>Usuário ou e-mail</label>
                <input name="username" required autocomplete="username">
            </div>
            <div class="form-group">
                <label>Senha</label>
                <input type="password" name="password" required autocomplete="current-password">
            </div>
            <div class="auth-forgot">
                <a href="/forgot-password.php">Esqueci minha senha</a>
            </div>
            <button class="btn primary full-width">Entrar no painel</button>
        </form>

        <div class="auth-footer">
            Não tem uma conta? <a href="/register.php">Registrar</a>
        </div>
    </div>
</body>
</html>