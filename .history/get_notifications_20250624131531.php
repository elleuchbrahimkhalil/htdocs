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
    
    // Récupérer les notifications récentes
    $notifications = [];
    
    // 1. Solutions soumises pour les problèmes de l'utilisateur (en attente)
    $stmt = $pdo->prepare("
        SELECT TOP 10
            s.id as related_id,
            'solution_submitted' as type,
            'Nouvelle solution soumise' as title,
            CONCAT('Une solution a été soumise pour \"', p.title, '\" par ', u.name) as message,
            s.created_at,
            0 as is_read
        FROM solutions s
        JOIN problems p ON s.problem_id = p.problem_id
        JOIN users u ON s.user_id = u.id
        WHERE p.user_id = ? AND s.user_id != ? AND s.status = 'pending'
        ORDER BY s.created_at DESC
        
    ");
    $stmt->execute([$user_id, $user_id]);
    $solution_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. Mises à jour des solutions soumises par l'utilisateur
    $stmt = $pdo->prepare("
        SELECT 
            s.id as related_id,
            CASE 
                WHEN s.status = 'accepted' THEN 'solution_accepted'
                WHEN s.status = 'rejected' THEN 'solution_rejected'
                WHEN s.status = 'paid' THEN 'payment_received'
                ELSE 'solution_updated'
            END as type,
            CASE 
                WHEN s.status = 'accepted' THEN 'Solution acceptée'
                WHEN s.status = 'rejected' THEN 'Solution rejetée'
                WHEN s.status = 'paid' THEN 'Paiement reçu'
                ELSE 'Solution mise à jour'
            END as title,
            CASE 
                WHEN s.status = 'accepted' THEN CONCAT('Votre solution pour \"', p.title, '\" a été acceptée')
                WHEN s.status = 'rejected' THEN CONCAT('Votre solution pour \"', p.title, '\" a été rejetée')
                WHEN s.status = 'paid' THEN CONCAT('Vous avez reçu le paiement pour \"', p.title, '\"')
                ELSE CONCAT('Votre solution pour \"', p.title, '\" a été mise à jour')
            END as message,
            s.updated_at as created_at,
            0 as is_read
        FROM solutions s
        JOIN problems p ON s.problem_id = p.problem_id
        WHERE s.user_id = ? AND s.status IN ('accepted', 'rejected', 'paid')
        AND s.updated_at > s.created_at
        ORDER BY s.updated_at DESC
        
    ");
    $stmt->execute([$user_id]);
    $status_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Combiner et trier les notifications
    $notifications = array_merge($solution_notifications, $status_notifications);
    
    // Trier par date décroissante
    usort($notifications, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    // Limiter à 15 notifications
    $notifications = array_slice($notifications, 0, 15);
    
    // Ajouter des IDs uniques pour les notifications
    foreach ($notifications as &$notification) {
        $notification['id'] = $notification['type'] . '_' .         $notification['id'] = $notification['type'] . '_' . $notification['related_id'] . '_' . strtotime($notification['created_at']);
    }
    
    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'count' => count($notifications)
    ]);
    
} catch (Exception $e) {
    error_log("Erreur dans get_notifications.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?>
