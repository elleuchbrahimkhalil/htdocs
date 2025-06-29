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
$method = isset($_GET['method']) ? $_GET['method'] : 'stripe';

if ($payment_id <= 0 || $status !== 'success') {
    $_SESSION['error_message'] = "Paramètres de paiement invalides";
    header('Location: premium_solutions.php');
    exit;
}

try {
    $conn = connect();
    $user = getCurrentUser();
    
    // Vérifier que le paiement existe et appartient à l'utilisateur
    $stmt = $conn->prepare("
        SELECT p.*, s.solution_code, s.explanation, s.price,
               pr.title as problem_title, pr.description as problem_description,
               seller.username as seller_username, seller.name as seller_name
        FROM payments p
        JOIN solutions s ON p.solution_id = s.id
        JOIN problems pr ON s.problem_id = pr.problem_id
        JOIN users seller ON s.user_id = seller.id
        WHERE p.id = ? AND p.payer_id = ? AND p.status = 'pending'
    ");
    $stmt->execute([$payment_id, $user['id']]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        $_SESSION['error_message'] = "Paiement non trouvé ou déjà traité";
        header('Location: premium_solutions.php');
        exit;
    }
    
    // Générer un ID de transaction unique
    $transaction_id = 'TXN_' . strtoupper(uniqid()) . '_' . time();
    
    // Mettre à jour le paiement comme complété
    $stmt = $conn->prepare("
        UPDATE payments 
        SET status = 'completed', 
            payment_method = ?, 
            transaction_id = ?,
            completed_at = NOW(),
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$method, $transaction_id, $payment_id]);
    
    // Mettre à jour les gains du vendeur
    $stmt = $conn->prepare("
        UPDATE users 
        SET earnings = earnings + ? 
        WHERE id = (SELECT user_id FROM solutions WHERE id = ?)
    ");
    $stmt->execute([$payment['amount'], $payment['solution_id']]);
    
    // Nettoyer les données de session
    unset($_SESSION['payment_data']);
    
    // Rediriger vers la page de succès avec l'ID de paiement
    header('Location: payment_success.php?payment_id=' . $payment['id']);
    exit;
    
} catch (Exception $e) {
    error_log("Erreur complete_payment.php: " . $e->getMessage());
    $_SESSION['error_message'] = "Erreur lors de la finalisation du paiement";
    header('Location: premium_solutions.php');
    exit;
}
?>