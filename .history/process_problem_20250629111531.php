<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    $_SESSION['error_message'] = "Vous devez être connecté pour publier un problème";
    header('Location: login.php');
    exit;
}

// Vérifier si c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: expublier.php');
    exit;
}

$user = getCurrentUser();
$errors = [];
$form_data = [];

// Récupérer et valider les données du formulaire
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$language = trim($_POST['language'] ?? '');
$code = trim($_POST['code'] ?? '');
$tags = trim($_POST['tags'] ?? '');
$solution = trim($_POST['solution'] ?? '');

// Validation
if (empty($title)) {
    $errors['title'][] = "Le titre est requis";
} elseif (strlen($title) < 5) {
    $errors['title'][] = "Le titre doit contenir au moins 5 caractères";
} elseif (strlen($title) > 200) {
    $errors['title'][] = "Le titre ne peut pas dépasser 200 caractères";
}

if (empty($description)) {
    $errors['description'][] = "La description est requise";
} elseif (strlen($description) < 50) {
    $errors['description'][] = "La description doit contenir au moins 50 caractères";
}

if (empty($language)) {
    $errors['language'][] = "Le langage de programmation est requis";
}

if (empty($solution)) {
    $errors['solution'][] = "La solution attendue est requise";
} elseif (strlen($solution) < 20) {
    $errors['solution'][] = "La solution doit contenir au moins 20 caractères";
}

// Sauvegarder les données du formulaire pour les réafficher en cas d'erreur
$form_data = [
    'title' => $title,
    'description' => $description,
    'language' => $language,
    'code' => $code,
    'tags' => $tags,
    'solution' => $solution
];

// S'il y a des erreurs, rediriger avec les erreurs
if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_data'] = $form_data;
    header('Location: expublier.php');
    exit;
}

try {
    $conn = connect();
    if (!$conn) {
        throw new Exception("Erreur de connexion à la base de données");
    }

    // Analyse IA simulée pour déterminer la difficulté et les points
    $difficulty = 'medium'; // Valeur par défaut
    $points = 15; // Valeur par défaut

    // Logique d'analyse simple basée sur le code et la description
    $codeLength = strlen($code);
    $descriptionComplexity = str_word_count($description);
    
    // Mots-clés indiquant la complexité
    $hardKeywords = ['recursion', 'dynamic programming', 'graph', 'tree traversal', 'backtracking'];
    $easyKeywords = ['loop', 'array', 'string', 'basic'];
    
    $contentToAnalyze = strtolower($description . ' ' . $code);
    
    $hardCount = 0;
    $easyCount = 0;
    
    foreach ($hardKeywords as $keyword) {
        if (strpos($contentToAnalyze, $keyword) !== false) {
            $hardCount++;
        }
    }
    
    foreach ($easyKeywords as $keyword) {
        if (strpos($contentToAnalyze, $keyword) !== false) {
            $easyCount++;
        }
    }

    // Déterminer la difficulté
    if ($hardCount > 0 || $codeLength > 500 || $descriptionComplexity > 100) {
        $difficulty = 'hard';
        $points = 25;
    } elseif ($easyCount > $hardCount && $codeLength < 200 && $descriptionComplexity < 50) {
        $difficulty = 'easy';
        $points = 10;
    } else {
        $difficulty = 'medium';
        $points = 15;
    }

    // Insérer le problème dans la base de données (SQL Server)
    $sql = "INSERT INTO problems (user_id, title, description, language, code, difficulty, tags, solution, points, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE())";
    
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([
        $user['id'],
        $title,
        $description,
        $language,
        $code,
        $difficulty,
        $tags,
        $solution,
        $points
    ]);

    if ($result) {
        $_SESSION['success_message'] = "Problème publié avec succès! Difficulté: " . ucfirst($difficulty) . ", Points: " . $points;
        header('Location: exacueil.php');
    } else {
        throw new Exception("Erreur lors de l'insertion du problème");
    }

} catch (Exception $e) {
    error_log("Erreur lors de la publication du problème: " . $e->getMessage());
    $_SESSION['form_errors'] = ['general' => ["Erreur lors de la publication: " . $e->getMessage()]];
    $_SESSION['form_data'] = $form_data;
    header('Location: expublier.php');
}
exit;
?>
