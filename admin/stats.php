<?php
require_once 'config.php'; requireAuth();
$db = getDB();
$dateFrom = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
$dateTo = $_GET['to'] ?? date('Y-m-d');

$stmt = $db->prepare("SELECT * FROM tracking WHERE DATE(created_at) BETWEEN ? AND ? ORDER BY created_at DESC LIMIT 500");
$stmt->execute([$dateFrom, $dateTo]);
$events = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Статистика - LuckyBear Admin</title>
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
        .filter-bar{background:#161B29;border:1px solid #1E2538;border-radius:16px;padding:20px;margin-bottom:24px;display:flex;gap:16px;align-items:center;flex-wrap:wrap}
        .filter-bar input{padding:10px 16px;background:#0B0E17;border:1px solid #1E2538;border-radius:10px;color:#FFF}
        .filter-bar button{padding:10px 24px;background:linear-gradient(135deg,#39FF14,#00F0FF);border:none;border-radius:10px;color:#0B0E17;font-weight:600;cursor:pointer}
        table{width:100%;border-collapse:collapse;background:#161B29;border-radius:16px;overflow:hidden}
        th,td{padding:14px 16px;text-align:left;border-bottom:1px solid #1E2538}
        th{color:#9BA3B5;font-weight:500;font-size:.85rem;background:#111624}
        td{font-size:.9rem}
        .badge-event{display:inline-block;padding:4px 12px;border-radius:20px;font-size:.75rem;font-weight:600}
        .badge-register{background:rgba(57,255,20,0.2);color:#39FF14}
        .badge-play{background:rgba(255,215,0,0.2);color:#FFD700}
        .badge-view{background:rgba(79,70,229,0.2);color:#818cf8}
        .btn-export{padding:10px 24px;background:transparent;border:1px solid #1E2538;border-radius:10px;color:#9BA3B5;cursor:pointer;text-decoration:none}
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
            <li><a href="pages.php"><i class="fas fa-file"></i>Страницы</a></li>
            <li><a href="stats.php" class="active"><i class="fas fa-table"></i>Статистика</a></li>
        </ul>
        <a href="logout.php" class="sidebar-logout"><i class="fas fa-sign-out-alt"></i>Выйти</a>
    </aside>
    <main class="main-content">
        <h1 class="page-title">Детальная статистика</h1>
        <div class="filter-bar">
            <form method="GET" style="display:flex;gap:16px;align-items:center;flex-wrap:wrap">
                <span>С:</span><input type="date" name="from" value="<?=$dateFrom?>">
                <span>По:</span><input type="date" name="to" value="<?=$dateTo?>">
                <button type="submit"><i class="fas fa-filter"></i> Фильтр</button>
                <a href="stats.php?export=1&from=<?=$dateFrom?>&to=<?=$dateTo?>" class="btn-export">📥 CSV</a>
            </form>
        </div>
        <div style="overflow-x:auto">
            <table>
                <thead><tr><th>Дата</th><th>Событие</th><th>Данные</th><th>Устройство</th><th>Страница</th></tr></thead>
                <tbody>
                    <?php foreach($events as $e): 
                        $badgeClass = match($e['event']) {'register_click'=>'badge-register','play_click'=>'badge-play',default=>'badge-view'};
                    ?>
                    <tr>
                        <td><?=date('d.m.Y H:i', strtotime($e['created_at']))?></td>
                        <td><span class="badge-event <?=$badgeClass?>"><?=$e['event']?></span></td>
                        <td><?=htmlspecialchars($e['data']??'')?></td>
                        <td><?=$e['device']?></td>
                        <td><?=htmlspecialchars($e['page_url']??'')?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
