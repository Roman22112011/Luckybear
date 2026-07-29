<?php
require_once 'config.php'; requireAuth();
$db = getDB();
$today = date('Y-m-d'); $weekAgo = date('Y-m-d', strtotime('-7 days'));

$stmt = $db->prepare("SELECT COUNT(*) as total FROM tracking WHERE DATE(created_at) = ?");
$stmt->execute([$today]); $todayTotal = $stmt->fetch()['total'];
$stmt = $db->prepare("SELECT COUNT(*) as total FROM tracking WHERE DATE(created_at) = ? AND event = 'register_click'");
$stmt->execute([$today]); $todayReg = $stmt->fetch()['total'];
$stmt = $db->prepare("SELECT COUNT(*) as total FROM tracking WHERE DATE(created_at) = ? AND event = 'play_click'");
$stmt->execute([$today]); $todayPlay = $stmt->fetch()['total'];
$stmt = $db->prepare("SELECT COUNT(*) as total FROM tracking WHERE created_at >= ?");
$stmt->execute([$weekAgo]); $weekTotal = $stmt->fetch()['total'];

$dailyStats = [];
for ($i=6; $i>=0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $stmt = $db->prepare("SELECT COUNT(*) as t FROM tracking WHERE DATE(created_at) = ?");
    $stmt->execute([$d]); $dailyStats[$d] = $stmt->fetch()['t'];
}

$stmt = $db->prepare("SELECT device, COUNT(*) as c FROM tracking WHERE created_at >= ? GROUP BY device");
$stmt->execute([$weekAgo]); $devices = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Дашборд - LuckyBear Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        *{margin:0;padding:0;box-sizing:border-box}
        body{font-family:'Inter',sans-serif;background:#0B0E17;color:#FFF;display:flex;min-height:100vh}
        .sidebar{width:250px;background:#111624;border-right:1px solid #1E2538;padding:24px;position:fixed;height:100vh;display:flex;flex-direction:column}
        .sidebar-logo{font-size:1.5rem;font-weight:700;margin-bottom:40px;color:#39FF14}
        .sidebar-nav{list-style:none;flex:1}
        .sidebar-nav li{margin-bottom:8px}
        .sidebar-nav a{display:flex;align-items:center;gap:12px;padding:12px 16px;color:#9BA3B5;text-decoration:none;border-radius:12px}
        .sidebar-nav a:hover,.sidebar-nav a.active{background:rgba(57,255,20,0.1);color:#39FF14}
        .sidebar-logout{display:block;padding:12px;text-align:center;color:#ff4444;text-decoration:none;border-top:1px solid #1E2538;margin-top:auto}
        .main-content{margin-left:250px;flex:1;padding:32px}
        .page-title{font-size:1.8rem;font-weight:700;margin-bottom:32px}
        .stats-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px;margin-bottom:32px}
        .stat-card{background:#161B29;border:1px solid #1E2538;border-radius:16px;padding:24px}
        .stat-label{color:#9BA3B5;font-size:.85rem;margin-bottom:8px}
        .stat-value{font-size:2rem;font-weight:700}
        .charts-grid{display:grid;grid-template-columns:2fr 1fr;gap:20px}
        .chart-card{background:#161B29;border:1px solid #1E2538;border-radius:16px;padding:24px}
        .chart-title{font-weight:600;margin-bottom:20px}
        .chart-container{position:relative;height:300px}
        canvas{width:100%!important}
        @media(max-width:1024px){.stats-grid{grid-template-columns:repeat(2,1fr)}.charts-grid{grid-template-columns:1fr}}
        @media(max-width:768px){.sidebar{width:60px;padding:16px 8px}.sidebar-nav a span{display:none}.main-content{margin-left:60px}.stats-grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="sidebar-logo">🐻</div>
        <ul class="sidebar-nav">
            <li><a href="dashboard.php" class="active"><i class="fas fa-chart-line"></i><span>Дашборд</span></a></li>
            <li><a href="links.php"><i class="fas fa-link"></i><span>Ссылки</span></a></li>
            <li><a href="games.php"><i class="fas fa-gamepad"></i><span>Игры</span></a></li>
            <li><a href="reviews.php"><i class="fas fa-star"></i><span>Отзывы</span></a></li>
            <li><a href="pages.php"><i class="fas fa-file"></i><span>Страницы</span></a></li>
            <li><a href="stats.php"><i class="fas fa-table"></i><span>Статистика</span></a></li>
        </ul>
        <a href="logout.php" class="sidebar-logout"><i class="fas fa-sign-out-alt"></i><span>Выйти</span></a>
    </aside>
    <main class="main-content">
        <h1 class="page-title">Дашборд</h1>
        <div class="stats-grid">
            <div class="stat-card"><div class="stat-label">Посетители сегодня</div><div class="stat-value"><?=number_format($todayTotal)?></div></div>
            <div class="stat-card"><div class="stat-label">Клики "Забрать бонус"</div><div class="stat-value"><?=number_format($todayReg)?></div></div>
            <div class="stat-card"><div class="stat-label">Клики "Играть"</div><div class="stat-value"><?=number_format($todayPlay)?></div></div>
            <div class="stat-card"><div class="stat-label">Всего за неделю</div><div class="stat-value"><?=number_format($weekTotal)?></div></div>
        </div>
        <div class="charts-grid">
            <div class="chart-card"><h3 class="chart-title">Посещения за 7 дней</h3><div class="chart-container"><canvas id="weeklyChart"></canvas></div></div>
            <div class="chart-card"><h3 class="chart-title">Устройства</h3><div class="chart-container"><canvas id="deviceChart"></canvas></div></div>
        </div>
    </main>
    <script>
        new Chart(document.getElementById('weeklyChart'),{type:'line',data:{labels:<?=json_encode(array_keys($dailyStats))?>,datasets:[{label:'Посетители',data:<?=json_encode(array_values($dailyStats))?>,borderColor:'#39FF14',backgroundColor:'rgba(57,255,20,0.1)',borderWidth:2,fill:true,tension:.4}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{ticks:{color:'#9BA3B5'},grid:{color:'#1E2538'}},y:{ticks:{color:'#9BA3B5'},grid:{color:'#1E2538'},beginAtZero:true}}}});
        const devs = <?=json_encode($devices)?>;
        new Chart(document.getElementById('deviceChart'),{type:'doughnut',data:{labels:devs.map(d=>d.device||'Unknown'),datasets:[{data:devs.map(d=>d.c),backgroundColor:['#39FF14','#FFD700','#4F46E5','#ff4444'],borderColor:'#0B0E17'}]},options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{position:'bottom',labels:{color:'#9BA3B5'}}}}});
    </script>
</body>
</html>
