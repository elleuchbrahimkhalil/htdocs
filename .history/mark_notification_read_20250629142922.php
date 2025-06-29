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
    
    if ($input['action'] === 'mark_viewed') {
        // Marquer toutes les notifications comme lues
        $stmt = $conn->prepare("
            UPDATE notifications 
            SET is_read = 1, read_at = NOW() 
            WHERE user_id = ? AND is_read = 0
        ");
        $stmt->execute([$user['id']]);
        
        echo json_encode(['success' => true, 'message' => 'Notifications marquées comme lues']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    }

} catch (Exception $e) {
    error_log("Erreur mark_notifications_read: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?>
