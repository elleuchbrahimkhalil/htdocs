<?php
// Démarrer la session
session_start();

// Inclure les fichiers nécessaires
require_once 'verification.php';
require_once 'db_connect.php';
require_once 'ai_solution_validator_sqlserver.php'; // Inclure le validateur IA

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
    $solution_id = isset($_POST['solution_id']) ? (int)$_POST['solution_id'] : 0;
    $user_id = $_SESSION['user_id'];

    // Insérer ou mettre à jour le prix
    if ($solution_id > 0) {
        // Vérifier si un prix existe déjà pour cette solution
        $stmt = $pdo->prepare("SELECT price_id FROM solutions WHERE id = ?");
        $stmt->execute([$solution_id]);
        $existing_price_id = $stmt->fetchColumn();
        
        if ($existing_price_id) {
            // Mettre à jour le prix existant
            $stmt = $pdo->prepare("UPDATE prices SET amount = ? WHERE price_id = ?");
            $stmt->execute([$price, $existing_price_id]);
            $price_id = $existing_price_id;
        } else {
            // Créer un nouveau prix
            $stmt = $pdo->prepare("INSERT INTO prices (amount) VALUES (?)");
            $stmt->execute([$price]);
            $price_id = $pdo->lastInsertId();
            
            // Mettre à jour la solution avec le nouveau price_id
            $stmt = $pdo->prepare("UPDATE solutions SET price_id = ? WHERE id = ?");
            $stmt->execute([$price_id, $solution_id]);
        }
        
        // Mettre à jour la solution
        $stmt = $pdo->prepare("
            UPDATE solutions 
            SET solution_code = ?, explanation = ?, updated_at = GETDATE()
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$solution_code, $explanation, $solution_id, $user_id]);
        
        $final_solution_id = $solution_id;
    } else {
        // Créer un nouveau prix
        $stmt = $pdo->prepare("INSERT INTO prices (amount) VALUES (?)");
        $stmt->execute([$price]);
        $price_id = $pdo->lastInsertId();
        
        // Créer une nouvelle solution
        $stmt = $pdo->prepare("
            INSERT INTO solutions (problem_id, user_id, solution_code, explanation, status, price_id, created_at)
            VALUES (?, ?, ?, ?, 'pending', ?, GETDATE())
        ");
        $stmt->execute([$problem_id, $user_id, $solution_code, $explanation, $price_id]);
        
        $final_solution_id = $pdo->lastInsertId();
    }
    
    // Après avoir inséré/mis à jour la solution avec succès
    $_SESSION['solution_submitted'] = true;
    $_SESSION['submitted_problem_id'] = $problem_id;
    
    // *** INTÉGRATION IA - ANALYSE AUTOMATIQUE ***
    $ai_analysis_result = null;
    $ai_message = '';
    
    // Vérifier si l'IA est activée
    if (isAIValidationEnabled()) {
        try {
            // Lancer l'analyse IA en arrière-plan
            $ai_analysis_result = analyzeNewSolution($final_solution_id);
            
            if ($ai_analysis_result['success']) {
                $ai_status = $ai_analysis_result['status'];
                $ai_score = $ai_analysis_result['ai_results']['ai_score'] ?? 0;
                
                switch ($ai_status) {
                    case 'ai_passed':
                        $ai_message = "🤖 Excellente nouvelle ! Votre solution a été validée automatiquement par notre IA (Score: $ai_score/100). Elle sera bientôt approuvée !";
                        
                        // Auto-approuver si le score est très élevé
                        if ($ai_score >= 90) {
                            $stmt = $pdo->prepare("
                                UPDATE solutions 
                                SET status = 'approved', 
                                    feedback = CONCAT(ISNULL(feedback, ''), CHAR(13) + CHAR(10) + '🤖 Validation automatique IA - Score excellent: ' + CAST(? AS VARCHAR) + '/100'),
                                    evaluated_at = GETDATE()
                                WHERE id = ?
                            ");
                            $stmt->execute([$ai_score, $final_solution_id]);
                            
                            // Mettre à jour les points de l'utilisateur
                            $stmt = $pdo->prepare("
                                UPDATE users 
                                SET score = score + (SELECT points FROM problems WHERE problem_id = ?),
                                    problems_solved = problems_solved + 1
                                WHERE id = ?
                            ");
                            $stmt->execute([$problem_id, $user_id]);
                            
                            $ai_message = "🎉 Félicitations ! Votre solution a été automatiquement approuvée par notre IA avec un score parfait de $ai_score/100 !";
                        }
                        break;
                        
                    case 'ai_review':
                        $ai_message = "🔍 Votre solution a été analysée par notre IA (Score: $ai_score/100). Elle nécessite une révision humaine avant validation.";
                        break;
                        
                    case 'ai_failed':
                        $ai_message = "⚠️ Notre IA a détecté quelques problèmes dans votre solution (Score: $ai_score/100). Vous pouvez la modifier ou attendre une révision humaine.";
                        break;
                        
                    default:
                        $ai_message = "🤖 Votre solution est en cours d'analyse par notre IA...";
                }
            } else {
                $ai_message = "ℹ️ Votre solution sera analysée prochainement par notre système IA.";
            }
            
        } catch (Exception $e) {
            // L'erreur IA ne doit pas empêcher la soumission
            error_log("Erreur IA lors de la soumission: " . $e->getMessage());
            $ai_message = "ℹ️ Votre solution a été soumise avec succès. L'analyse IA sera effectuée prochainement.";
        }
    }
    
    // Message de succès avec information IA
    $success_message = "Votre solution a été soumise avec succès !";
    if (!empty($ai_message)) {
        $success_message .= "\n\n" . $ai_message;
    }
    
    $_SESSION['success_message'] = $success_message;
    
    // Redirection en cas de succès
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
?>
        // Créer un nouveau prix
