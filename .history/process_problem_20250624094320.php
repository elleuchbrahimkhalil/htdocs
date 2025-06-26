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

// Conversion des difficultés texte → int
$difficultyMap = [
    'easy' => 1,
    'medium' => 2,
    'hard' => 3
];

// Récupération et validation des données
$errors = [];
$data = [
    'title' => trim($_POST['title'] ?? ''),
    'description' => trim($_POST['description'] ?? ''),
    'language' => trim($_POST['language'] ?? ''),
    'code' => trim($_POST['code'] ?? ''),
    'difficulty_text' => trim($_POST['difficulty'] ?? 'medium'),
    'tags' => trim($_POST['tags'] ?? ''),
    'solution' => trim($_POST['solution'] ?? ''),
    'points' => intval($_POST['points'] ?? 10),
    'user_id' => $_SESSION['user_id'] ?? null
];

// Convertir la difficulté
$data['difficulty'] = $difficultyMap[$data['difficulty_text']] ?? 2; // Default to medium

// Validation
if (empty($data['title'])) {
    $errors['title'] = ["Le titre est obligatoire"];
} elseif (strlen($data['title']) > 255) {
    $errors['title'] = ["Le titre ne doit pas dépasser 255 caractères"];
}

if (empty($data['description'])) {
    $errors['description'] = ["La description est obligatoire"];
}

if (empty($data['language'])) {
    $errors['language'] = ["Le langage est obligatoire"];
}

if (empty($data['solution'])) {
    $errors['solution'] = ["La solution est obligatoire"];
}

if ($data['points'] < 1 || $data['points'] > 100) {
    $errors['points'] = ["Les points doivent être entre 1 et 100"];
}

if (empty($data['user_id'])) {
    $errors['general'] = ["Erreur d'authentification"];
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
        $conn->prepare("UPDATE users SET problems_posted = problems_posted + 1 WHERE id = ?")
            ->execute([$data['user_id']]);
        
        $_SESSION['success_message'] = "Problème publié avec succès!";
        header("Location: problem.php?id=$problem_id");
        exit;
    }
    
    throw new Exception("Erreur lors de l'insertion");
    
} catch (PDOException $e) {
    error_log("Erreur DB: " . $e->getMessage());
    $_SESSION['form_errors'] = [
        'general' => ["Erreur technique lors de l'enregistrement"]
    ];
    $_SESSION['form_data'] = $data;
    header('Location: expublier.php');
    exit;
} catch (Exception $e) {
    error_log("Erreur générale: " . $e->getMessage());
    $_SESSION['form_errors'] = [
        'general' => ["Une erreur est survenue: " . $e->getMessage()]
    ];
    $_SESSION['form_data'] = $data;
    header('Location: expublier.php');
    exit;
}
?>
