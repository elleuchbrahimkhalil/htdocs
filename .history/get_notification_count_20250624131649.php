<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $pdo = connect();
    
    if (!$pdo) {
        throw new Exception("Erreur de connexion à la base de données");
    }
    
    // Compter les solutions reçues en attente
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM solutions s
        JOIN problems p ON s.problem_id = p.problem_id
        WHERE p.user_id = ? AND s.user_id != ? AND s.status = 'pending'
    ");
    $stmt->execute([$user_id, $user_id]);
    $received_count = $stmt->fetchColumn();
    
    // Compter les solutions soumises avec des mises à jour récentes
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM solutions s
        WHERE s.user_id = ? AND s.status IN ('accepted', 'rejected', 'paid')
        AND s.updated_at > s.created_at
        AND s.updated_at > COALESCE(
            (SELECT last_seen FROM user_notifications WHERE user_id = ? LIMIT 1),
            '1970-01-01'
        )
    ");
    $stmt->execute([$user_id, $user_id]);
    $submitted_count = $stmt->fetchColumn();
    
    $total_count = $received_count + $submitted_count;
    
    echo json_encode([
        'success' => true,
        'count' => (int)$total_count,
        'received_count' => (int)$received_count,
        'submitted_count' => (int)$submitted_count
    ]);
    
} catch (Exception $e) {
    error_log("Erreur dans get_notification_count.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?>