<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

if (!isLoggedIn()) {
    http_response_code(401);
    exit('Non autorisé');
}

$solution_id = isset($_GET['solution_id']) ? (int)$_GET['solution_id'] : 0;

if ($solution_id <= 0) {
    http_response_code(400);
    exit('ID de solution invalide');
}

try {
    $conn = connect();
    $user = getCurrentUser();
    
    // Vérifier que l'utilisateur a acheté cette solution
    $stmt = $conn->prepare("
        SELECT s.solution_code, s.explanation, p.title as problem_title,
               p.description as problem_description, u.name as author_name
        FROM solutions s
        JOIN problems p ON s.problem_id = p.problem_id
        JOIN users u ON s.user_id = u.id
        WHERE s.id = ? 
        AND EXISTS(
            SELECT 1 FROM payments pay 
            WHERE pay.solution_id = s.id 
            AND pay.payer_id = ? 
            AND pay.status = 'completed'
        )
    ");
    $stmt->execute([$solution_id, $user['id']]);
    $solution = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$solution) {
        http_response_code(403);
        exit('Accès refusé - Solution non achetée');
    }
    
    // Générer le contenu du fichier
    $content = "/*\n";
    $content .= "* Solution pour: " . $solution['problem_title'] . "\n";
    $content .= "* Auteur: " . $solution['author_name'] . "\n";
    $content .= "* Téléchargé le: " . date('d/m/Y à H:i') . "\n";
    $content .= "* Acheteur: " . $user['name'] . "\n";
    $content .= "*/\n\n";
    
    if (!empty($solution['explanation'])) {
        $content .= "/*\nEXPLICATION:\n" . $solution['explanation'] . "\n*/\n\n";
    }
    
    $content .= "// DESCRIPTION DU PROBLÈME:\n";
    $content .= "// " . str_replace("\n", "\n// ", $solution['problem_description']) . "\n\n";
    
    $content .= "// SOLUTION:\n";
    $content .= $solution['solution_code'];
    
    // Définir les en-têtes pour le téléchargement
    $filename = 'solution_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $solution['problem_title']) . '.txt';
    
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . strlen($content));
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // Envoyer le contenu
    echo $content;
    
} catch (Exception $e) {
    error_log("Erreur download_solution.php: " . $e->getMessage());
    http_response_code(500);
    exit('Erreur serveur');
}
?>
