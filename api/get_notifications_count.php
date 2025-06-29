<?php
session_start();
require_once '../verification.php';
require_once '../db_connect.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

try {
    $user = getCurrentUser();
    $conn = connect();
    
    // Compter les notifications
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user['id']]);
    $notifications_count = $stmt->fetch()['count'] ?? 0;
    
    // Compter les paiements non consultés
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM payments WHERE payer_id = ? AND viewed = 0");
    $stmt->execute([$user['id']]);
    $payments_count = $stmt->fetch()['count'] ?? 0;
    
    echo json_encode([
        'success' => true,
        'notifications_count' => $notifications_count,
        'payments_count' => $payments_count
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>