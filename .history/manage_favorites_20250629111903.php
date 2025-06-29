<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

$user = getCurrentUser();

// Traitement des requêtes AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $problem_id = isset($_POST['problem_id']) ? (int)$_POST['problem_id'] : 0;
    $action = isset($_POST['favorite_action']) ? $_POST['favorite_action'] : '';
    
    if ($problem_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID problème invalide']);
        exit;
    }
    
    try {
        $conn = connect();
        if (!$conn) {
            throw new Exception("Erreur de connexion à la base de données");
        }
        
        if ($action === 'add') {
            // Vérifier si déjà en favori
            $checkStmt = $conn->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ? AND problem_id = ?");
            $checkStmt->execute([$user['id'], $problem_id]);
            $exists = $checkStmt->fetchColumn();
            
            if ($exists == 0) {
                // Ajouter aux favoris avec GETDATE() pour SQL Server
                $insertStmt = $conn->prepare("INSERT INTO favorites (user_id, problem_id, created_at) VALUES (?, ?, GETDATE())");
                $insertStmt->execute([$user['id'], $problem_id]);
                echo json_encode(['success' => true, 'action' => 'added']);
            } else {
                echo json_encode(['success' => true, 'action' => 'already_exists']);
            }
            
        } elseif ($action === 'remove') {
            // Retirer des favoris
            $deleteStmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND problem_id = ?");
            $deleteStmt->execute([$user['id'], $problem_id]);
            echo json_encode(['success' => true, 'action' => 'removed']);
            
        } else {
            echo json_encode(['success' => false, 'message' => 'Action invalide']);
        }
        
    } catch (Exception $e) {
        error_log("Erreur manage_favorites: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
    }
    exit;
}

// Si ce n'est pas une requête AJAX, rediriger
header('Location: exacueil.php');
exit;
?>
