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
    header('Location: problems.php');
    exit;
}

// Vérifier et nettoyer les données du formulaire
$problem_id = isset($_POST['problem_id']) ? (int)$_POST['problem_id'] : 0;
$solution_code = isset($_POST['solution_code']) ? trim($_POST['solution_code']) : '';
$explanation = isset($_POST['explanation']) ? trim($_POST['explanation']) : '';

error_log("Solution Code: " . $solution_code); // Debugging statement
error_log("Explanation: " . $explanation); // Debugging statement
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

// Si des erreurs sont présentes, rediriger vers le formulaire
if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['old_solution_code'] = $solution_code;
    $_SESSION['old_explanation'] = $explanation;
    header('Location: solve_problem.php?id=' . $problem_id);
    exit;
}

// Enregistrer la solution dans la base de données
try {
    $pdo = connect();
    
    if (!$pdo) {
        throw new Exception("Erreur de connexion à la base de données");
    }

    // Commencer une transaction
    $pdo->beginTransaction();
    
    // Vérifier si une solution existe déjà
    $check_stmt = $pdo->prepare("
        SELECT COUNT(*) FROM solutions 
        WHERE problem_id = ? AND user_id = ?
    ");
    $check_stmt->execute([$problem_id, $_SESSION['user_id']]);
    $exists = $check_stmt->fetchColumn() > 0;

    if ($exists) {
        // Mettre à jour la solution existante
        $update_stmt = $pdo->prepare("
            UPDATE solutions 
            SET solution_code = ?, explanation = ?, status = 'pending', updated_at = CURRENT_TIMESTAMP 
            WHERE problem_id = ? AND user_id = ?
        ");
        $update_stmt->execute([$solution_code, $explanation, $problem_id, $_SESSION['user_id']]);
    } else {
        // Insérer la nouvelle solution
        $stmt = $pdo->prepare("
            INSERT INTO solutions (problem_id, user_id, solution_code, explanation, status, created_at)
            VALUES (:problem_id, :user_id, :solution_code, :explanation, 'pending', CURRENT_TIMESTAMP)
        ");
        
        $stmt->execute([
            ':problem_id' => $problem_id,
            ':user_id' => $_SESSION['user_id'],
            ':solution_code' => $solution_code,
            ':explanation' => $explanation
        ]);
    }
    
    // Mettre à jour le statut de résolution de l'utilisateur
    // Check if the status already exists
    $status_stmt = $pdo->prepare("
        SELECT COUNT(*) FROM user_problem_status 
        WHERE problem_id = ? AND user_id = ?
    ");
    $status_stmt->execute([$problem_id, $_SESSION['user_id']]);
    $status_exists = $status_stmt->fetchColumn() > 0;

    if ($status_exists) {
        // Update the existing status
    // Check if the status already exists
    $status_stmt = $pdo->prepare("
        SELECT COUNT(*) FROM user_problem_status 
        WHERE problem_id = ? AND user_id = ?
    ");
    $status_stmt->execute([$problem_id, $_SESSION['user_id']]);
    $status_exists = $status_stmt->fetchColumn() > 0;

    if ($status_exists) {
        // Update the existing status
    // Check if the status already exists
    $status_stmt = $pdo->prepare("
        SELECT COUNT(*) FROM user_problem_status 
        WHERE problem_id = ? AND user_id = ?
    ");
    $status_stmt->execute([$problem_id, $_SESSION['user_id']]);
    $status_exists = $status_stmt->fetchColumn() > 0;

    if ($status_exists) {
        // Update the existing status
        $update_status_stmt = $pdo->prepare("
            UPDATE user_problem_status 
            SET status = 'accepted' 
            WHERE problem_id = ? AND user_id = ?
        ");
        $update_status_stmt->execute([$problem_id, $_SESSION['user_id']]);
    } else {
        // Insert a new status
        $insert_status_stmt = $pdo->prepare("
            INSERT INTO user_problem_status (problem_id, user_id, status)
            VALUES (?, ?, 'accepted')
        ");
        $insert_status_stmt->execute([$problem_id, $_SESSION['user_id']]);
    }

        $insert_status_stmt->execute([$problem_id, $_SESSION['user_id']]);
    }

        $insert_status_stmt->execute([$problem_id, $_SESSION['user_id']]);
    }
    
    // Valider la transaction
    $pdo->commit();
    
    $_SESSION['success_message'] = "Votre solution a été soumise avec succès!";
    header('Location: exacueil.php');
    exit;
    
} catch (PDOException $e) {
    // Annuler la transaction en cas d'erreur
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Erreur PDO lors de la soumission de la solution: " . $e->getMessage());
    
    $_SESSION['form_errors'] = [
        'general' => ["Une erreur de base de données est survenue. Veuillez réessayer."]
    ];
    header('Location: solve_problem.php?id=' . $problem_id);
    exit;
    
} catch (Exception $e) {
    error_log("Erreur lors de la soumission de la solution: " . $e->getMessage());
    
    $_SESSION['form_errors'] = [
        'general' => ["Une erreur inattendue est survenue. Veuillez réessayer."]
    ];
    header('Location: solve_problem.php?id=' . $problem_id);
    exit;
}
