<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

// Headers pour les réponses JSON et POST
header('Content-Type: application/json');

// Vérifier la connexion de l'utilisateur
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

// Récupérer les informations de l'utilisateur
$user = getCurrentUser();

// Vérifier les paramètres
if (!isset($_POST['problem_id']) || !isset($_POST['favorite_action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit;
}

$problem_id = (int)$_POST['problem_id'];
$action = $_POST['favorite_action'];

// Valider l'action
if (!in_array($action, ['add', 'remove'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Action invalide']);
    exit;
}

// Valider l'ID du problème
if ($problem_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID de problème invalide']);
    exit;
}

try {
    $pdo = connect();
    
    if (!$pdo) {
        throw new Exception("Erreur de connexion à la base de données");
    }
    
    // Vérifier que le problème existe
    $stmt = $pdo->prepare("SELECT problem_id FROM problems WHERE problem_id = ?");
    $stmt->execute([$problem_id]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Problème non trouvé']);
        exit;
    }
    
    if ($action === 'add') {
        // Ajouter aux favoris (éviter les doublons)
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO favorites (user_id, problem_id, created_at) 
            VALUES (?, ?, CURRENT_TIMESTAMP)
        ");
        $result = $stmt->execute([$user['id'], $problem_id]);
        
        if ($result) {
            // Compter le nouveau nombre de favoris
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE problem_id = ?");
            $stmt->execute([$problem_id]);
            $favorite_count = $stmt->fetchColumn();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Ajouté aux favoris',
                'action' => 'added',
                'favorite_count' => (int)$favorite_count
            ]);
        } else {
            throw new Exception("Erreur lors de l'ajout aux favoris");
        }
        
    } elseif ($action === 'remove') {
        // Retirer des favoris
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND problem_id = ?");
        $result = $stmt->execute([$user['id'], $problem_id]);
        
        if ($result) {
            // Compter le nouveau nombre de favoris
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE problem_id = ?");
            $stmt->execute([$problem_id]);
            $favorite_count = $stmt->fetchColumn();
            
            echo json_encode([
                'success' => true, 
                'message' => 'Retiré des favoris',
                'action' => 'removed',
                'favorite_count' => (int)$favorite_count
            ]);
        } else {
            throw new Exception("Erreur lors de la suppression des favoris");
        }
    }
    
} catch (PDOException $e) {
    error_log("Erreur PDO dans manage_favorites.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur de base de données']);
} catch (Exception $e) {
    error_log("Erreur dans manage_favorites.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?>
