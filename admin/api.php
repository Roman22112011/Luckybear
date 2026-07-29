<?php
require_once 'config.php'; requireAuth();
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'get_stats') {
    $db = getDB();
    $days = [];
    for ($i=6; $i>=0; $i--) {
        $d = date('Y-m-d', strtotime("-{$i} days"));
        $stmt = $db->prepare("SELECT COUNT(*) as t FROM tracking WHERE DATE(created_at) = ?");
        $stmt->execute([$d]);
        $days[] = ['date'=>$d, 'count'=>(int)$stmt->fetch()['t']];
    }
    echo json_encode($days);
}
