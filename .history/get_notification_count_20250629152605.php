<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'count' => 0, 'solution_count' => 0]);
    exit;
}

try {
    $conn = connect();
    if (!$conn) {
        throw new Exception("Erreur de connexion à la base de données");
    }

    $user = getCurrentUser();
    
    // Compter toutes les notifications non lues
    $stmt = $conn->prepare("
        SELECT COUNT(*) as unread_count 
        FROM notifications 
        WHERE user_id = ? AND is_read = 0
    ");
    $stmt->execute([$user['id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $unread_count = (int)($result['unread_count'] ?? 0);
    
    // Compter spécifiquement les notifications de solutions soumises
    $stmt = $conn->prepare("
        SELECT COUNT(*) as solution_notifications 
        FROM notifications 
        WHERE user_id = ? AND is_read = 0 
        AND (
            message LIKE '%solution%' OR 
            message LIKE '%soumis%' OR 
            message LIKE '%résolu%' OR 
            message LIKE '%approuvé%' OR
            message LIKE '%rejeté%' OR
            type = 'solution_submitted' OR
            type = 'solution_approved' OR
            type = 'solution_rejected'
        )
    ");
    $stmt->execute([$user['id']]);
    $solution_result = $stmt->fetch(PDO::FETCH_ASSOC);
    $solution_count = (int)($solution_result['solution_notifications'] ?? 0);
    
    echo json_encode([
        'success' => true, 
        'count' => $unread_count,
        'solution_count' => $solution_count
    ]);

} catch (Exception $e) {
    error_log("Erreur get_notification_count: " . $e->getMessage());
    echo json_encode(['success' => false, 'count' => 0, 'solution_count' => 0]);
}
?>
