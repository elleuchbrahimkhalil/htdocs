<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

$solution_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$own_solution = isset($_GET['own']) && $_GET['own'] == '1';

if ($solution_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de solution invalide']);
    exit;
}

try {
    $conn = connect();
    $user = getCurrentUser();
    
    if ($own_solution) {
        // Récupérer sa propre solution
        $stmt = $conn->prepare("
            SELECT s.*, p.title as problem_title, u.name as author_name, u.username
            FROM solutions s
            JOIN problems p ON s.problem_id = p.problem_id
            JOIN users u ON s.user_id = u.id
            WHERE s.id = ? AND s.user_id = ?
        ");
        $stmt->execute([$solution_id, $user['id']]);
    } else {
        // Récupérer une solution soumise à ses problèmes
        $stmt = $conn->prepare("
            SELECT s.*, p.title as problem_title, u.name as author_name, u.username
            FROM solutions s
            JOIN problems p ON s.problem_id = p.problem_id
            JOIN users u ON s.user_id = u.id
            WHERE s.id = ? AND p.user_id = ?
        ");
        $stmt->execute([$solution_id, $user['id']]);
    }
    
    $solution = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$solution) {
        echo json_encode(['success' => false, 'message' => 'Solution non trouvée ou accès non autorisé']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'solution' => [
            'id' => $solution['id'],
            'solution_code' => $solution['solution_code'],
            'explanation' => $solution['explanation'],
            'status' => $solution['status'],
            'created_at' => $solution['created_at'],
            'evaluated_at' => $solution['evaluated_at'],
            'feedback' => $solution['feedback'],
            'author_name' => $solution['author_name'] ?: $solution['username'],
            'problem_title' => $solution['problem_title']
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Erreur lors de la récupération de la solution: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?>
