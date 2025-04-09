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
    $problem_id = isset($_POST['problem_id']) ? (int)$_POST['problem_id'] : 0;
    $solution_id = isset($_POST['solution_id']) ? (int)$_POST['solution_id'] : 0;
    $solution_code = trim($_POST['solution_code']);
    $explanation = trim($_POST['explanation']);
    $price_amount = isset($_POST['price']) ? (float)$_POST['price'] : 0.00;

    // Validation...

    // Insérer ou mettre à jour le prix
    if ($solution_id > 0) {
        // Vérifier si un prix existe déjà pour cette solution
        $stmt = $pdo->prepare("SELECT price_id FROM solutions WHERE id = ?");
        $stmt->execute([$solution_id]);
        $existing_price_id = $stmt->fetchColumn();
        
        if ($existing_price_id) {
            // Mettre à jour le prix existant
            $stmt = $pdo->prepare("UPDATE prices SET amount = ? WHERE price_id = ?");
            $stmt->execute([$price_amount, $existing_price_id]);
            $price_id = $existing_price_id;
        } else {
            // Créer un nouveau prix
            $stmt = $pdo->prepare("INSERT INTO prices (amount) VALUES (?)");
            $stmt->execute([$price_amount]);
            $price_id = $pdo->lastInsertId();
            
            // Mettre à jour la solution avec le nouveau price_id
            $stmt = $pdo->prepare("UPDATE solutions SET price_id = ? WHERE id = ?");
            $stmt->execute([$price_id, $solution_id]);
        }
        
        // Mettre à jour la solution
        $stmt = $pdo->prepare("
            UPDATE solutions 
            SET solution_code = ?, explanation = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$solution_code, $explanation, $solution_id, $user_id]);
    } else {
        // Créer un nouveau prix
        $stmt = $pdo->prepare("INSERT INTO prices (amount) VALUES (?)");
        $stmt->execute([$price_amount]);
        $price_id = $pdo->lastInsertId();
        
        // Créer une nouvelle solution
        $stmt = $pdo->prepare("
            INSERT INTO solutions (problem_id, user_id, solution_code, explanation, status, price_id, created_at)
            VALUES (?, ?, ?, ?, 'pending', ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$problem_id, $user_id, $solution_code, $explanation, $price_id]);
    }
    
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
