<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

// Headers pour les réponses JSON
header('Content-Type: application/json');

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

// Validation de l'action
if (!in_array($action, ['add', 'remove'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action invalide']);
    exit;
}

try {
    $pdo = connect();
    
    if (!$pdo) {
        throw new Exception("Erreur de connexion à la base de données");
    }
    
    if ($action === 'add') {
        // Ajouter aux favoris (éviter les doublons)
        $stmt = $pdo->prepare("INSERT IGNORE INTO favorites (user_id, problem_id, created_at) VALUES (?, ?, NOW())");
        $result = $stmt->execute([$user['id'], $problemId]);
        $message = 'Ajouté aux favoris!';
    } else {
        // Retirer des favoris
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND problem_id = ?");
        $result = $stmt->execute([$user['id'], $problemId]);
        $message = 'Retiré des favoris!';
    }
    
    if ($result) {
        // Récupérer le nouveau nombre de favoris pour ce problème
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE problem_id = ?");
        $stmt->execute([$problemId]);
        $favoriteCount = $stmt->fetchColumn();
        
        // Vérifier si l'utilisateur actuel a ce problème en favori
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE problem_id = ? AND user_id = ?");
        $stmt->execute([$problemId, $user['id']]);
        $isFavorite = $stmt->fetchColumn() > 0;
        
        echo json_encode([
            'success' => true, 
            'message' => $message,
            'favorite_count' => (int)$favoriteCount,
            'is_favorite' => $isFavorite
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
    }
    
} catch (Exception $e) {
    error_log("Erreur toggle_favorite: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?>
