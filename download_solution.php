<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Récupérer l'ID de la solution
$solution_id = isset($_GET['solution_id']) ? (int)$_GET['solution_id'] : 0;

if ($solution_id <= 0) {
    $_SESSION['error_message'] = "ID de solution invalide";
    header('Location: user_feedback.php');
    exit;
}

try {
    $conn = connect();
    if (!$conn) {
        throw new Exception("Erreur de connexion à la base de données");
    }

    $user_id = $_SESSION['user_id'];

    // Vérifier si l'utilisateur a payé pour cette solution ou en est le propriétaire
    $stmt = $conn->prepare("
        SELECT s.*, p.title as problem_title, u.name as developer_name,
               CASE WHEN s.user_id = ? THEN 1 ELSE 0 END as is_owner,
               CASE WHEN EXISTS(
                   SELECT 1 FROM payments 
                   WHERE solution_id = s.id AND payer_id = ? AND status = 'completed'
               ) THEN 1 ELSE 0 END as has_paid
        FROM solutions s
        JOIN problems p ON s.problem_id = p.problem_id
        JOIN users u ON s.user_id = u.id
        WHERE s.id = ?
    ");
    $stmt->execute([$user_id, $user_id, $solution_id]);
    $solution = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$solution) {
        throw new Exception("Solution non trouvée");
    }

    if (!$solution['is_owner'] && !$solution['has_paid']) {
        throw new Exception("Vous n'avez pas accès à cette solution");
    }

    // Préparer le contenu du fichier
    $content = "/*\n";
    $content .= "* Solution pour: " . $solution['problem_title'] . "\n";
    $content .= "* Développeur: " . $solution['developer_name'] . "\n";
    $content .= "* Date de téléchargement: " . date('d/m/Y à H:i') . "\n";
    $content .= "* ID de la solution: " . $solution_id . "\n";
    $content .= "*/\n\n";

    if (!empty($solution['explanation'])) {
        $content .= "/*\n";
        $content .= "EXPLICATION:\n";
        $content .= wordwrap($solution['explanation'], 70, "\n") . "\n";
        $content .= "*/\n\n";
    }

    $content .= $solution['solution_code'];

    // Définir les en-têtes pour le téléchargement
    $filename = "solution_" . preg_replace('/[^a-zA-Z0-9_-]/', '_', $solution['problem_title']) . "_" . $solution_id . ".txt";
    
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($content));
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

    echo $content;
    exit;

} catch (Exception $e) {
    error_log("Erreur download_solution.php: " . $e->getMessage());
    $_SESSION['error_message'] = "Erreur: " . $e->getMessage();
    header('Location: user_feedback.php');
    exit;
}
?>
