<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

try {
    $conn = connect();
    if (!$conn) {
        throw new Exception("Erreur de connexion à la base de données");
    }

    $user = getCurrentUser();
    $input = json_decode(file_get_contents('php://input'), true);
    
    if ($input['action'] === 'mark_seen') {
        // Marquer toutes les notifications comme vues (mais pas nécessairement lues)
        $stmt = $conn->prepare("
            UPDATE notifications 
            SET seen_at = CURRENT_TIMESTAMP 
            WHERE user_id = ? AND seen_at IS NULL
        ");
        $stmt->execute([$user['id']]);
        
        echo json_encode(['success' => true, 'message' => 'Notifications marquées comme vues']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }

} catch (Exception $e) {
    error_log("Erreur mark_notifications_seen: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?>