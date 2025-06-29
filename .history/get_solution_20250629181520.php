<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

$solution_id = isset($_GET['solution_id']) ? (int)$_GET['solution_id'] : 0;

if ($solution_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de solution invalide']);
    exit;
}

try {
    $conn = connect();
    $user = getCurrentUser();
    
    // Vérifier que l'utilisateur a acheté cette solution
    $stmt = $conn->prepare("
        SELECT s.solution_code, s.explanation, p.title as problem_title,
               p.description as problem_description
        FROM solutions s
        JOIN problems p ON s.problem_id = p.problem_id
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
        echo json_encode(['success' => false, 'message' => 'Solution non trouvée ou non achetée']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'solution' => [
            'code' => $solution['solution_code'],
            'explanation' => $solution['explanation'],
            'title' => $solution['problem_title'],
            'description' => $solution['problem_description']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Erreur get_solution.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?>