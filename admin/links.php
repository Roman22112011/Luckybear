<?php
require_once 'config.php'; requireAuth();
$db = getDB(); $message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) { $message = '<div class="alert alert-error">Ошибка безопасности</div>'; }
    else {
        $stmt = $db->prepare("REPLACE INTO settings (setting_key, setting_value) VALUES ('register_link', ?), ('play_link', ?)");
        $stmt->execute([$_POST['register_link'] ?? '#', $_POST['play_link'] ?? '#']);
        $message = '<div class="alert alert-success">Ссылки обновлены!</div>';
    }
}

$stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('register_link', 'play_link')");
$s = []; while($r = $stmt->fetch()) $s[$r['setting_key']] = $r['setting_value'];
$rl = $s['register_link'] ?? '#'; $pl = $s['play_link'] ?? '#';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ссылки - LuckyBear Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',sans-serif;background:#0B0E17;color:#FFF;display:flex;min-height:100vh}
        .sidebar{width:250px;background:#111624;border-right:1px solid #1E2538;padding:24px;position:fixed;height:100vh}
        .sidebar-logo{font-size:1.5rem;font-weight:700;margin-bottom:40px;color:#39FF14}
        .sidebar-nav{list-style:none}
        .sidebar-nav li{margin-bottom:8px}
        .sidebar-nav a{display:flex;align-items:center;gap:12px;padding:12px 16px;color:#9BA3B5;text-decoration:none;border-radius:12px}
        .sidebar-nav a:hover,.sidebar-nav a.active{background:rgba(57,255,20,0.1);color:#39FF14}
        .sidebar-logout{display:block;padding:12px;text-align:center;color:#ff4444;text-decoration:none;border-top:1px solid #1E2538;margin-top:auto}
        .main-content{margin-left:250px;flex:1;padding:32px}
        .page-title{font-size:1.8rem;font-weight:700;margin-bottom:32px}
        .form-card{background:#161B29;border:1px solid #1E2538;border-radius:16px;padding:32px;max-width:600px}
        .form-group{margin-bottom:24px}
        .form-group label{display:block;color:#9BA3B5;margin-bottom:8px;font-weight:500}
        .form-group input{width:100%;padding:14px 16px;background:#0B0E17;border:1px solid #1E2538;border-radius:12px;color:#FFF;font-size:1rem}
        .form-group input:focus{outline:none;border-color:#39FF14;box-shadow:0 0 20px rgba(57,255,20,0.2)}
        .form-hint{color:#6B7280;font-size:.8rem;margin-top:6px}
        .btn-save{padding:14px 32px;background:linear-gradient(135deg,#39FF14,#00F0FF);border:none;border-radius:12px;color:#0B0E17;font-weight:700;font-size:1rem;cursor:pointer}
        .btn-save:hover{transform:translateY(-2px);box-shadow:0 8px 30px rgba(57,255,20,0.3)}
        .alert{padding:16px;border-radius:12px;margin-bottom:24px}
        .alert-success{background:rgba(57,255,20,0.1);border:1px solid rgba(57,255,20,0.3);color:#39FF14}
        .alert-error{background:rgba(255,68,68,0.1);border:1px solid rgba(255,68,68,0.3);color:#ff4444}
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-logo">🐻</div>
        <ul class="sidebar-nav">
            <li><a href="dashboard.php"><i class="fas fa-chart-line"></i>Дашборд</a></li>
            <li><a href="links.php" class="active"><i class="fas fa-link"></i>Ссылки</a></li>
            <li><a href="games.php"><i class="fas fa-gamepad"></i>Игры</a></li>
            <li><a href="reviews.php"><i class="fas fa-star"></i>Отзывы</a></li>
            <li><a href="pages.php"><i class="fas fa-file"></i>Страницы</a></li>
            <li><a href="stats.php"><i class="fas fa-table"></i>Статистика</a></li>
        </ul>
        <a href="logout.php" class="sidebar-logout"><i class="fas fa-sign-out-alt"></i>Выйти</a>
    </aside>
    <main class="main-content">
        <h1 class="page-title">Управление ссылками</h1>
        <?=$message?>
        <div class="form-card">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?=generateCSRF()?>">
                <div class="form-group">
                    <label>Ссылка "Забрать бонус" (Регистрация)</label>
                    <input type="url" name="register_link" value="<?=htmlspecialchars($rl)?>" required>
                    <div class="form-hint">Все кнопки "Забрать бонус" ведут сюда</div>
                </div>
                <div class="form-group">
                    <label>Ссылка "Играть" (Игры)</label>
                    <input type="url" name="play_link" value="<?=htmlspecialchars($pl)?>" required>
                    <div class="form-hint">Кнопки "Играть сейчас" ведут сюда</div>
                </div>
                <button type="submit" class="btn-save"><i class="fas fa-save"></i>Сохранить</button>
            </form>
        </div>
    </main>
</body>
</html>
