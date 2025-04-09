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
    
    if (!$pdo) {
        throw new Exception("Erreur de connexion à la base de données");
    }

    // Commencer une transaction
    $pdo->beginTransaction();
    
    // Vérifier si une solution existe déjà
    $check_stmt = $pdo->prepare("
        SELECT id FROM solutions 
        WHERE problem_id = ? AND user_id = ?
    ");
    $check_stmt->execute([$problem_id, $_SESSION['user_id']]);
    $existing_solution = $check_stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing_solution) {
        // Mettre à jour la solution existante
        $update_stmt = $pdo->prepare("
            UPDATE solutions 
            SET solution_code = ?, explanation = ?, status = 'pending', updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $update_stmt->execute([$solution_code, $explanation, $existing_solution['id']]);
        
        // Mettre à jour le prix dans user_corrections
        $check_price_stmt = $pdo->prepare("
            SELECT id FROM user_corrections 
            WHERE solution_id = ?
        ");
        $check_price_stmt->execute([$existing_solution['id']]);
        $existing_price = $check_price_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_price) {
            // Mettre à jour le prix existant
            $update_price_stmt = $pdo->prepare("
                UPDATE user_corrections 
                SET price = ? 
                WHERE solution_id = ?
            ");
            $update_price_stmt->execute([$price, $existing_solution['id']]);
        } else {
            // Insérer un nouveau prix
            $insert_price_stmt = $pdo->prepare("
                INSERT INTO user_corrections (solution_id, price, created_at)
                VALUES (?, ?, CURRENT_TIMESTAMP)
            ");
            $insert_price_stmt->execute([$existing_solution['id'], $price]);
        }
        
        $solution_id = $existing_solution['id'];
    } else {
        // Insérer la nouvelle solution
        $stmt = $pdo->prepare("
            INSERT INTO solutions (problem_id, user_id, solution_code, explanation, status, created_at)
            VALUES (?, ?, ?, ?, 'pending', CURRENT_TIMESTAMP)
        ");
        
        $stmt->execute([
            $problem_id,
            $_SESSION['user_id'],
            $solution_code,
            $explanation
        ]);
        
        $solution_id = $pdo->lastInsertId();
        
        // Insérer le prix dans user_corrections
        $insert_price_stmt = $pdo->prepare("
            INSERT INTO user_corrections (solution_id, price, created_at)
            VALUES (?, ?, CURRENT_TIMESTAMP)
        ");
        $insert_price_stmt->execute([$solution_id, $price]);
    }
    
    // Mettre à jour le statut de résolution de l'utilisateur
    $status_stmt = $pdo->prepare("
        SELECT COUNT(*) FROM user_problem_status 
        WHERE problem_id = ? AND user_id = ?
    ");
    $status_stmt->execute([$problem_id, $_SESSION['user_id']]);
    $status_exists = $status_stmt->fetchColumn() > 0;

    if ($status_exists) {
        // Mettre à jour le statut existant
        $update_status_stmt = $pdo->prepare("
            UPDATE user_problem_status 
            SET status = 'pending' 
            WHERE problem_id = ? AND user_id = ?
        ");
        $update_status_stmt->execute([$problem_id, $_SESSION['user_id']]);
    } else {
        // Insérer un nouveau statut
        $insert_status_stmt = $pdo->prepare("
            INSERT INTO user_problem_status (problem_id, user_id, status)
            VALUES (?, ?, 'pending')
        ");
        $insert_status_stmt->execute([$problem_id, $_SESSION['user_id']]);
    }
    
    // Valider la transaction
    $pdo->commit();
    
    $_SESSION['success_message'] = "Votre solution a été soumise avec succès! Elle sera examinée par l'auteur du problème.";
    header('Location: problem.php?id=' . $problem_id);
    exit;
    
} catch (PDOException $e) {
    // Annuler la transaction en cas d'erreur
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("Erreur dans process_solution.php: " . $e->getMessage());
    die("Erreur SQL: " . $e->getMessage());
    
} catch (Exception $e) {
    error_log("Erreur lors de la soumission de la solution: " . $e->getMessage());
    
    $_SESSION['form_errors'] = [
        'general' => ["Une erreur inattendue est survenue. Veuillez réessayer."]
    ];
    header('Location: submit_solution.php?problem_id=' . $problem_id);
    exit;
}
