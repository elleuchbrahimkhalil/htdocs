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

if ($problemId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de problème invalide']);
    exit;
}

try {
    // Utiliser la même variable $pdo que dans favorites.php
    // Vérifier d'abord si $pdo existe, sinon créer la connexion
    if (!isset($pdo)) {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    
    if ($action === 'add') {
        // Vérifier si le favori existe déjà
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ? AND problem_id = ?");
        $stmt->execute([$user['id'], $problemId]);
        
        if ($stmt->fetchColumn() == 0) {
            // Ajouter aux favoris
            $stmt = $pdo->prepare("INSERT INTO favorites (user_id, problem_id, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$user['id'], $problemId]);
            
            echo json_encode(['success' => true, 'message' => 'Ajouté aux favoris', 'action' => 'added']);
        } else {
            echo json_encode(['success' => true, 'message' => 'Déjà dans les favoris', 'action' => 'already_exists']);
        }
        
    } elseif ($action === 'remove') {
        // Retirer des favoris
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND problem_id = ?");
        $stmt->execute([$user['id'], $problemId]);
        
        echo json_encode(['success' => true, 'message' => 'Retiré des favoris', 'action' => 'removed']);
        
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action invalide']);
    }
    
} catch (Exception $e) {
    // Log l'erreur pour le débogage
    error_log("Erreur dans manage_favorites.php: " . $e->getMessage());
    error_log("User ID: " . $user['id'] . ", Problem ID: " . $problemId . ", Action: " . $action);
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur serveur: ' . $e->getMessage(),
        'debug' => [
            'user_id' => $user['id'],
            'problem_id' => $problemId,
            'action' => $action
        ]
    ]);
}
?>
