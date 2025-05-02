<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    $response = [
        'success' => false,
        'message' => 'Vous devez être connecté pour gérer vos favoris',
        'redirect' => 'exlogin.php'
    ];
    
    if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    $_SESSION['error_message'] = $response['message'];
    header('Location: exlogin.php');
    exit;
}

error_log("Received problem_id: " . $_GET['problem_id']); // Debugging line
// Vérifier si l'ID du problème est fourni

if (!isset($_GET['problem_id']) || !is_numeric($_GET['problem_id'])) {
    $response = [
        'success' => false,
        'message' => 'ID de problème invalide ou manquant'
    ];
    
    if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    $_SESSION['error_message'] = $response['message'];
    header('Location: exacueil.php');
    exit;
}

$problem_id = (int)$_GET['problem_id'];
$user_id = $_SESSION['user_id'];
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'exacueil.php';

try {
    $pdo = connect();
    
    if (!$pdo) {
        throw new Exception("Erreur de connexion à la base de données");
    }
    
    error_log("Checking existence of problem_id: " . $problem_id); // Debugging line
    // Vérifier d'abord si le problème existe
    $stmt_check = $pdo->prepare("SELECT problem_id FROM problems WHERE problem_id = :problem_id");
    $stmt_check->bindValue(':problem_id', $problem_id, PDO::PARAM_INT);
    $stmt_check->execute();
    
    if (!$stmt_check->fetch()) {
        throw new Exception("Le problème demandé n'existe pas");
    }
    
    // Vérifier si le problème est déjà dans les favoris
    $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = :user_id AND problem_id = :problem_id");
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':problem_id', $problem_id, PDO::PARAM_INT);
    $stmt->execute();
    $favorite = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($favorite) {
        // Le problème est déjà en favori, on le supprime
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE id = :id");
        $stmt->bindValue(':id', $favorite['id'], PDO::PARAM_INT);
        $stmt->execute();
        $message = "Problème retiré des favoris";
        $action = "removed";
    } else {
        // Le problème n'est pas en favori, on l'ajoute
        $stmt = $pdo->prepare("INSERT INTO favorites (user_id, problem_id, created_at) VALUES (:user_id, :problem_id, GETDATE())");
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':problem_id', $problem_id, PDO::PARAM_INT);
        $stmt->execute();
        $message = "Problème ajouté aux favoris";
        $action = "added";
    }
    
    // Compter le nombre total de favoris de l'utilisateur
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ?");
    $stmt_count->execute([$user_id]);
    $favorites_count = $stmt_count->fetchColumn();
    
    $response = [
        'success' => true,
        'message' => $message,
        'action' => $action,
        'favorites_count' => $favorites_count
    ];
    
    if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    $_SESSION['success_message'] = $message;
    
    // Rediriger vers la page d'origine avec l'ID du problème si nécessaire
    if (strpos($redirect, 'problem.php') !== false && !strpos($redirect, 'id=')) {
        $redirect .= (strpos($redirect, '?') !== false ? '&' : '?') . 'id=' . $problem_id;
    }
    
    header('Location: ' . $redirect);
    exit;
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Erreur lors de la mise à jour des favoris: ' . $e->getMessage()
    ];
    
    if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    $_SESSION['error_message'] = $response['message'];
    header('Location: ' . $redirect);
    exit;
}
