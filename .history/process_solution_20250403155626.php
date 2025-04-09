<?php
// Démarrer la session
session_start();

// Inclure les fichiers nécessaires
require_once 'verification.php';
require_once 'db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: exlogin.php');
    exit;
}

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: exacueil.php');
    exit;
}

// Vérifier et nettoyer les données du formulaire
$problem_id = isset($_POST['problem_id']) ? (int)$_POST['problem_id'] : 0;
$solution_code = isset($_POST['solution_code']) ? trim($_POST['solution_code']) : '';
$explanation = isset($_POST['explanation']) ? trim($_POST['explanation']) : '';
$price = isset($_POST['price']) ? (float)$_POST['price'] : 0;

// Valider les données
$errors = [];

if ($problem_id <= 0) {
    $errors['problem_id'] = "ID de problème invalide";
}

if (empty($solution_code)) {
    $errors['solution_code'] = "Le code de solution est obligatoire.";
}

if (strlen($solution_code) > 10000) {
    $errors['solution_code'] = "Le code de solution est trop long (max 10000 caractères)";
}

if (strlen($explanation) > 2000) {
    $errors['explanation'] = "L'explication est trop longue (max 2000 caractères)";
}

if ($price <= 0) {
    $errors['price'] = "Le prix doit être supérieur à 0";
}

// Si des erreurs sont présentes, rediriger vers le formulaire
if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['old_solution_code'] = $solution_code;
    $_SESSION['old_explanation'] = $explanation;
    $_SESSION['old_price'] = $price;
    header('Location: submit_solution.php?problem_id=' . $problem_id);
    exit;
}

// Enregistrer la solution dans la base de données
try {
    $pdo = connect();
    
    // Récupérer les données du formulaire
    $problem_id = $_POST['problem_id'];
    $user_id = $_SESSION['user_id']; // Assurez-vous que cette variable existe
    $solution_code = $_POST['solution_code'];
    $explanation = $_POST['explanation'];
    $price = $_POST['price'];
    $status = 'pending'; // Ou toute autre valeur par défaut
    
    // Requête d'insertion corrigée
    $stmt = $pdo->prepare("INSERT INTO solutions (problem_id, user_id, solution_code, explanation, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$problem_id, $user_id, $solution_code, $explanation, 'pending']);
    
    // Après avoir inséré la solution avec succès
    $_SESSION['solution_submitted'] = true;
    $_SESSION['submitted_problem_id'] = $problem_id;
    
    // Redirection en cas de succès
    $_SESSION['success_message'] = "Votre solution a été soumise avec succès!";
    header('Location: problem.php?id=' . $problem_id);
    exit;
    
} catch (PDOException $e) {
    error_log("Erreur dans process_solution.php: " . $e->getMessage());
    $_SESSION['error_message'] = "Une erreur de base de données est survenue. Veuillez réessayer.";
    
    // Conserver les données du formulaire en cas d'erreur
    $_SESSION['old_solution_code'] = $_POST['solution_code'];
    $_SESSION['old_explanation'] = $_POST['explanation'];
    $_SESSION['old_price'] = $_POST['price'];
    
    header('Location: submit_solution.php?problem_id=' . $_POST['problem_id']);
    exit;
}
