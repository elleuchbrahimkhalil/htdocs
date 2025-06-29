<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';
require_once 'includes/problem_functions.php';
require_once 'includes/problem_styles.php';
require_once 'includes/problem_scripts.php';
require_once 'components/problem_header.php';
require_once 'components/problem_sections.php';

// Vérifier si l'ID est fourni
if (!isset($_GET['id'])) {
    header('Location: exacueil.php');
    exit;
}

$problem_id = (int)$_GET['id'];
$user_id = isLoggedIn() ? $_SESSION['user_id'] : 0;

try {
    $conn = connect();
    if (!$conn) {
        throw new Exception("Erreur de connexion à la base de données");
    }

    // Récupérer le problème
    $problem = getProblemById($conn, $problem_id, $user_id);
    if (!$problem) {
        throw new Exception("Problème non trouvé");
    }

    // Récupérer les données associées
    $solutions = getProblemSolutions($conn, $problem_id);
    $stats = getProblemStats($conn, $problem_id);
    $user_solution = null;
    
    if (isLoggedIn()) {
        $user_solution = getUserSolutionForProblem($conn, $problem_id, $user_id);
    }

} catch (Exception $e) {
    $_SESSION['error_message'] = "Erreur: " . $e->getMessage();
    header('Location: exacueil.php');
    exit;
}

// Définir le titre de la page
$page_title = htmlspecialchars($problem['title']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/atom-one-dark.min.css">
    <style>
        <?= getProblemStyles() ?>
    </style>
</head>
<body>
    <?php include 'exmenu.php'; ?>

    <?php 
    // Afficher l'avatar utilisateur
    require_once('exavatar.php');
    displayUserAvatar();
    ?>

    <div class="problem-container">
        <?php 
        // Afficher le statut de la solution utilisateur si applicable
        if ($user_solution) {
            renderUserSolutionStatus($user_solution);
        }
        
        // Afficher les différentes sections
        renderProblemHeader($problem, $stats);
        renderProblemDescription($problem);
        renderProblemCode($problem);
        renderProblemSolution($problem);
        renderSolutionsSection($solutions);
        renderProblemActions($problem, $user_solution);
        ?>
    </div>

    <script>
        <?= getProblemScripts() ?>
    </script>
</body>
</html>
