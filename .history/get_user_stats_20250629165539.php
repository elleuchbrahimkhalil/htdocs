<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

// Récupérer l'ID utilisateur
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $_SESSION['user_id'];

// Vérifier que l'utilisateur peut accéder à ces stats
if ($user_id !== $_SESSION['user_id']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Accès refusé']);
    exit;
}

try {
    $conn = connect();
    if (!$conn) {
        throw new Exception("Erreur de connexion à la base de données");
    }

    // Calculer le nombre de problèmes résolus (solutions acceptées)
    $stmt = $conn->prepare("SELECT COUNT(*) FROM solutions WHERE user_id = ? AND status = 'accepted'");
    $stmt->execute([$user_id]);
    $problems_solved = $stmt->fetchColumn() ?: 0;

    // Calculer le nombre de problèmes postés
    $stmt = $conn->prepare("SELECT COUNT(*) FROM problems WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $problems_posted = $stmt->fetchColumn() ?: 0;

    // Calculer le score total (somme des points des solutions acceptées)
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(p.points), 0) 
        FROM solutions s 
        JOIN problems p ON s.problem_id = p.problem_id 
        WHERE s.user_id = ? AND s.status = 'accepted'
    ");
    $stmt->execute([$user_id]);
    $total_score = $stmt->fetchColumn() ?: 0;

    // Calculer les gains totaux (montants reçus pour les solutions vendues)
    $stmt = $conn->prepare("
        SELECT COALESCE(SUM(pay.amount), 0) 
        FROM payments pay 
        JOIN solutions s ON pay.solution_id = s.id 
        WHERE s.user_id = ? AND pay.status = 'completed'
    ");
    $stmt->execute([$user_id]);
    $total_earnings = $stmt->fetchColumn() ?: 0;

    // Retourner les statistiques
    echo json_encode([
        'success' => true,
        'stats' => [
            'score' => (int)$total_score,
            'problems_solved' => (int)$problems_solved,
            'problems_posted' => (int)$problems_posted,
            'earnings' => (float)$total_earnings
        ],
        'timestamp' => time()
    ]);

} catch (Exception $e) {
    error_log("Erreur get_user_stats.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => 'Erreur serveur',
        'details' => $e->getMessage()
    ]);
}
?>