<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    $_SESSION['error_message'] = "Vous devez être connecté pour effectuer un paiement";
    header('Location: exlogin.php');
    exit;
}

// Vérifier si c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Méthode de requête invalide";
    header('Location: user_feedback.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$solution_id = isset($_POST['solution_id']) ? (int)$_POST['solution_id'] : 0;

// Validation des données du formulaire
$errors = [];
$payment_data = [
    'solution_id' => $solution_id,
    'payment_method' => trim($_POST['payment_method'] ?? ''),
    'card_number' => trim($_POST['card_number'] ?? ''),
    'card_name' => trim($_POST['card_name'] ?? ''),
    'card_expiry' => trim($_POST['card_expiry'] ?? ''),
    'card_cvv' => trim($_POST['card_cvv'] ?? ''),
    'billing_address' => trim($_POST['billing_address'] ?? ''),
    'billing_city' => trim($_POST['billing_city'] ?? ''),
    'billing_postal' => trim($_POST['billing_postal'] ?? ''),
    'billing_country' => trim($_POST['billing_country'] ?? '')
];

// Validation des champs obligatoires
if ($solution_id <= 0) {
    $errors[] = "ID de solution invalide";
}

if (empty($payment_data['payment_method'])) {
    $errors[] = "Méthode de paiement requise";
}

if ($payment_data['payment_method'] === 'card') {
    if (empty($payment_data['card_number']) || !preg_match('/^\d{4}\s\d{4}\s\d{4}\s\d{4}$/', $payment_data['card_number'])) {
        $errors[] = "Numéro de carte invalide";
    }
    
    if (empty($payment_data['card_name']) || strlen($payment_data['card_name']) < 2) {
        $errors[] = "Nom du titulaire requis";
    }
    
    if (empty($payment_data['card_expiry']) || !preg_match('/^\d{2}\/\d{2}$/', $payment_data['card_expiry'])) {
        $errors[] = "Date d'expiration invalide";
    }
    
    if (empty($payment_data['card_cvv']) || !preg_match('/^\d{3,4}$/', $payment_data['card_cvv'])) {
        $errors[] = "CVV invalide";
    }
    
    // Validation de la date d'expiration
    if (!empty($payment_data['card_expiry'])) {
        list($month, $year) = explode('/', $payment_data['card_expiry']);
        $expiry_date = new DateTime('20' . $year . '-' . $month . '-01');
        $current_date = new DateTime();
        
        if ($expiry_date < $current_date) {
            $errors[] = "La carte a expiré";
        }
    }
}

// Si des erreurs sont présentes, rediriger
if (!empty($errors)) {
    $_SESSION['payment_errors'] = $errors;
    $_SESSION['payment_form_data'] = $payment_data;
    header('Location: payment.php?solution_id=' . $solution_id);
    exit;
}

try {
    $pdo = connect();
    
    if (!$pdo) {
        throw new Exception("Erreur de connexion à la base de données");
    }
    
    // Vérifier que la solution existe et est acceptée
    $stmt = $pdo->prepare("
        SELECT s.*, p.title as problem_title, p.user_id as problem_owner_id,
               u.username as solver_username, u.name as solver_name,
               pr.amount, pr.currency
        FROM solutions s
        JOIN problems p ON s.problem_id = p.problem_id
        JOIN users u ON s.user_id = u.id
        JOIN prices pr ON s.price_id = pr.price_id
        WHERE s.id = ? AND p.user_id = ? AND s.status = 'accepted'
    ");
    
    $stmt->execute([$solution_id, $user_id]);
    $solution = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$solution) {
        throw new Exception("Solution non trouvée ou non autorisée");
    }
    
    // Vérifier qu'il n'y a pas déjà un paiement pour cette solution
    $stmt = $pdo->prepare("SELECT id FROM payments WHERE solution_id = ?");
    $stmt->execute([$solution_id]);
    if ($stmt->fetch()) {
        throw new Exception("Cette solution a déjà été payée");
    }
    
    // Commencer une transaction
    $pdo->beginTransaction();
    
    try {
        // Générer un ID de transaction unique
        $transaction_id = 'TXN_' . date('Ymd') . '_' . uniqid();
        
        // Simuler le traitement du paiement
        $payment_success = simulatePaymentProcessing($payment_data, $solution['amount']);
        
        if (!$payment_success['success']) {
            throw new Exception($payment_success['error_message']);
        }
        
        // Enregistrer le paiement
        $stmt = $pdo->prepare("
            INSERT INTO payments (
                solution_id, payer_id, amount, currency, payment_method,
                transaction_id, status, payment_date, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, 'completed', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
            )
        ");
        
        $result = $stmt->execute([
            $solution_id,
            $user_id,
            $solution['amount'],
            $solution['currency'] ?? 'EUR',
            $payment_data['payment_method'],
            $transaction_id
        ]);
        
        if (!$result) {
            throw new Exception("Erreur lors de l'enregistrement du paiement");
        }
        
        $payment_id = $pdo->lastInsertId();
        
        // Mettre à jour le statut de la solution
        $stmt = $pdo->prepare("
            UPDATE solutions 
            SET status = 'paid', updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $stmt->execute([$solution_id]);
        
        // Mettre à jour les statistiques de l'utilisateur (acheteur)
        $stmt = $pdo->prepare("
            UPDATE users 
            SET solutions_purchased = solutions_purchased + 1 
            WHERE id = ?
        ");
        $stmt->execute([$user_id]);
        
        // Mettre à jour les statistiques du vendeur
        $stmt = $pdo->prepare("
            UPDATE users 
            SET solutions_sold = solutions_sold + 1, 
                earnings = earnings + ? 
            WHERE id = ?
        ");
        $stmt->execute([$solution['amount'], $solution['user_id']]);
        
        // Créer une notification pour le vendeur
        $stmt = $pdo->prepare("
            INSERT INTO notifications (
                user_id, type, title, message, related_id, created_at
            ) VALUES (
                ?, 'payment_received', 'Paiement reçu', 
                'Votre solution pour \"' || ? || '\" a été payée', 
                ?, CURRENT_TIMESTAMP
            )
        ");
        $stmt->execute([
            $solution['user_id'],
            $solution['problem_title'],
            $solution_id
        ]);
        
        // Valider la transaction
        $pdo->commit();
        
        // Nettoyer les données de session
        unset($_SESSION['payment_errors']);
        unset($_SESSION['payment_form_data']);
        
        // Rediriger vers la page de succès
        $_SESSION['payment_success'] = true;
        $_SESSION['payment_id'] = $payment_id;
        $_SESSION['transaction_id'] = $transaction_id;
        
        header('Location: payment_success.php?payment_id=' . $payment_id);
        exit;
        
    } catch (Exception $e) {
        // Annuler la transaction
        $pdo->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Erreur lors du traitement du paiement: " . $e->getMessage());
    
    $_SESSION['error_message'] = "Erreur lors du traitement du paiement: " . $e->getMessage();
    $_SESSION['payment_form_data'] = $payment_data;
    
    header('Location: payment.php?solution_id=' . $solution_id);
    exit;
}

/**
 * Simuler le traitement du paiement
 */
function simulatePaymentProcessing($payment_data, $amount) {
    // Simuler un délai de traitement
    usleep(rand(1000000, 3000000)); // 1-3 secondes
    
    // Simuler différents scénarios
    $scenarios = [
        ['success' => true, 'probability' => 0.85], // 85% de succès
        ['success' => false, 'error_message' => 'Paiement refusé par votre banque', 'probability' => 0.05],
        ['success' => false, 'error_message' => 'Fonds insuffisants', 'probability' => 0.03],
        ['success' => false, 'error_message' => 'Carte expirée', 'probability' => 0.02],
        ['success' => false, 'error_message' => 'Erreur de réseau', 'probability' => 0.05]
    ];
    
    $random = mt_rand() / mt_getrandmax();
    $cumulative_probability = 0;
    
    foreach ($scenarios as $scenario) {
        $cumulative_probability += $scenario['probability'];
        if ($random <= $cumulative_probability) {
            return $scenario;
        }
    }
    
    // Par défaut, retourner un succès
    return ['success' => true];
}

/**
 * Valider un numéro de carte avec l'algorithme de Luhn
 */
function validateCardNumber($number) {
    $number = preg_replace('/\D/', '', $number);
    
    if (strlen($number) !== 16) {
        return false;
    }
    
    $sum = 0;
    $is_even = false;
    
    for ($i = strlen($number) - 1; $i >= 0; $i--) {
        $digit = (int)$number[$i];
        
        if ($is_even) {
            $digit *= 2;
            if ($digit > 9) {
                $digit -= 9;
            }
        }
        
        $sum += $digit;
        $is_even = !$is_even;
    }
    
    return $sum % 10 === 0;
}

/**
 * Détecter le type de carte
 */
function detectCardType($number) {
    $number = preg_replace('/\D/', '', $number);
    
    if (preg_match('/^4/', $number)) {
        return 'visa';
    } elseif (preg_match('/^5[1-5]/', $number)) {
        return 'mastercard';
    } elseif (preg_match('/^3[47]/', $number)) {
        return 'amex';
    } elseif (preg_match('/^6(?:011|5)/', $number)) {
        return 'discover';
    }
    
    return 'unknown';
}

/**
 * Chiffrer les données sensibles (pour le stockage sécurisé)
 */
function encryptSensitiveData($data, $key) {
    $cipher = 'AES-256-CBC';
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($cipher));
    $encrypted = openssl_encrypt($data, $cipher, $key, 0, $iv);
    return base64_encode($encrypted . '::' . $iv);
}

/**
 * Déchiffrer les données sensibles
 */
function decryptSensitiveData($data, $key) {
    $cipher = 'AES-256-CBC';
    list($encrypted_data, $iv) = explode('::', base64_decode($data), 2);
    return openssl_decrypt($encrypted_data, $cipher, $key, 0, $iv);
}
?>
