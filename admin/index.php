<?php
require_once 'config.php';
if (isLoggedIn()) { header('Location: dashboard.php'); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['username'] === ADMIN_USER && password_verify($_POST['password'], ADMIN_PASS)) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: dashboard.php'); exit;
    } else { $error = 'Неверный логин или пароль'; }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LuckyBear Admin - Вход</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',sans-serif;background:linear-gradient(135deg,#0B0E17 0%,#111624 100%);min-height:100vh;display:flex;align-items:center;justify-content:center}
        .login-container{background:#161B29;border:1px solid #1E2538;border-radius:24px;padding:48px;width:100%;max-width:420px}
        .login-logo{text-align:center;margin-bottom:32px;font-size:2rem}
        .login-title{font-size:1.5rem;font-weight:700;color:#FFF;text-align:center;margin-bottom:32px}
        .form-group{margin-bottom:20px}
        .form-group label{display:block;color:#9BA3B5;margin-bottom:8px;font-size:.9rem}
        .form-group input{width:100%;padding:14px 16px;background:#0B0E17;border:1px solid #1E2538;border-radius:12px;color:#FFF;font-size:1rem}
        .form-group input:focus{outline:none;border-color:#39FF14;box-shadow:0 0 20px rgba(57,255,20,0.2)}
        .btn-login{width:100%;padding:14px;background:linear-gradient(135deg,#39FF14,#00F0FF);border:none;border-radius:12px;color:#0B0E17;font-weight:700;font-size:1rem;cursor:pointer}
        .btn-login:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(57,255,20,0.3)}
        .error{background:rgba(255,68,68,0.1);border:1px solid rgba(255,68,68,0.3);color:#ff4444;padding:12px;border-radius:8px;margin-bottom:20px;text-align:center}
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-logo">🐻 LuckyBear</div>
        <h1 class="login-title">Вход в админ-панель</h1>
        <?php if($error): ?><div class="error"><?=htmlspecialchars($error)?></div><?php endif; ?>
        <form method="POST">
            <div class="form-group"><label>Логин</label><input type="text" name="username" required autofocus></div>
            <div class="form-group"><label>Пароль</label><input type="password" name="password" required></div>
            <button type="submit" class="btn-login">Войти</button>
        </form>
    </div>
</body>
</html>
