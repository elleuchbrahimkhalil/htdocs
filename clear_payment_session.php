<?php
session_start();
require_once 'verification.php';

header('Content-Type: application/json');

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

// Nettoyer les variables de session liées au paiement
unset($_SESSION['payment_success']);
unset($_SESSION['payment_id']);
unset($_SESSION['transaction_id']);
unset($_SESSION['payment_errors']);
unset($_SESSION['payment_form_data']);

echo json_encode(['success' => true]);
?>