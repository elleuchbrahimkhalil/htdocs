<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

// Vérifier la connexion de l'utilisateur
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

// Récupérer les informations de l'utilisateur
$user = getCurrentUser();

// Vérifier les paramètres
if (!isset($_POST['problem_id']) || !isset($_POST['favorite_action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit;
}

$problemId = (int)$_POST['problem_id'];
$action = $_POST['favorite_action'];

try {
    $pdo = connect();
    
    if ($action === 'add') {
        // Ajouter aux favoris (éviter les doublons)
        $stmt = $pdo->prepare("INSERT IGNORE INTO favorites (user_id, problem_id, created_at) VALUES (?, ?, NOW())");
        $result = $stmt->execute([$user['id'], $problemId]);
        $message = 'Ajouté aux favoris!';
    } else if ($action === 'remove') {
        // Retirer des favoris
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND problem_id = ?");
        $result = $stmt->execute([$user['id'], $problemId]);
        $message = 'Retiré des favoris!';
    } else {
        throw new Exception('Action invalide');
    }
    
    if ($result) {
        // Récupérer le nouveau nombre de favoris
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE problem_id = ?");
        $stmt->execute([$problemId]);
        $favoriteCount = $stmt->fetchColumn();
        
        echo json_encode([
            'success' => true, 
            'message' => $message,
            'favorite_count' => $favoriteCount,
            'is_favorite' => $action === 'add'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
    }
    
} catch (Exception $e) {
    error_log("Erreur toggle_favorite: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>
