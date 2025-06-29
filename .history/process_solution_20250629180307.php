<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    $_SESSION['error_message'] = "Vous devez être connecté pour effectuer un achat";
    header('Location: login.php');
    exit;
}

// Récupérer les paramètres
$solution_id = isset($_GET['solution_id']) ? (int)$_GET['solution_id'] : 0;
$payment_method = isset($_GET['method']) ? $_GET['method'] : '';

if ($solution_id <= 0 || empty($payment_method)) {
    $_SESSION['error_message'] = "Paramètres de paiement invalides";
    header('Location: premium_solutions.php');
    exit;
}

$user = getCurrentUser();

try {
    $conn = connect();
    
    // Récupérer les détails de la solution
    $stmt = $conn->prepare("
        SELECT s.*, p.title as problem_title, u.username as seller_username
        FROM solutions s
        JOIN problems p ON s.problem_id = p.problem_id
        JOIN users u ON s.user_id = u.id
        WHERE s.id = ? AND s.status = 'approved' AND s.price > 0
    ");
    $stmt->execute([$solution_id]);
    $solution = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$solution) {
        throw new Exception("Solution non trouvée");
    }
    
    // Vérifier si l'utilisateur n'a pas déjà acheté cette solution
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM payments 
        WHERE solution_id = ? AND payer_id = ? AND status = 'completed'
    ");
    $stmt->execute([$solution_id, $user['id']]);
    
    if ($stmt->fetchColumn() > 0) {
        $_SESSION['error_message'] = "Vous avez déjà acheté cette solution";
        header('Location: premium_solutions.php');
        exit;
    }
    
    // Générer un ID de transaction unique
    $transaction_id = 'TXN_' . time() . '_' . $user['id'] . '_' . $solution_id;
    
    // Enregistrer la transaction en attente
    $stmt = $conn->prepare("
        INSERT INTO payments (
            solution_id, payer_id, seller_id, amount, currency, 
            payment_method, transaction_id, status, created_at
        ) VALUES (?, ?, ?, ?, 'EUR', ?, ?, 'pending', NOW())
    ");
    
        $stmt->execute([
        $solution_id,
        $user['id'],
        $solution['user_id'],
        $solution['price'],
        $payment_method,
        $transaction_id
    ]);
    
    $payment_id = $conn->lastInsertId();
    
    // Rediriger selon la méthode de paiement
    switch ($payment_method) {
        case 'paypal':
            // Simulation PayPal - En production, utiliser l'API PayPal
            $_SESSION['payment_data'] = [
                'payment_id' => $payment_id,
                'transaction_id' => $transaction_id,
                'amount' => $solution['price'],
                'solution_title' => $solution['problem_title']
            ];
            header('Location: paypal_simulation.php');
            break;
            
        case 'stripe':
            // Simulation Stripe - En production, utiliser l'API Stripe
            $_SESSION['payment_data'] = [
                'payment_id' => $payment_id,
                'transaction_id' => $transaction_id,
                'amount' => $solution['price'],
                'solution_title' => $solution['problem_title']
            ];
            header('Location: stripe_simulation.php');
            break;
            
        case 'crypto':
            // Simulation Crypto - En production, utiliser une API crypto
            $_SESSION['payment_data'] = [
                'payment_id' => $payment_id,
                'transaction_id' => $transaction_id,
                'amount' => $solution['price'],
                'solution_title' => $solution['problem_title']
            ];
            header('Location: crypto_simulation.php');
            break;
            
        default:
            throw new Exception("Méthode de paiement non supportée");
    }
    
} catch (Exception $e) {
    error_log("Erreur process_payment.php: " . $e->getMessage());
    $_SESSION['error_message'] = "Erreur lors du traitement du paiement: " . $e->getMessage();
    header('Location: premium_solutions.php');
    exit;
}
?>

