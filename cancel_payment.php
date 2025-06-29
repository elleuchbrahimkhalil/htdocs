<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$payment_id = isset($_GET['payment_id']) ? (int)$_GET['payment_id'] : 0;

if ($payment_id <= 0) {
    $_SESSION['error_message'] = "ID de paiement invalide";
    header('Location: premium_solutions.php');
    exit;
}

try {
    $conn = connect();
    $user = getCurrentUser();
    
    // Vérifier que le paiement appartient à l'utilisateur et est en attente
    $stmt = $conn->prepare("
        SELECT * FROM payments 
        WHERE id = ? AND payer_id = ? AND status = 'pending'
    ");
    $stmt->execute([$payment_id, $user['id']]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        $_SESSION['error_message'] = "Paiement non trouvé ou déjà traité";
        header('Location: premium_solutions.php');
        exit;
    }
    
    // Marquer le paiement comme annulé
    $stmt = $conn->prepare("
        UPDATE payments 
        SET status = 'cancelled', updated_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$payment_id]);
    
    $_SESSION['success_message'] = "Paiement annulé avec succès";
    
} catch (Exception $e) {
    error_log("Erreur cancel_payment.php: " . $e->getMessage());
    $_SESSION['error_message'] = "Erreur lors de l'annulation du paiement";
}

header('Location: premium_solutions.php');
exit;
?>