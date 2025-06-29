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
    
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$user['id']]);
    
    echo json_encode(['success' => true, 'message' => 'Notifications marquées comme lues']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>