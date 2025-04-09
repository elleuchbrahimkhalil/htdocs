<?php
session_start();
require_once 'db_connect.php';

// Vérifier si l'ID du problème est fourni
if (!isset($_GET['problem_id']) || !ctype_digit($_GET['problem_id'])) {
    exit;
}

$problem_id = (int)$_GET['problem_id'];

try {
    $conn = connect();
    
    // Mettre à jour le compteur de vues
    $stmt = $conn->prepare("
        UPDATE problems 
        SET views = ISNULL(views, 0) + 1 
        WHERE problem_id = ?
    ");
    
    $stmt->execute([$problem_id]);
    
    // Répondre avec succès
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    // En cas d'erreur, ne rien faire
    error_log("Erreur lors de la mise à jour des vues: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
