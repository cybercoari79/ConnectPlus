<?php
require_once __DIR__ . '/includes/functions.php';

if (current_user()) {
    header('Location:/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim((string)($_POST['username'] ?? ''));
    $email = trim((string)($_POST['email'] ?? ''));
    $pass  = (string)($_POST['password'] ?? '');

    if (!preg_match('/^[a-zA-Z0-9_.-]{3,40}$/', $name)) {
        $error = 'Usuário inválido.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'E-mail inválido.';
    } elseif (strlen($pass) < 4) {
        $error = 'A senha deve ter pelo menos 4 caracteres.';
    } elseif (find_login($name) || find_login($email)) {
        $error = 'Usuário ou e-mail já cadastrado.';
    } else {
        $u = [
            'username'  => $name,
            'email'     => $email,
            'password'  => password_hash($pass, PASSWORD_DEFAULT),
            'role'      => 'user',
            'isAdmin'   => false,
            'plan'      => 'trial',
            'expiresAt' => date('Y-m-d H:i:s', strtotime('+3 days')),
            'active'    => true,
            'status'    => 'active'
        ];

        $u['updateUuid'] = uuidv4();
        save_user($u);

        $u = get_user($name);
        $c = default_config();
        save_config($u, $c);

        header('Location:/login.php');
        exit;
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Registrar · ConnectPlus</title>
    <link rel="stylesheet" href="/assets/css/style.css?v=1">
</head>
<body class="auth-body">
    <div class="glass-container auth-box">
        <div class="logo-area">
            <div class="auth-mark">CP</div>
            <div class="auth-eyebrow">CONNECTPLUS PANEL</div>
            <h2>Criar conta</h2>
            <p>Cadastre seu acesso ao painel.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="form-group">
                <label>Usuário</label>
                <input name="username" required>
            </div>
            <div class="form-group">
                <label>E-mail</label>
                <input type="email" name="email" required>
            </div>
            <div class="form-group">
                <label>Senha</label>
                <input type="password" name="password" minlength="4" required>
            </div>
            <button class="btn primary full-width">Criar conta</button>
        </form>

        <br>
        <div class="auth-footer">
            <a href="/login.php">Voltar para o login</a>
        </div>
    </div>
</body>
</html>