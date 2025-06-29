<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';
require_once 'ai_analyzer.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    $_SESSION['error_message'] = "Vous devez être connecté pour publier un problème";
    header('Location: login.php');
    exit;
}

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: expublier.php');
    exit;
}

// Récupérer les données du formulaire
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$language = $_POST['language'] ?? '';
$code = trim($_POST['code'] ?? '');
$tags = trim($_POST['tags'] ?? '');
$solution = trim($_POST['solution'] ?? '');

// Validation des données
$errors = [];

if (strlen($title) < 5) {
    $errors['title'][] = "Le titre doit contenir au moins 5 caractères";
}

if (strlen($description) < 50) {
    $errors['description'][] = "La description doit contenir au moins 50 caractères";
}

if (empty($language)) {
    $errors['language'][] = "Veuillez sélectionner un langage de programmation";
}

if (strlen($code) < 20) {
    $errors['code'][] = "Le code doit contenir au moins 20 caractères";
}

if (strlen($solution) < 20) {
    $errors['solution'][] = "La solution doit contenir au moins 20 caractères";
}

// Si des erreurs sont trouvées, retourner au formulaire
if (!empty($errors)) {
    $_SESSION['form_errors'] = $errors;
    $_SESSION['form_data'] = $_POST;
    header('Location: expublier.php');
    exit;
}

try {
    $conn = connect();
    if (!$conn) {
        throw new Exception("Erreur de connexion à la base de données");
    }
    
    // Initialiser l'analyseur IA
    $aiAnalyzer = new AIAnalyzer();
    
    // Analyser le problème avec l'IA
    $analysis = $aiAnalyzer->analyzeProblem($code, $description, $language, $solution);
    
    // Générer le rapport d'analyse
    $analysisReport = $aiAnalyzer->generateAnalysisReport($analysis);
    
    // Préparer les données pour l'insertion
    $user_id = $_SESSION['user_id'];
    $difficulty = $analysis['difficulty'];
    $points = $analysis['base_score'];
    
    // Insérer le problème dans la base de données
    $stmt = $conn->prepare("
        INSERT INTO problems (
            user_id, title, description, language, code, tags, solution, 
            difficulty, points, ai_analysis, ai_confidence, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $ai_analysis_json = json_encode([
        'analysis' => $analysis,
        'report' => $analysisReport,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    $stmt->execute([
        $user_id,
        $title,
        $description,
        $language,
        $code,
        $tags,
        $solution,
        $difficulty,
        $points,
        $ai_analysis_json,
        $analysis['ai_confidence']
    ]);
    
    $problem_id = $conn->lastInsertId();
    
    // Mettre à jour les statistiques de l'utilisateur
    $stmt = $conn->prepare("
        UPDATE users 
        SET problems_posted = problems_posted + 1 
        WHERE id = ?
    ");
    $stmt->execute([$user_id]);
    
    // Enregistrer l'analyse dans un log pour amélioration future
    $log_entry = [
        'problem_id' => $problem_id,
        'user_id' => $user_id,
        'analysis' => $analysis,
        'report' => $analysisReport,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    error_log("AI Analysis: " . json_encode($log_entry));
    
    // Message de succès avec détails de l'analyse
    $difficulty_labels = [
        'easy' => 'Facile',
        'medium' => 'Moyen',
        'hard' => 'Difficile'
    ];
    
    $_SESSION['success_message'] = sprintf(
        "Problème publié avec succès ! L'IA a analysé votre code : Difficulté %s, Score de base %d points (Confiance: %.1f%%)",
        $difficulty_labels[$difficulty],
        $points,
        $analysis['ai_confidence'] * 100
    );
    
    // Rediriger vers la page du problème créé
    header("Location: problem.php?id=" . $problem_id);
    exit;
    
} catch (Exception $e) {
    error_log("Erreur lors de la création du problème: " . $e->getMessage());
    
    $_SESSION['form_errors'] = ['general' => ["Erreur lors de la publication: " . $e->getMessage()]];
    $_SESSION['form_data'] = $_POST;
    
    header('Location: expublier.php');
    exit;
}
?>
