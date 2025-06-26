<?php
// Démarrer la session
session_start();

// Inclure les fichiers nécessaires
require_once 'verification.php';
require_once 'db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    $_SESSION['error_message'] = "Vous devez être connecté pour soumettre une solution";
    header('Location: exlogin.php');
    exit;
}

// Vérifier si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Méthode non autorisée";
    header('Location: exacueil.php');
    exit;
}

// Récupérer l'utilisateur connecté
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
    $errors['problem_id'] = ["ID de problème invalide"];
}

if (empty($solution_code)) {
    $errors['solution_code'] = ["Le code de solution est obligatoire"];
}

if (strlen($solution_code) > 10000) {
    $errors['solution_code'] = ["Le code de solution est trop long (max 10000 caractères)"];
}

if (strlen($explanation) > 2000) {
    $errors['explanation'] = ["L'explication est trop longue (max 2000 caractères)"];
}

if ($price <= 0) {
    $errors['price'] = ["Le prix doit être supérieur à 0"];
}

if ($price > 10000) {
    $errors['price'] = ["Le prix ne peut pas dépasser 10000€"];
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
    
    // Vérifier que le problème existe et que l'utilisateur n'en est pas l'auteur
    $stmt = $conn->prepare("SELECT user_id, title FROM problems WHERE problem_id = ?");
    $stmt->execute([$problem_id]);
    $problem = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$problem) {
        throw new Exception("Le problème spécifié n'existe pas");
    }
    
    if ($problem['user_id'] == $user_id) {
        throw new Exception("Vous ne pouvez pas soumettre une solution à votre propre problème");
    }
    
    // Commencer une transaction
    $conn->beginTransaction();
    
    try {
        // Créer le prix
        $stmt = $conn->prepare("INSERT INTO prices (amount, created_at) VALUES (?, CURRENT_TIMESTAMP)");
        $stmt->execute([$price]);
        $price_id = $conn->lastInsertId();
        
        if ($solution_id > 0) {
            // Vérifier que la solution appartient bien à l'utilisateur
            $stmt = $conn->prepare("SELECT id, price_id FROM solutions WHERE id = ? AND user_id = ? AND problem_id = ?");
            $stmt->execute([$solution_id, $user_id, $problem_id]);
            $existing_solution = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$existing_solution) {
                throw new Exception("Solution non trouvée ou vous n'avez pas les droits pour la modifier");
            }
            
            // Supprimer l'ancien prix s'il existe
            if ($existing_solution['price_id']) {
                $stmt = $conn->prepare("DELETE FROM prices WHERE price_id = ?");
                $stmt->execute([$existing_solution['price_id']]);
            }
            
            // Mettre à jour la solution existante
            $stmt = $conn->prepare("
                UPDATE solutions 
                SET solution_code = ?, explanation = ?, price_id = ?, status = 'pending', updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$solution_code, $explanation, $price_id, $solution_id]);
            
            $success_message = "Votre solution a été mise à jour avec succès!";
            
        } else {
            // Vérifier qu'il n'y a pas déjà une solution de cet utilisateur pour ce problème
            $stmt = $conn->prepare("SELECT id FROM solutions WHERE problem_id = ? AND user_id = ?");
            $stmt->execute([$problem_id, $user_id]);
            if ($stmt->fetch()) {
                throw new Exception("Vous avez déjà soumis une solution pour ce problème");
            }
            
            // Créer une nouvelle solution
            $stmt = $conn->prepare("
                INSERT INTO solutions (problem_id, user_id, solution_code, explanation, price_id, status, created_at)
                VALUES (?, ?, ?, ?, ?, 'pending', CURRENT_TIMESTAMP)
            ");
            $stmt->execute([$problem_id, $user_id, $solution_code, $explanation, $price_id]);
            
            $success_message = "Votre solution a été soumise avec succès!";
        }
        
        // Valider la transaction
        $conn->commit();
        
        // Marquer que la solution a été soumise pour ce problème
        $_SESSION['solution_submitted'] = true;
        $_SESSION['submitted_problem_id'] = $problem_id;
        
        // Message de succès
        $_SESSION['success_message'] = $success_message . " Elle est maintenant en attente d'évaluation par l'auteur du problème.";
        
        // Redirection vers la page du problème
        header('Location: problem.php?id=' . $problem_id);
        exit;
        
    } catch (Exception $e) {
        // Annuler la transaction en cas d'erreur
        $conn->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log("Erreur PDO dans process_solution.php: " . $e->getMessage());
    $_SESSION['error_message'] = "Une erreur de base de données est survenue. Veuillez réessayer.";
    
    // Conserver les données du formulaire en cas d'erreur
    $_SESSION['old_solution_code'] = $solution_code;
    $_SESSION['old_explanation'] = $explanation;
    $_SESSION['old_price'] = $price;
    
} catch (Exception $e) {
    error_log("Erreur dans process_solution.php: " . $e->getMessage());
    $_SESSION['error_message'] = $e->getMessage();
    
    // Conserver les données du formulaire en cas d'erreur
    $_SESSION['old_solution_code'] = $solution_code;
    $_SESSION['old_explanation'] = $explanation;
    $_SESSION['old_price'] = $price;
}

// Redirection en cas d'erreur
header('Location: submit_solution.php?problem_id=' . $problem_id);
exit;
?>

Copy

Apply

process_solution.php
7. Correction de process_problem.php
<?php
session_start();
require_once 'db_connect.php';
require_once 'verification.php';

if (!isLoggedIn()) {
    $_SESSION['error_message'] = "Vous devez être connecté";
    header('Location: exlogin.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Méthode non autorisée";
    header('Location: expublier.php');
    exit;
}

// Récupérer l'utilisateur connecté
$user = getCurrentUser();
$user_id = $user['id'];

// Conversion des difficultés texte → string (garder cohérent avec la base)
$validDifficulties = ['easy', 'medium', 'hard'];

// Récupération et validation des données
$errors = [];
$data = [
    'title' => trim($_POST['title'] ?? ''),
    'description' => trim($_POST['description'] ?? ''),
    'language' => trim($_POST['language'] ?? ''),
    'code' => trim($_POST['code'] ?? ''),
    'difficulty' => trim($_POST['difficulty'] ?? 'medium'),
    'tags' => trim($_POST['tags'] ?? ''),
    'solution' => trim($_POST['solution'] ?? ''),
    'points' => intval($_POST['points'] ?? 10),
    'user_id' => $user_id
];

// Validation
if (empty($data['title'])) {
    $errors['title'] = ["Le titre est obligatoire"];
} elseif (strlen($data['title']) > 255) {
    $errors['title'] = ["Le titre ne doit pas dépasser 255 caractères"];
}

if (empty($data['description'])) {
    $errors['description'] = ["La description est obligatoire"];
} elseif (strlen($data['description']) > 5000) {
    $errors['description'] = ["La description ne doit pas dépasser 5000 caractères"];
}

if (empty($data['language'])) {
    $errors['language'] = ["Le langage est obligatoire"];
}

if (empty($data['solution'])) {
    $errors['solution'] = ["La solution est obligatoire"];
} elseif (strlen($data['solution']) > 10000) {
    $errors['solution'] = ["La solution ne doit pas dépasser 10000 caractères"];
}

if (!in_array($data['difficulty'], $validDifficulties)) {
    $errors['difficulty'] = ["Difficulté invalide"];
}

if ($data['points'] < 1 || $data['points'] > 100) {
    $errors['points'] = ["Les points doivent être entre 1 et 100"];
}

if (strlen($data['code']) > 10000) {
    $errors['code'] = ["Le code ne doit pas dépasser 10000 caractères"];
}

if (strlen($data['tags']) > 500) {
    $errors['tags'] = ["Les tags ne doivent pas dépasser 500 caractères"];
}

// Si erreurs, rediriger avec les données
if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_data'] = $data;
    header('Location: expublier.php');
    exit;
}

error_log("Tentative d'insertion du problème: " . json_encode($data));

// Enregistrement en base de données
try {
    $conn = connect();
    
    if (!$conn) {
        throw new Exception("Erreur de connexion à la base de données");
    }
    
    // Commencer une transaction
    $conn->beginTransaction();
    
    try {
        $stmt = $conn->prepare("
            INSERT INTO problems (
                title, description, language, code, difficulty, tags, solution, points, user_id, created_at
            ) VALUES (
                :title, :description, :language, :code, :difficulty, :tags, :solution, :points, :user_id, CURRENT_TIMESTAMP
            )
        ");
        
        $result = $stmt->execute([
            ':title' => $data['title'],
            ':description' => $data['description'],
            ':language' => $data['language'],
            ':code' => $data['code'],
            ':difficulty' => $data['difficulty'],
            ':tags' => $data['tags'],
            ':solution' => $data['solution'],
            ':points' => $data['points'],
            ':user_id' => $data['user_id']
        ]);
        
        if ($result) {
            $problem_id = $conn->lastInsertId();
            
            error_log("Problème inséré avec succès, ID: " . $problem_id);
            
            // Mettre à jour les stats utilisateur
            $stmt = $conn->prepare("UPDATE users SET problems_posted = problems_posted + 1 WHERE id = ?");
            $stmt->execute([$data['user_id']]);
            
            // Valider la transaction
            $conn->commit();
            
            // Messages de succès
            $_SESSION['success_message'] = "Problème publié avec succès!";
            
            // Nettoyer les données de formulaire en cas de succès
            unset($_SESSION['form_data']);
            unset($_SESSION['form_errors']);
            
            header("Location: problem.php?id=$problem_id");
            exit;
        }
        
        throw new Exception("Erreur lors de l'insertion");
        
    } catch (Exception $e) {
        // Annuler la transaction
        $conn->rollBack();
        throw $e;
    }
    
} catch (PDOException $e) {
    error_log("Erreur PDO dans process_problem.php: " . $e->getMessage());
    $_SESSION['form_errors'] = [
        'general' => ["Erreur technique lors de l'enregistrement. Veuillez réessayer."]
    ];
    $_SESSION['form_data'] = $data;
    
} catch (Exception $e) {
    error_log("Erreur dans process_problem.php: " . $e->getMessage());
    $_SESSION['form_errors'] = [
        'general' => [$e->getMessage()]
    ];
    $_SESSION['form_data'] = $data;
}

// Redirection en cas d'erreur
header('Location: expublier.php');
exit;
?>