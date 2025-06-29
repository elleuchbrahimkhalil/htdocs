<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$solution_id = (int)($input['solution_id'] ?? 0);

if ($solution_id <= 0 || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Paramètres invalides']);
    exit;
}

try {
    $conn = connect();
    $user = getCurrentUser();
    
    // Vérifier que l'utilisateur est propriétaire du problème
    $stmt = $conn->prepare("
        SELECT s.*, p.user_id as problem_owner_id
        FROM solutions s
        JOIN problems p ON s.problem_id = p.problem_id
        WHERE s.id = ? AND p.user_id = ?
    ");
    $stmt->execute([$solution_id, $user['id']]);
    $solution = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$solution) {
        echo json_encode(['success' => false, 'message' => 'Solution non trouvée ou accès non autorisé']);
        exit;
    }
    
    if ($solution['status'] !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'Cette solution a déjà été évaluée']);
        exit;
    }
    
    // Mettre à jour le statut de la solution
    $new_status = ($action === 'approve') ? 'approved' : 'rejected';
    $feedback = ($action === 'reject') ? ($input['feedback'] ?? '') : null;
    
    $stmt = $conn->prepare("
        UPDATE solutions 
        SET status = ?, feedback = ?, evaluated_at = GETDATE()
        WHERE id = ?
    ");
    $stmt->execute([$new_status, $feedback, $solution_id]);
    
    // Si approuvée, mettre à jour le score de l'utilisateur qui a soumis la solution
    if ($action === 'approve') {
        $stmt = $conn->prepare("
            UPDATE users 
            SET score = score + (SELECT points FROM problems WHERE problem_id = ?),
                problems_solved = problems_solved + 1
            WHERE id = ?
        ");
        $stmt->execute([$solution['problem_id'], $solution['user_id']]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => $action === 'approve' ? 'Solution approuvée' : 'Solution rejetée'
    ]);
    
} catch (Exception $e) {
    error_log("Erreur manage_solution.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}
?>