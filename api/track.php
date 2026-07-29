<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['error'=>'Method not allowed']); exit; }

require_once '../admin/config.php';

try {
    $db = getDB();
    $input = json_decode(file_get_contents('php://input'), true);
    $event = $input['event'] ?? 'unknown';
    $data = isset($input['data']) ? json_encode($input['data']) : null;
    $pageUrl = $input['url'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $device = 'Desktop';
    if (preg_match('/mobile|android|iphone/i', $ua)) $device = 'Mobile';
    elseif (preg_match('/tablet|ipad/i', $ua)) $device = 'Tablet';

    $stmt = $db->prepare("INSERT INTO tracking (event, data, page_url, ip_address, user_agent, device) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$event, $data, $pageUrl, $ip, $ua, $device]);
    echo json_encode(['success'=>true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error'=>'Internal server error']);
}
