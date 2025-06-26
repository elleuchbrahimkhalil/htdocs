<?php
session_start();
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

// Récupérer l'ID utilisateur
$user = getCurrentUser();
$user_id = $user['id'];

// Vérifier et nettoyer les données du formulaire
$problem_id = isset($_POST['problem_id']) ? (int)$_POST['problem_id'] : 0;
$solution_id = isset($_POST['solution_id']) ? (int)$_POST['solution_id'] : 0;
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
    $conn = connect();
    if (!$conn) {
        throw new Exception("Erreur de connexion à la base de données");
    }
    
    $conn->beginTransaction();
    
    if ($solution_id > 0) {
        // Mise à jour d'une solution existante
        
        // Vérifier si un prix existe déjà pour cette solution
        $stmt = $conn->prepare("SELECT price_id FROM solutions WHERE id = ? AND user_id = ?");
        $stmt->execute([$solution_id, $user_id]);
        $existing_price_id = $stmt->fetchColumn();
        
        if ($existing_price_id) {
            // Mettre à jour le prix existant
            $stmt = $conn->prepare("UPDATE prices SET amount = ? WHERE price_id = ?");
            $stmt->execute([$price, $existing_price_id]);
            $price_id = $existing_price_id;
        } else {
            // Créer un nouveau prix
            $stmt = $conn->prepare("INSERT INTO prices (amount) VALUES (?)");
            $stmt->execute([$price]);
            $price_id = $conn->lastInsertId();
            
            // Mettre à jour la solution avec le nouveau price_id
            $stmt = $conn->prepare("UPDATE solutions SET price_id = ? WHERE id = ? AND user_id = ?");
            $stmt->execute([$price_id, $solution_id, $user_id]);
        }
        
        // Mettre à jour la solution
        $stmt = $conn->prepare("
            UPDATE solutions 
            SET solution_code = ?, explanation = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$solution_code, $explanation, $solution_id, $user_id]);
        
        $message = "Votre solution a été mise à jour avec succès!";
        
    } else {
        // Création d'une nouvelle solution
        
        // Vérifier si l'utilisateur a déjà soumis une solution pour ce problème
        $stmt = $conn->prepare("SELECT id FROM solutions WHERE problem_id = ? AND user_id = ?");
        $stmt->execute([$problem_id, $user_id]);
        $existing_solution_id = $stmt->fetchColumn();
        
        if ($existing_solution_id) {
            // Une solution existe déjà, la mettre à jour au lieu de créer une nouvelle
            $solution_id = $existing_solution_id;
            
            // Récupérer le price_id existant
            $stmt = $conn->prepare("SELECT price_id FROM solutions WHERE id = ?");
            $stmt->execute([$solution_id]);
            $existing_price_id = $stmt->fetchColumn();
            
            if ($existing_price_id) {
                // Mettre à jour le prix existant
                $stmt = $conn->prepare("UPDATE prices SET amount = ? WHERE price_id = ?");
                $stmt->execute([$price, $existing_price_id]);
            } else {
                // Créer un nouveau prix
                $stmt = $conn->prepare("INSERT INTO prices (amount) VALUES (?)");
                $stmt->execute([$price]);
                $price_id = $conn->lastInsertId();
                
                // Mettre à jour la solution avec le nouveau price_id
                $stmt = $conn->prepare("UPDATE solutions SET price_id = ? WHERE id = ?");
                $stmt->execute([$price_id, $solution_id]);
            }
            
            // Mettre à jour la solution
            $stmt = $conn->prepare("
                UPDATE solutions 
                SET solution_code = ?, explanation = ?, status = 'pending', updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$solution_code, $explanation, $solution_id]);
            
            $message = "Votre solution a été mise à jour avec succès!";
        } else {
            // Créer un nouveau prix
            $stmt = $conn->prepare("INSERT INTO prices (amount) VALUES (?)");
            $stmt->execute([$price]);
            $price_id = $conn->lastInsertId();
            
            // Créer une nouvelle solution
            $stmt = $conn->prepare("
                INSERT INTO solutions (problem_id, user_id, solution_code, explanation, status, price_id, created_at)
                VALUES (?, ?, ?, ?, 'pending', ?, CURRENT_TIMESTAMP)
            ");
            $stmt->execute([$problem_id, $user_id, $solution_code, $explanation, $price_id]);
            
            $message = "Votre solution a été soumise avec succès!";
        }
    }
    
    $conn->commit();
    
    // Marquer que la solution a été soumise pour ce problème
    $_SESSION['solution_submitted'] = true;
    $_SESSION['submitted_problem_id'] = $problem_id;
    $_SESSION['success_message'] = $message;
    
    header('Location: problem.php?id=' . $problem_id);
    exit;
    
} catch (PDOException $e) {
    if ($conn) {
        $conn->rollBack();
    }
    error_log("Erreur dans process_solution.php: " . $e->getMessage());
    $_SESSION['error_message'] = "Une erreur de base de données est survenue. Veuillez réessayer.";
    
    // Conserver les données du formulaire en cas d'erreur
    $_SESSION['old_solution_code'] = $solution_code;
    $_SESSION['old_explanation'] = $explanation;
    $_SESSION['old_price'] = $price;
    
    header('Location: submit_solution.php?problem_id=' . $problem_id);
    exit;
} catch (Exception $e) {
    if ($conn) {
        $conn->rollBack();
    }
    error_log("Erreur générale dans process_solution.php: " . $e->getMessage());
    $_SESSION['error_message'] = "Une erreur est survenue: " . $e->getMessage();
    
    // Conserver les données du formulaire en cas d'erreur
    $_SESSION['old_solution_code'] = $solution_code;
    $_SESSION['old_explanation'] = $explanation;
    $_SESSION['old_price'] = $price;
    
    header('Location: submit_solution.php?problem_id=' . $problem_id);
    exit;
}
?>
