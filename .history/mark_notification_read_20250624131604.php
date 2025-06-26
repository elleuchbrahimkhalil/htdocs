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

// Vérifier la méthode de requête
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Récupérer les données JSON
$input = json_decode(file_get_contents('php://input'), true);
$notification_id = $input['notification_id'] ?? '';

if (empty($notification_id)) {
    echo json_encode(['success' => false, 'message' => 'ID de notification manquant']);
    exit;
}

try {
    $pdo = connect();
    
    if (!$pdo) {
        throw new Exception("Erreur de connexion à la base de données");
    }
    
    // Créer ou mettre à jour l'enregistrement de lecture
    $stmt = $pdo->prepare("
        INSERT INTO notification_reads (user_id, notification_id, read_at)
        VALUES (?, ?, CURRENT_TIMESTAMP)
        ON DUPLICATE KEY UPDATE read_at = CURRENT_TIMESTAMP
    ");
    
    $result = $stmt->execute([$user_id, $notification_id]);
    
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
    }
    
} catch (Exception $e) {
    error_log("Erreur dans mark_notification_read.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?>