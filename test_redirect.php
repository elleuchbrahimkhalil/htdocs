<?php
// Afficher les erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrer la session
session_start();

// Inclure les fichiers nécessaires
require_once 'verification.php';
require_once 'db_connect.php';

// Vérifier la connexion utilisateur
if (!isLoggedIn()) {
    echo "Vous n'êtes pas connecté";
    exit;
}

// Vérifier l'ID du problème
$problem_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
echo "ID du problème: " . $problem_id . "<br>";

// Tester la connexion à la base de données
try {
    $pdo = connect();
    echo "Connexion réussie<br>";
    
    // Test 1: Requête simple
    echo "Test 1: ";
    $stmt = $pdo->query("SELECT TOP 1 * FROM problems");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "OK<br>";
    
    // Test 2: Requête avec JOIN
    echo "Test 2: ";
    $stmt = $pdo->query("
        SELECT TOP 1 p.*, u.username 
        FROM problems p
        JOIN users u ON p.user_id = u.id
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "OK<br>";
    
    // Test 3: Requête avec paramètres
    echo "Test 3: ";
    $stmt = $pdo->prepare("SELECT TOP 1 * FROM problems WHERE problem_id = ?");
    $stmt->execute([1]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "OK<br>";
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}
