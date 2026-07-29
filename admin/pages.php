<?php
require_once 'config.php'; requireAuth();
$db = getDB(); $message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) { $message = '<div class="alert alert-error">Ошибка безопасности</div>'; }
    elseif ($_POST['action'] === 'save') {
        $id = $_POST['id'] ?? null; $is_active = isset($_POST['is_active']) ? 1 : 0;
        if ($id) {
            $stmt = $db->prepare("UPDATE pages SET title=?, content=?, is_active=? WHERE id=?");
            $stmt->execute([$_POST['title'], $_POST['content'], $is_active, $id]);
        } else {
            $stmt = $db->prepare("INSERT INTO pages (slug, title, content, is_active) VALUES (?,?,?,?)");
            $stmt->execute([$_POST['slug'], $_POST['title'], $_POST['content'], $is_active]);
        }
        $message = '<div class="alert alert-success">Сохранено!</div>';
    }
}

$pages = $db->query("SELECT * FROM pages ORDER BY id DESC")->fetchAll();
$edit = null;
if (isset($_GET['edit'])) { $stmt = $db->prepare("SELECT * FROM pages WHERE id=?"); $stmt->execute([$_GET['edit']]); $edit = $stmt->fetch(); }
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Страницы - LuckyBear Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',sans-serif;background:#0B0E17;color:#FFF;display:flex;min-height:100vh}
        .sidebar{width:250px;background:#111624;border-right:1px solid #1E2538;padding:24px;position:fixed;height:100vh}
        .sidebar-logo{font-size:1.5rem;font-weight:700;margin-bottom:40px;color:#39FF14}
        .sidebar-nav{list-style:none}.sidebar-nav li{margin-bottom:8px}
        .sidebar-nav a{display:flex;align-items:center;gap:12px;padding:12px 16px;color:#9BA3B5;text-decoration:none;border-radius:12px}
        .sidebar-nav a:hover,.sidebar-nav a.active{background:rgba(57,255,20,0.1);color:#39FF14}
        .sidebar-logout{display:block;padding:12px;text-align:center;color:#ff4444;text-decoration:none;border-top:1px solid #1E2538;margin-top:auto}
        .main-content{margin-left:250px;flex:1;padding:32px}
        .page-title{font-size:1.8rem;font-weight:700;margin-bottom:32px}
        .content-grid{display:grid;grid-template-columns:1fr 2fr;gap:24px}
        .form-card,.list-card{background:#161B29;border:1px solid #1E2538;border-radius:16px;padding:24px}
        .form-group{margin-bottom:16px}.form-group label{display:block;color:#9BA3B5;margin-bottom:6px}
        .form-group input,.form-group textarea{width:100%;padding:12px;background:#0B0E17;border:1px solid #1E2538;border-radius:10px;color:#FFF}
        .form-group textarea{resize:vertical;min-height:150px}
        .btn-save{padding:12px 24px;background:linear-gradient(135deg,#39FF14,#00F0FF);border:none;border-radius:10px;color:#0B0E17;font-weight:700;cursor:pointer}
        .btn-cancel{padding:12px 24px;background:transparent;border:1px solid #1E2538;border-radius:10px;color:#9BA3B5;cursor:pointer;margin-left:8px;text-decoration:none;display:inline-block}
        table{width:100%;border-collapse:collapse}th,td{padding:12px;text-align:left;border-bottom:1px solid #1E2538}
        .btn-action{padding:6px 12px;border:1px solid #1E2538;border-radius:6px;color:#9BA3B5;text-decoration:none}
        .btn-action:hover{border-color:#39FF14;color:#39FF14}
        .badge{display:inline-block;padding:4px 10px;border-radius:20px;font-size:.75rem}
        .badge-active{background:rgba(57,255,20,0.2);color:#39FF14}
        .alert{padding:16px;border-radius:12px;margin-bottom:24px}
        .alert-success{background:rgba(57,255,20,0.1);border:1px solid rgba(57,255,20,0.3);color:#39FF14}
        @media(max-width:1024px){.content-grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-logo">🐻</div>
        <ul class="sidebar-nav">
            <li><a href="dashboard.php"><i class="fas fa-chart-line"></i>Дашборд</a></li>
            <li><a href="links.php"><i class="fas fa-link"></i>Ссылки</a></li>
            <li><a href="games.php"><i class="fas fa-gamepad"></i>Игры</a></li>
            <li><a href="reviews.php"><i class="fas fa-star"></i>Отзывы</a></li>
            <li><a href="pages.php" class="active"><i class="fas fa-file"></i>Страницы</a></li>
            <li><a href="stats.php"><i class="fas fa-table"></i>Статистика</a></li>
        </ul>
        <a href="logout.php" class="sidebar-logout"><i class="fas fa-sign-out-alt"></i>Выйти</a>
    </aside>
    <main class="main-content">
        <h1 class="page-title">Управление страницами</h1>
        <?=$message?>
        <div class="content-grid">
            <div class="form-card">
                <h3><?=$edit?'Редактировать':'Добавить'?> страницу</h3>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?=generateCSRF()?>">
                    <input type="hidden" name="action" value="save">
                    <?php if($edit):?><input type="hidden" name="id" value="<?=$edit['id']?>"><?php endif;?>
                    <div class="form-group"><label>Slug</label><input type="text" name="slug" value="<?=$edit['slug']??''?>" <?=$edit?'readonly':''?> required></div>
                    <div class="form-group"><label>Заголовок</label><input type="text" name="title" value="<?=$edit['title']??''?>" required></div>
                    <div class="form-group"><label>Контент (HTML)</label><textarea name="content"><?=$edit['content']??''?></textarea></div>
                    <div class="form-group"><label><input type="checkbox" name="is_active" <?=($edit['is_active']??1)?'checked':''?>> Активна</label></div>
                    <button type="submit" class="btn-save">Сохранить</button>
                    <?php if($edit):?><a href="pages.php" class="btn-cancel">Отмена</a><?php endif;?>
                </form>
            </div>
            <div class="list-card">
                <h3>Все страницы</h3>
                <table>
                    <thead><tr><th>Slug</th><th>Заголовок</th><th>Статус</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach($pages as $p):?>
                        <tr>
                            <td><?=$p['slug']?></td><td><?=htmlspecialchars($p['title'])?></td>
                            <td><span class="badge badge-active"><?=$p['is_active']?'Активна':'Скрыта'?></span></td>
                            <td><a href="?edit=<?=$p['id']?>" class="btn-action">✏️</a></td>
                        </tr>
                        <?php endforeach;?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
