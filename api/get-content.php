<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
require_once '../admin/config.php';

try {
    $db = getDB();
    $response = ['success'=>true];

    $stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('register_link','play_link')");
    $links = [];
    while($r = $stmt->fetch()) $links[$r['setting_key']] = $r['setting_value'];
    $response['links'] = $links;

    $response['games'] = $db->query("SELECT * FROM games WHERE is_active=1 ORDER BY sort_order, id DESC")->fetchAll();
    $response['reviews'] = $db->query("SELECT * FROM reviews WHERE is_active=1 ORDER BY sort_order, id DESC")->fetchAll();

    if (isset($_GET['page'])) {
        $stmt = $db->prepare("SELECT * FROM pages WHERE slug=? AND is_active=1");
        $stmt->execute([$_GET['page']]);
        $page = $stmt->fetch();
        $response['content'] = $page ? '<h2>'.htmlspecialchars($page['title']).'</h2>'.$page['content'] : '<p>Страница не найдена</p>';
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['success'=>false, 'error'=>$e->getMessage()]);
}
