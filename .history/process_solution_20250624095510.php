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
