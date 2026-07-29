<?php
require_once 'config.php'; requireAuth();
$db = getDB(); $message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCSRF($_POST['csrf_token'] ?? '')) { $message = '<div class="alert alert-error">Ошибка безопасности</div>'; }
    elseif ($_POST['action'] === 'save') {
        $id = $_POST['id'] ?? null; $is_active = isset($_POST['is_active']) ? 1 : 0;
        if ($id) {
            $stmt = $db->prepare("UPDATE reviews SET name=?, avatar=?, stars=?, text=?, is_active=? WHERE id=?");
            $stmt->execute([$_POST['name'], $_POST['avatar'], $_POST['stars'], $_POST['text'], $is_active, $id]);
        } else {
            $stmt = $db->prepare("INSERT INTO reviews (name, avatar, stars, text, is_active) VALUES (?,?,?,?,?)");
            $stmt->execute([$_POST['name'], $_POST['avatar'], $_POST['stars'], $_POST['text'], $is_active]);
        }
        $message = '<div class="alert alert-success">Сохранено!</div>';
    } elseif ($_POST['action'] === 'delete') {
        $db->prepare("DELETE FROM reviews WHERE id=?")->execute([$_POST['id']]);
        $message = '<div class="alert alert-success">Удалено!</div>';
    }
}

$reviews = $db->query("SELECT * FROM reviews ORDER BY id DESC")->fetchAll();
$edit = null;
if (isset($_GET['edit'])) { $stmt = $db->prepare("SELECT * FROM reviews WHERE id=?"); $stmt->execute([$_GET['edit']]); $edit = $stmt->fetch(); }
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Отзывы - LuckyBear Admin</title>
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
        .card-title{font-weight:600;margin-bottom:20px;font-size:1.1rem}
        .form-group{margin-bottom:16px}.form-group label{display:block;color:#9BA3B5;margin-bottom:6px;font-size:.9rem}
        .form-group input,.form-group select,.form-group textarea{width:100%;padding:12px;background:#0B0E17;border:1px solid #1E2538;border-radius:10px;color:#FFF;font-size:.95rem}
        .form-group textarea{resize:vertical;min-height:80px}
        .btn-save{padding:12px 24px;background:linear-gradient(135deg,#39FF14,#00F0FF);border:none;border-radius:10px;color:#0B0E17;font-weight:700;cursor:pointer}
        .btn-cancel{padding:12px 24px;background:transparent;border:1px solid #1E2538;border-radius:10px;color:#9BA3B5;cursor:pointer;margin-left:8px;text-decoration:none;display:inline-block}
        table{width:100%;border-collapse:collapse}th,td{padding:12px;text-align:left;border-bottom:1px solid #1E2538}
        th{color:#9BA3B5;font-weight:500;font-size:.85rem}td{font-size:.9rem}
        .btn-action{padding:6px 12px;border:1px solid #1E2538;border-radius:6px;color:#9BA3B5;text-decoration:none;font-size:.8rem;margin-right:4px}
        .btn-action:hover{border-color:#39FF14;color:#39FF14}
        .badge{display:inline-block;padding:4px 10px;border-radius:20px;font-size:.75rem}
        .badge-active{background:rgba(57,255,20,0.2);color:#39FF14}
        .badge-inactive{background:rgba(255,68,68,0.2);color:#ff4444}
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
            <li><a href="reviews.php" class="active"><i class="fas fa-star"></i>Отзывы</a></li>
            <li><a href="pages.php"><i class="fas fa-file"></i>Страницы</a></li>
            <li><a href="stats.php"><i class="fas fa-table"></i>Статистика</a></li>
        </ul>
        <a href="logout.php" class="sidebar-logout"><i class="fas fa-sign-out-alt"></i>Выйти</a>
    </aside>
    <main class="main-content">
        <h1 class="page-title">Управление отзывами</h1>
        <?=$message?>
        <div class="content-grid">
            <div class="form-card">
                <h3 class="card-title"><?=$edit?'Редактировать':'Добавить'?> отзыв</h3>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?=generateCSRF()?>">
                    <input type="hidden" name="action" value="save">
                    <?php if($edit):?><input type="hidden" name="id" value="<?=$edit['id']?>"><?php endif;?>
                    <div class="form-group"><label>Имя</label><input type="text" name="name" value="<?=$edit['name']??''?>" required></div>
                    <div class="form-group"><label>Аватар (emoji)</label><input type="text" name="avatar" value="<?=$edit['avatar']??'👤'?>"></div>
                    <div class="form-group"><label>Рейтинг (1-5)</label><input type="number" name="stars" min="1" max="5" value="<?=$edit['stars']??5?>" required></div>
                    <div class="form-group"><label>Текст отзыва</label><textarea name="text" required><?=$edit['text']??''?></textarea></div>
                    <div class="form-group"><label><input type="checkbox" name="is_active" <?=($edit['is_active']??1)?'checked':''?>> Активен</label></div>
                    <button type="submit" class="btn-save">Сохранить</button>
                    <?php if($edit):?><a href="reviews.php" class="btn-cancel">Отмена</a><?php endif;?>
                </form>
            </div>
            <div class="list-card">
                <h3 class="card-title">Все отзывы (<?=count($reviews)?>)</h3>
                <div style="overflow-x:auto">
                    <table>
                        <thead><tr><th>Аватар</th><th>Имя</th><th>⭐</th><th>Текст</th><th>Статус</th><th></th></tr></thead>
                        <tbody>
                            <?php foreach($reviews as $r):?>
                            <tr>
                                <td><?=$r['avatar']?></td><td><?=htmlspecialchars($r['name'])?></td><td><?=$r['stars']?></td>
                                <td><?=mb_substr(htmlspecialchars($r['text']),0,50)?>...</td>
                                <td><span class="badge <?=$r['is_active']?'badge-active':'badge-inactive'?>"><?=$r['is_active']?'Активен':'Скрыт'?></span></td>
                                <td>
                                    <a href="?edit=<?=$r['id']?>" class="btn-action">✏️</a>
                                    <form method="POST" style="display:inline" onsubmit="return confirm('Удалить?')">
                                        <input type="hidden" name="csrf_token" value="<?=generateCSRF()?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?=$r['id']?>">
                                        <button type="submit" class="btn-action" style="color:#ff4444">🗑️</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach;?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
