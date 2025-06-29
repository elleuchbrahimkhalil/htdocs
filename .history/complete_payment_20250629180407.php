<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$payment_id = isset($_GET['payment_id']) ? (int)$_GET['payment_id'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';

if ($payment_id <= 0) {
    $_SESSION['error_message'] = "ID de paiement invalide";
    header('Location: premium_solutions.php');
    exit;
}

try {
    $conn = connect();
    $user = getCurrentUser();
    
    // Récupérer les détails du paiement
    $stmt = $conn->prepare("
        SELECT p.*, s.solution_code, s.explanation, s.price,
               pr.title as problem_title, pr.description as problem_description,
               u.username as seller_username, u.name as seller_name
        FROM payments p
        JOIN solutions s ON p.solution_id = s.id
        JOIN problems pr ON s.problem_id = pr.problem_id
        JOIN users u ON s.user_id = u.id
        WHERE p.id = ? AND p.payer_id = ?
    ");
    $stmt->execute([$payment_id, $user['id']]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        throw new Exception("Paiement non trouvé");
    }
    
    if ($status === 'success' && $payment['status'] === 'pending') {
        // Marquer le paiement comme complété
        $stmt = $conn->prepare("
            UPDATE payments 
            SET status = 'completed', completed_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$payment_id]);
        
        // Mettre à jour les gains du vendeur
        $stmt = $conn->prepare("
            UPDATE users 
            SET earnings = earnings + ? 
            WHERE id = ?
        ");
        $stmt->execute([$payment['amount'], $payment['seller_id']]);
        
        $_SESSION['success_message'] = "Paiement effectué avec succès ! Vous avez maintenant accès à la solution.";
    }
    
} catch (Exception $e) {
    error_log("Erreur complete_payment.php: " . $e->getMessage());
    $_SESSION['error_message'] = "Erreur lors de la finalisation du paiement";
    header('Location: premium_solutions.php');
    exit;
}

// Rediriger vers la page de succès du paiement
header('Location: payment_success.php?payment_id=' . $payment_id);
exit;
?>