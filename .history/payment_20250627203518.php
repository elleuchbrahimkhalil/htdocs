<?php
session_start();

// Diagnostic au début
error_log("DIAGNOSTIC: Accès à payment.php");
error_log("DIAGNOSTIC: GET params: " . print_r($_GET, true));

require_once 'verification.php';
require_once 'db_connect.php';

// Mode debug
$debug_mode = isset($_GET['debug']) && $_GET['debug'] == '1';
$test_mode = isset($_GET['test']) && $_GET['test'] == '1';

if ($debug_mode || $test_mode) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    error_log("DIAGNOSTIC: Mode debug/test activé dans payment.php");
}

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    error_log("DIAGNOSTIC: Utilisateur non connecté dans payment.php");
    $_SESSION['error_message'] = "Vous devez être connecté pour accéder à cette page.";
    header('Location: exlogin.php');
    exit;
}

// Vérifier si l'ID de la solution est fourni
if (!isset($_GET['solution_id']) || !is_numeric($_GET['solution_id'])) {
    error_log("DIAGNOSTIC: solution_id manquant ou invalide - " . ($_GET['solution_id'] ?? 'NULL'));
    $_SESSION['error_message'] = "Solution non spécifiée ou invalide.";
    header('Location: user_feedback.php?payment_error=invalid_solution_id');
    exit;
}

$solution_id = (int)$_GET['solution_id'];
$user_id = $_SESSION['user_id'];

error_log("DIAGNOSTIC: solution_id = $solution_id, user_id = $user_id");

try {
    $pdo = connect();
    
    if (!$pdo) {
        throw new Exception("Erreur de connexion à la base de données");
    }
    
    error_log("DIAGNOSTIC: Connexion DB réussie dans payment.php");
    
    // Récupérer les détails de la solution
    $stmt = $pdo->prepare("
        SELECT s.*, p.title as problem_title, p.description as problem_description, 
               p.user_id as problem_owner_id, pr.amount, pr.currency,
               u.username as solver_username, u.name as solver_name
        FROM solutions s
        JOIN problems p ON s.problem_id = p.problem_id
        LEFT JOIN prices pr ON s.price_id = pr.price_id
        JOIN users u ON s.user_id = u.id
        WHERE s.id = ? AND p.user_id = ?
    ");
    
    $stmt->execute([$solution_id, $user_id]);
    $solution = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("DIAGNOSTIC: Solution trouvée: " . ($solution ? 'OUI' : 'NON'));
    
    if (!$solution) {
        error_log("DIAGNOSTIC: Redirection - solution non trouvée");
        $_SESSION['error_message'] = "Solution non trouvée ou vous n'êtes pas autorisé à y accéder.";
        if ($debug_mode) {
            $_SESSION['error_message'] .= " (Debug: solution_id=$solution_id, user_id=$user_id)";
        }
        header('Location: user_feedback.php?payment_error=solution_not_found');
        exit;
    }
    
    // Vérifier le statut de la solution
    $valid_statuses = ['accepted', 'approved', 'approve', 'accept'];
    if (!in_array($solution['status'], $valid_statuses)) {
        error_log("DIAGNOSTIC: Statut invalide - " . $solution['status']);
        $_SESSION['error_message'] = "Cette solution n'est pas prête pour le paiement (statut: " . $solution['status'] . ").";
        if ($debug_mode) {
            $_SESSION['error_message'] .= " (Statuts valides: " . implode(', ', $valid_statuses) . ")";
        }
        header('Location: user_feedback.php?payment_error=invalid_status');
        exit;
    }
    
    // Vérifier si le paiement a déjà été effectué
    $stmt = $pdo->prepare("SELECT payment_id, status FROM payments WHERE solution_id = ? AND payer_id = ?");
    $stmt->execute([$solution_id, $user_id]);
    $existing_payment = $stmt->fetch();
    
    if ($existing_payment) {
        error_log("DIAGNOSTIC: Paiement déjà effectué - ID " . $existing_payment['payment_id']);
        $_SESSION['error_message'] = "Cette solution a déjà été payée (Paiement #" . $existing_payment['payment_id'] . ").";
        header('Location: user_feedback.php?payment_error=already_paid');
        exit;
    }
    
    error_log("DIAGNOSTIC: Toutes les vérifications passées - affichage de la page de paiement");
    
} catch (Exception $e) {
    error_log("DIAGNOSTIC: Exception dans payment.php: " . $e->getMessage());
    $_SESSION['error_message'] = "Une erreur est survenue lors du chargement de la page de paiement.";
    if ($debug_mode) {
        $_SESSION['error_message'] .= " (Debug: " . $e->getMessage() . ")";
    }
    header('Location: user_feedback.php?payment_error=exception&message=' . urlencode($e->getMessage()));
    exit;
}

// Traitement du paiement (si POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    error_log("DIAGNOSTIC: Traitement du paiement POST");
    
    $payment_method = $_POST['payment_method'] ?? '';
    $amount = $solution['amount'] ?? 0;
    
    error_log("DIAGNOSTIC: method=$payment_method, amount=$amount");
    
    if (empty($payment_method)) {
        $error_message = "Veuillez sélectionner une méthode de paiement.";
    } elseif ($amount <= 0) {
        $error_message = "Montant invalide ($amount).";
    } else {
        try {
            // Commencer une transaction
            $pdo->beginTransaction();
            
            // Simuler le traitement du paiement
            $transaction_id = 'TXN_' . time() . '_' . rand(1000, 9999);
            
            error_log("DIAGNOSTIC: Transaction ID = $transaction_id");
            
            // Insérer le paiement dans la base de données
            $stmt = $pdo->prepare("
                INSERT INTO payments (
                    solution_id, payer_id, payee_id, amount, payment_method, 
                    transaction_id, status, payment_date, created_at
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, 'completed', GETDATE(), GETDATE()
                )
            ");
            
            $result = $stmt->execute([
                $solution_id,
                $user_id,
                $solution['user_id'], // payee_id = l'ID du développeur
                $amount,
                $payment_method,
                $transaction_id
            ]);
            
            if ($result) {
                $payment_id = $pdo->lastInsertId();
                
                error_log("DIAGNOSTIC: Paiement inséré avec ID = $payment_id");
                
                // Mettre à jour le statut de la solution
                $stmt = $pdo->prepare("UPDATE solutions SET status = 'paid' WHERE id = ?");
                $stmt->execute([$solution_id]);
                
                // Valider la transaction
                $pdo->commit();
                
                error_log("DIAGNOSTIC: Transaction validée, redirection vers payment_success.php");
                
                // Rediriger vers la page de succès
                $_SESSION['payment_success'] = true;
                $_SESSION['payment_id'] = $payment_id;
                $_SESSION['transaction_id'] = $transaction_id;
                
                header('Location: payment_success.php?payment_id=' . $payment_id);
                exit;
            } else {
                $pdo->rollBack();
                throw new Exception("Erreur lors de l'enregistrement du paiement");
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("DIAGNOSTIC: Erreur lors du traitement du paiement: " . $e->getMessage());
            $error_message = "Une erreur est survenue lors du traitement du paiement.";
            if ($debug_mode) {
                $error_message .= " (Debug: " . $e->getMessage() . ")";
            }
        }
    }
}

// Configuration de la page
$page_title = "Paiement Sécurisé";
$additional_css = "
    .debug-info {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
        font-family: monospace;
        font-size: 12px;
        max-height: 300px;
        overflow-y: auto;
    }
    
    .debug-info h4 {
        margin-top: 0;
        color: #495057;
    }
    
    .payment-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .payment-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        overflow: hidden;
        margin-bottom: 30px;
        border: 1px solid #e9ecef;
    }
    
    .payment-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 40px 30px;
        text-align: center;
        position: relative;
    }
    
    .payment-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 100 100\"><defs><pattern id=\"grain\" width=\"100\" height=\"100\" patternUnits=\"userSpaceOnUse\"><circle cx=\"50\" cy=\"50\" r=\"1\" fill=\"white\" opacity=\"0.1\"/></pattern></defs><rect width=\"100\" height=\"100\" fill=\"url(%23grain)\"/></svg>');
        opacity: 0.3;
    }
    
    .payment-header h1 {
        margin: 0 0 10px 0;
        font-size: 32px;
        font-weight: 700;
        position: relative;
        z-index: 1;
    }
    
    .payment-header p {
        margin: 0;
        opacity: 0.9;
        font-size: 18px;
        position: relative;
        z-index: 1;
    }
    
    .payment-body {
        padding: 40px 30px;
    }
    
    .solution-summary {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 30px;
        border-left: 5px solid #667eea;
        position: relative;
        overflow: hidden;
    }
    
    .solution-summary::before {
        content: '💡';
        position: absolute;
        top: 20px;
        right: 20px;
        font-size: 24px;
        opacity: 0.3;
    }
    
    .solution-title {
        font-size: 22px;
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .solution-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 20px;
        font-size: 14px;
        color: #6c757d;
    }
    
    .solution-meta span {
        display: flex;
        align-items: center;
        gap: 6px;
        background: white;
        padding: 8px 12px;
        border-radius: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    
    .solution-preview {
        background: #2c3e50;
        color: #ecf0f1;
        padding: 20px;
        border-radius: 8px;
        font-family: 'Courier New', monospace;
        font-size: 13px;
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid #34495e;
        position: relative;
    }
    
    .solution-preview::before {
        content: 'Code Preview';
        position: absolute;
        top: 5px;
        right: 10px;
        font-size: 10px;
        opacity: 0.5;
        text-transform: uppercase;
    }
    
    .price-breakdown {
        background: white;
        border: 2px solid #28a745;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }
    
    .price-breakdown::before {
        content: '💰';
        position: absolute;
        top: 20px;
        right: 20px;
        font-size: 24px;
        opacity: 0.3;
    }
    
    .price-breakdown h3 {
        color: #28a745;
        margin-top: 0;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 20px;
    }
    
    .price-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px 0;
        border-bottom: 1px solid #f8f9fa;
        font-size: 16px;
    }
    
    .price-row:last-child {
        border-bottom: none;
        font-weight: bold;
        font-size: 24px;
        color: #28a745;
        border-top: 3px solid #28a745;
        margin-top: 15px;
        padding-top: 20px;
        background: linear-gradient(135deg, #f8fff9 0%, #e8f5e8 100%);
        margin-left: -10px;
        margin-right: -10px;
        padding-left: 10px;
        padding-right: 10px;
        border-radius: 8px;
    }
    
    .payment-methods {
        margin-bottom: 30px;
    }
    .payment-methods h3 {
        color: #2c3e50;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 20px;
    }
    
    .payment-method {
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 15px;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 20px;
        position: relative;
        overflow: hidden;
    }
    
    .payment-method::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
        transition: left 0.5s;
    }
    
    .payment-method:hover {
        border-color: #667eea;
        background: linear-gradient(135deg, #f8f9ff 0%, #f0f2ff 100%);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
    }
    
    .payment-method:hover::before {
        left: 100%;
    }
    
    .payment-method.selected {
        border-color: #667eea;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
    }
    
    .payment-method.selected .payment-method-icon {
        background: rgba(255,255,255,0.2);
        color: white;
    }
    
    .payment-method input[type='radio'] {
        margin: 0;
        transform: scale(1.2);
    }
    
    .payment-method-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        color: white;
        transition: all 0.3s ease;
    }
    
    .payment-method-icon.card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }
    
    .payment-method-icon.paypal {
        background: linear-gradient(135deg, #0070ba 0%, #003087 100%);
    }
    
    .payment-method-icon.crypto {
        background: linear-gradient(135deg, #f7931a 0%, #ff6b35 100%);
    }
    
    .payment-method-icon.google-pay {
        background: linear-gradient(135deg, #4285f4 0%, #34a853 100%);
    }
    
    .payment-method-info {
        flex: 1;
    }
    
    .payment-method-name {
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 5px;
        font-size: 18px;
    }
    
    .payment-method.selected .payment-method-name {
        color: white;
    }
    
    .payment-method-desc {
        color: #6c757d;
        font-size: 14px;
        line-height: 1.4;
    }
    
    .payment-method.selected .payment-method-desc {
        color: rgba(255,255,255,0.9);
    }
    
    .card-form {
        display: none;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        padding: 25px;
        border-radius: 12px;
        margin-top: 20px;
        border: 1px solid #dee2e6;
        animation: slideDown 0.3s ease;
    }
    
    .card-form.active {
        display: block;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .form-row {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .form-group {
        flex: 1;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #2c3e50;
        font-size: 14px;
    }
    
    .form-group input {
        width: 100%;
        padding: 15px;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        font-size: 16px;
        transition: all 0.3s ease;
        background: white;
    }
    
    .form-group input:focus {
        border-color: #667eea;
        outline: none;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        transform: translateY(-1px);
    }
    
    .security-info {
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
        border: 1px solid #28a745;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .security-info i {
        color: #28a745;
        font-size: 24px;
    }
    
    .security-info-text {
        flex: 1;
        font-size: 14px;
        color: #155724;
        line-height: 1.5;
    }
    
    .payment-actions {
        display: flex;
        gap: 20px;
        justify-content: space-between;
        margin-top: 40px;
        flex-wrap: wrap;
    }
    
    .btn {
        padding: 15px 30px;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        transition: all 0.3s ease;
        border: none;
        font-size: 16px;
        position: relative;
        overflow: hidden;
    }
    
    .btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
        transition: left 0.5s;
    }
    
    .btn:hover::before {
        left: 100%;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
    }
    
    .btn-primary:hover {
        background: linear-gradient(135deg, #218838 0%, #1ea085 100%);
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(40, 167, 69, 0.4);
    }
    
    .btn-secondary {
        background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
        color: white;
    }
    
    .btn-secondary:hover {
        background: linear-gradient(135deg, #5a6268 0%, #3d4043 100%);
        transform: translateY(-2px);
    }
    
    .btn-outline {
        background: transparent;
        color: #667eea;
        border: 2px solid #667eea;
    }
    
    .btn-outline:hover {
        background: #667eea;
        color: white;
        transform: translateY(-2px);
    }
    
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.7);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        backdrop-filter: blur(5px);
    }
    
    .loading-content {
        background: white;
        padding: 40px;
        border-radius: 16px;
        text-align: center;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        max-width: 300px;
    }
    
    .loading-spinner {
        width: 50px;
        height: 50px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #667eea;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 20px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .google-pay-section {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 12px;
        padding: 25px;
        margin: 20px 0;
        border: 1px solid #dee2e6;
        text-align: center;
    }
    
    .google-pay-section h4 {
        color: #2c3e50;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }
    
    #google-pay-container {
        margin: 20px 0;
        min-height: 50px;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    
    .features-list {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 20px;
        margin: 20px 0;
    }
    
    .features-list h4 {
        color: #2c3e50;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .features-list ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    
    .features-list li {
        padding: 8px 0;
        display: flex;
        align-items: center;
        gap: 10px;
        color: #495057;
    }
    
    .features-list li i {
        color: #28a745;
        width: 16px;
    }
    
    @media (max-width: 768px) {
        .payment-container {
            padding: 15px;
        }
        
        .payment-body {
            padding: 25px 20px;
        }
        
        .form-row {
            flex-direction: column;
        }
        
        .payment-actions {
            flex-direction: column;
        }
        
        .solution-meta {
            flex-direction: column;
            gap: 10px;
        }
        
        .payment-method {
            padding: 15px;
        }
        
        .payment-method-icon {
            width: 40px;
            height: 40px;
            font-size: 18px;
        }
    }
    
    @media (max-width: 480px) {
        .payment-header h1 {
            font-size: 24px;
        }
        
        .payment-header p {
            font-size: 16px;
        }
        
        .solution-title {
            font-size: 18px;
        }
        
        .btn {
            padding: 12px 20px;
            font-size: 14px;
        }
    }
";

// Include header
include 'header.php';
?>

<div class="payment-container">
    <?php if ($debug_mode): ?>
        <div class="debug-info">
            <h4>🔍 Informations de débogage - payment.php</h4>
            <div><strong>Solution ID:</strong> <?php echo $solution_id; ?></div>
            <div><strong>User ID:</strong> <?php echo $user_id; ?></div>
            <div><strong>Solution Status:</strong> <?php echo htmlspecialchars($solution['status'] ?? 'N/A'); ?></div>
            <div><strong>Problem Owner:</strong> <?php echo htmlspecialchars($solution['problem_owner_id'] ?? 'N/A'); ?></div>
            <div><strong>Amount:</strong> <?php echo htmlspecialchars($solution['amount'] ?? 'N/A'); ?> <?php echo htmlspecialchars($solution['currency'] ?? 'EUR'); ?></div>
            <div><strong>Problem Title:</strong> <?php echo htmlspecialchars($solution['problem_title'] ?? 'N/A'); ?></div>
            <div><strong>Solver:</strong> <?php echo htmlspecialchars($solution['solver_name'] ?? 'N/A'); ?></div>
            <div style="margin-top: 10px;">
                <a href="?solution_id=<?php echo $solution_id; ?>" class="btn btn-outline" style="font-size: 12px;">Désactiver le débogage</a>
            </div>
        </div>
    <?php endif; ?>

    <div class="payment-card">
        <div class="payment-header">
            <h1><i class="fas fa-shield-alt"></i> Paiement Sécurisé</h1>
            <p>Finalisez votre achat de solution en toute sécurité</p>
        </div>
        
        <div class="payment-body">
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <!-- Résumé de la solution -->
            <div class="solution-summary">
                <div class="solution-title">
                    <i class="fas fa-code"></i> 
                    <?php echo htmlspecialchars($solution['problem_title']); ?>
                </div>
                
                <div class="solution-meta">
                    <span>
                        <i class="fas fa-user-code"></i>
                        Développeur: <?php echo htmlspecialchars($solution['solver_name'] ?? $solution['solver_username']); ?>
                    </span>
                    <span>
                        <i class="fas fa-calendar-plus"></i>
                        Soumis le: <?php echo date('d/m/Y', strtotime($solution['created_at'])); ?>
                    </span>
                    <span>
                        <i class="fas fa-check-circle"></i>
                        Statut: <?php echo ucfirst($solution['status']); ?>
                    </span>
                    <span>
                        <i class="fas fa-star"></i>
                        Solution validée
                    </span>
                </div>
                
                <div class="solution-preview">
                    <strong>Aperçu de la solution:</strong><br>
                    <?php echo htmlspecialchars(substr($solution['solution_code'], 0, 300)) . (strlen($solution['solution_code']) > 300 ? '...' : ''); ?>
                </div>
                
                <?php if (!empty($solution['explanation'])): ?>
                    <div style="margin-top: 15px; padding: 15px; background: rgba(102, 126, 234, 0.1); border-radius: 8px; border-left: 4px solid #667eea;">
                        <strong><i class="fas fa-lightbulb"></i> Explication du développeur:</strong><br>
                        <?php echo nl2br(htmlspecialchars(substr($solution['explanation'], 0, 200))) . (strlen($solution['explanation']) > 200 ? '...' : ''); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Détail des prix -->
            <div class="price-breakdown">
                <h3><i class="fas fa-calculator"></i> Récapitulatif du paiement</h3>
                
                <div class="price-row">
                    <span><i class="fas fa-code"></i> Prix de la solution</span>
                    <span><?php echo number_format($solution['amount'], 2); ?> €</span>
                </div>
                
                <div class="price-row">
                    <span><i class="fas fa-gift"></i> <strong>Total à payer</strong></span>
                    <span><strong><?php echo number_format($solution['amount'], 2); ?> €</strong></span>
                </div>
            </div>
            
            <!-- Avantages -->
            <div class="features-list">
                <h4><i class="fas fa-star"></i> Ce que vous obtenez</h4>
                <ul>
                    <li><i class="fas fa-check"></i> Code source complet et fonctionnel</li>
                    <li><i class="fas fa-check"></i> Explication détaillée du développeur</li>
                    <li><i class="fas fa-check"></i> Accès immédiat après paiement</li>
                    <li><i class="fas fa-check"></i> Téléchargement illimité</li>
                    <li><i class="fas fa-check"></i> Support technique inclus</li>
                </ul>
            </div>
            
            <!-- Google Pay Section -->
            <div class="google-pay-section" id="google-pay-section" style="display: none;">
                <h4><i class="fab fa-google-pay"></i> Paiement Express avec Google Pay</h4>
                <p>Paiement rapide et sécurisé en un clic</p>
                <div id="google-pay-container">
                    <!-- Le bouton Google Pay sera inséré ici -->
                </div>
            </div>
            
            <!-- Formulaire de paiement -->
            <form method="POST" id="payment-form">
                <input type="hidden" name="process_payment" value="1">
                
                <!-- Méthodes de paiement -->
                <div class="payment-methods">
                    <h3><i class="fas fa-credit-card"></i> Choisissez votre méthode de paiement</h3>
                    
                    <div class="payment-method" onclick="selectPaymentMethod('card')">
                        <input type="radio" name="payment_method" value="card" id="payment-card">
                        <div class="payment-method-icon card">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <div class="payment-method-info">
                            <div class="payment-method-name">Carte bancaire</div>
                            <div class="payment-method-desc">Visa, Mastercard, American Express<br>Paiement sécurisé SSL 256 bits</div>
                        </div>
                    </div>
                    
                    <div class="card-form" id="card-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="card-number"><i class="fas fa-credit-card"></i> Numéro de carte</label>
                                <input type="text" id="card-number" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="card-name"><i class="fas fa-user"></i> Nom sur la carte</label>
                                <input type="text" id="card-name" name="card_name" placeholder="Jean Dupont">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="card-expiry"><i class="fas fa-calendar"></i> Date d'expiration</label>
                                <input type="text" id="card-expiry" name="card_expiry" placeholder="MM/AA" maxlength="5">
                            </div>
                            <div class="form-group">
                                <label for="card-cvv"><i class="fas fa-lock"></i> CVV</label>
                                <input type="text" id="card-cvv" name="card_cvv" placeholder="123" maxlength="4">
                            </div>
                        </div>
                    </div>
                    
                    <div class="payment-method" onclick="selectPaymentMethod('paypal')">
                        <input type="radio" name="payment_method" value="paypal" id="payment-paypal">
                        <div class="payment-method-icon paypal">
                            <i class="fab fa-paypal"></i>
                        </div>
                        <div class="payment-method-info">
                            <div class="payment-method-name">PayPal</div>
                            <div class="payment-method-desc">Paiement sécurisé avec votre compte PayPal<br>Protection des achats incluse</div>
                        </div>
                    </div>
                    
                    <div class="payment-method" onclick="selectPaymentMethod('crypto')">
                        <input type="radio" name="payment_method" value="crypto" id="payment-crypto">
                        <div class="payment-method-icon crypto">
                            <i class="fab fa-bitcoin"></i>
                        </div>
                        <div class="payment-method-info">
                            <div class="payment-method-name">Cryptomonnaie</div>
                            <div class="payment-method-desc">Bitcoin, Ethereum, Litecoin<br>Paiement anonyme et décentralisé</div>
                        </div>
                    </div>
                </div>
                
                <!-- Informations de sécurité -->
                <div class="security-info">
                    <i class="fas fa-shield-alt"></i>
                    <div class="security-info-text">
                        <strong>Paiement 100% sécurisé</strong><br>
                        Vos informations sont protégées par un cryptage SSL 256 bits et ne sont jamais stockées sur nos serveurs.
                        Nous respectons les normes PCI DSS pour la sécurité des paiements.
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="payment-actions">
                    <a href="user_feedback.php<?php echo $debug_mode ? '?debug=1' : ''; ?>" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Retour aux solutions
                    </a>
                    
                    <button type="submit" class="btn btn-primary" id="pay-button">
                        <i class="fas fa-lock"></i> Payer <?php echo number_format($solution['amount'], 2); ?> € maintenant
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Overlay de chargement -->
<div class="loading-overlay" id="loading-overlay">
    <div class="loading-content">
        <div class="loading-spinner"></div>
        <h3>Traitement du paiement...</h3>
        <p>Veuillez patienter, ne fermez pas cette page.</p>
        <div style="margin-top: 15px; font-size: 12px; color: #6c757d;">
            <i class="fas fa-shield-alt"></i> Transaction sécurisée
        </div>
    </div>
</div>

<!-- Chargement de l'API Google Pay -->
<script async
    src="https://pay.google.com/gp/p/js/pay.js"
    onload="onGooglePayLoaded()">
</script>

<script src="assets/js/google-pay-integration.js"></script>

<?php
$additional_scripts = "
    // Debug au chargement de la page
    " . ($debug_mode ? "
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🔧 DEBUG payment.php chargé');
        console.log('Solution ID: " . $solution_id . "');
        console.log('User ID: " . $user_id . "');
        console.log('Solution Status: " . ($solution['status'] ?? 'N/A') . "');
        console.log('Amount: " . ($solution['amount'] ?? 'N/A') . "');
        console.log('Problem Title: " . addslashes($solution['problem_title'] ?? 'N/A') . "');
        
        window.debugPaymentPage = function() {
            console.log('✅ Page de paiement fonctionnelle');
            console.log('📋 Données de la solution:', {
                id: " . $solution_id . ",
                status: '" . ($solution['status'] ?? 'N/A') . "',
                amount: " . ($solution['amount'] ?? 0) . ",
                title: '" . addslashes($solution['problem_title'] ?? 'N/A') . "'
            });
            return true;
        };
        
        console.log('💡 Tapez debugPaymentPage() pour vérifier le fonctionnement');
    });
    " : "") . "
    
    // Fonction Google Pay
    function onGooglePayLoaded() {
        console.log('🔧 DIAGNOSTIC: Google Pay API loaded');
        
        const solutionId = " . $solution_id . ";
        const price = " . ($solution['amount'] ?? 0) . ";
        
        console.log('🔧 DIAGNOSTIC: Initializing Google Pay with solution ID:', solutionId, 'price:', price);
        
        if (typeof initializeGooglePay === 'function') {
            initializeGooglePay(solutionId, price);
            document.getElementById('google-pay-section').style.display = 'block';
        } else {
            console.error('❌ DIAGNOSTIC: initializeGooglePay function not found');
        }
    }
    
    // Fallback si Google Pay ne se charge pas
    setTimeout(() => {
        if (typeof google === 'undefined') {
            console.warn('⚠️ DIAGNOSTIC: Google Pay API failed to load');
            const section = document.getElementById('google-pay-section');
            if (section) {
                section.innerHTML = '<p style=\"color: #666; font-style: italic; text-align: center;\"><i class=\"fas fa-info-circle\"></i> Google Pay temporairement indisponible</p>';
                section.style.display = 'block';
            }
        }
    }, 5000);
    
    // Sélection des méthodes de paiement
    function selectPaymentMethod(method) {
        // Désélectionner toutes les méthodes
        document.querySelectorAll('.payment-method').forEach(el => {
            el.classList.remove('selected');
        });
        
        // Masquer tous les formulaires
        document.querySelectorAll('.card-form').forEach(el => {
            el.classList.remove('active');
        });
        
        // Sélectionner la méthode choisie
        event.currentTarget.classList.add('selected');
        document.getElementById('payment-' + method).checked = true;
        
        // Afficher le formulaire correspondant
        if (method === 'card') {
            document.getElementById('card-form').classList.add('active');
            // Focus sur le premier champ
            setTimeout(() => {
                document.getElementById('card-number').focus();
            }, 300);
        }
        
        " . ($debug_mode ? "console.log('DEBUG: Méthode de paiement sélectionnée:', method);" : "") . "
    }
    
    // Formatage automatique des champs de carte
    document.addEventListener('DOMContentLoaded', function() {
        const cardNumber = document.getElementById('card-number');
        const cardExpiry = document.getElementById('card-expiry');
        const cardCvv = document.getElementById('card-cvv');
        
        // Formatage du numéro de carte
        if (cardNumber) {
            cardNumber.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
                let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
                if (formattedValue.length > 19) formattedValue = formattedValue.substr(0, 19);
                e.target.value = formattedValue;
                
                // Détection du type de carte
                const cardType = detectCardType(value);
                updateCardIcon(cardType);
            });
            
            cardNumber.addEventListener('paste', function(e) {
                setTimeout(() => {
                    const event = new Event('input');
                    e.target.dispatchEvent(event);
                }, 10);
            });
        }
        
        // Formatage de la date d'expiration
        if (cardExpiry) {
            cardExpiry.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                if (value.length >= 2) {
                    value = value.substring(0, 2) + '/' + value.substring(2, 4);
                }
                e.target.value = value;
            });
        }
        
        // Validation CVV
        if (cardCvv) {
            cardCvv.addEventListener('input', function(e) {
                e.target.value = e.target.value.replace(/[^0-9]/g, '');
            });
        }
    });
    
    // Détection du type de carte
    function detectCardType(number) {
        const patterns = {
            visa: /^4/,
            mastercard: /^5[1-5]/,
            amex: /^3[47]/,
            discover: /^6(?:011|5)/
        };
        
        for (let type in patterns) {
            if (patterns[type].test(number)) {
                return type;
            }
        }
        return 'unknown';
    }
    
    // Mise à jour de l'icône de carte
    function updateCardIcon(cardType) {
        const cardIcon = document.querySelector('.payment-method-icon.card i');
        if (cardIcon) {
            switch(cardType) {
                case 'visa':
                    cardIcon.className = 'fab fa-cc-visa';
                    break;
                case 'mastercard':
                    cardIcon.className = 'fab fa-cc-mastercard';
                    break;
                case 'amex':
                    cardIcon.className = 'fab fa-cc-amex';
                    break;
                case 'discover':
                    cardIcon.className = 'fab fa-cc-discover';
                    break;
                default:
                    cardIcon.className = 'fas fa-credit-card';
            }
        }
    }
    
    // Validation du formulaire
    document.getElementById('payment-form').addEventListener('submit', function(e) {
        const paymentMethod = document.querySelector('input[name=\"payment_method\"]:checked');
        
        " . ($debug_mode ? "console.log('DEBUG: Soumission du formulaire de paiement');" : "") . "
        
        if (!paymentMethod) {
            e.preventDefault();
            showNotification('Veuillez sélectionner une méthode de paiement.', 'error');
            " . ($debug_mode ? "console.log('DEBUG: Aucune méthode de paiement sélectionnée');" : "") . "
            return false;
        }
        
        " . ($debug_mode ? "console.log('DEBUG: Méthode sélectionnée:', paymentMethod.value);" : "") . "
        
        if (paymentMethod.value === 'card') {
            const cardNumber = document.getElementById('card-number').value.replace(/\s/g, '');
            const cardName = document.getElementById('card-name').value.trim();
            const cardExpiry = document.getElementById('card-expiry').value;
            const cardCvv = document.getElementById('card-cvv').value;
            
            if (cardNumber.length < 13 || cardNumber.length > 19) {
                e.preventDefault();
                showNotification('Numéro de carte invalide.', 'error');
                document.getElementById('card-number').focus();
                " . ($debug_mode ? "console.log('DEBUG: Numéro de carte invalide:', cardNumber.length);" : "") . "
                return false;
            }
            
            if (cardName.length < 2) {
                e.preventDefault();
                showNotification('Nom sur la carte requis.', 'error');
                document.getElementById('card-name').focus();
                " . ($debug_mode ? "console.log('DEBUG: Nom sur la carte manquant');" : "") . "
                return false;
            }
            
            if (!/^\d{2}\/\d{2}$/.test(cardExpiry)) {
                e.preventDefault();
                showNotification('Date d\\'expiration invalide (MM/AA).', 'error');
                document.getElementById('card-expiry').focus();
                " . ($debug_mode ? "console.log('DEBUG: Date d\\'expiration invalide:', cardExpiry);" : "") . "
                return false;
            }
            
            // Validation de la date d'expiration
            const [month, year] = cardExpiry.split('/');
            const expDate = new Date(2000 + parseInt(year), parseInt(month) - 1);
            const now = new Date();
            
            if (expDate < now) {
                e.preventDefault();
                showNotification('Votre carte a expiré.', 'error');
                document.getElementById('card-expiry').focus();
                return false;
            }
            
            if (cardCvv.length < 3 || cardCvv.length > 4) {
                e.preventDefault();
                showNotification('CVV invalide.', 'error');
                document.getElementById('card-cvv').focus();
                " . ($debug_mode ? "console.log('DEBUG: CVV invalide:', cardCvv.length);" : "") . "
                return false;
            }
            
            // Validation Luhn pour le numéro de carte
            if (!validateCardNumber(cardNumber)) {
                e.preventDefault();
                showNotification('Numéro de carte invalide.', 'error');
                document.getElementById('card-number').focus();
                return false;
            }
        }
        
        // Afficher l'overlay de chargement
        document.getElementById('loading-overlay').style.display = 'flex';
        document.getElementById('pay-button').disabled = true;
        
        // Désactiver tous les champs du formulaire
        const formElements = document.querySelectorAll('#payment-form input, #payment-form button');
        formElements.forEach(element => {
            element.disabled = true;
        });
        
        " . ($debug_mode ? "console.log('DEBUG: Formulaire valide, traitement en cours...');" : "") . "
        
        // Simuler un délai de traitement
        setTimeout(() => {
            " . ($debug_mode ? "console.log('DEBUG: Soumission du formulaire après délai');" : "") . "
        }, 1000);
    });
    
    // Validation Luhn pour les numéros de carte
    function validateCardNumber(number) {
        let sum = 0;
        let shouldDouble = false;
        
        for (let i = number.length - 1; i >= 0; i--) {
            let digit = parseInt(number.charAt(i));
            
            if (shouldDouble) {
                digit *= 2;
                if (digit > 9) {
                    digit -= 9;
                }
            }
            
            sum += digit;
            shouldDouble = !shouldDouble;
        }
        
        return sum % 10 === 0;
    }
    
    // Système de notifications
    function showNotification(message, type = 'info', duration = 5000) {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            z-index: 10000;
            max-width: 350px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateX(100%);
            transition: transform 0.3s ease;
            word-wrap: break-word;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
        `;
        
        let icon = '';
        switch(type) {
            case 'success':
                notification.style.background = 'linear-gradient(135deg, #28a745 0%, #20c997 100%)';
                icon = '<i class=\"fas fa-check-circle\"></i>';
                break;
            case 'error':
                notification.style.background = 'linear-gradient(135deg, #dc3545 0%, #c82333 100%)';
                icon = '<i class=\"fas fa-exclamation-circle\"></i>';
                break;
            case 'warning':
                notification.style.background = 'linear-gradient(135deg, #ffc107 0%, #e0a800 100%)';
                notification.style.color = '#212529';
                icon = '<i class=\"fas fa-exclamation-triangle\"></i>';
                break;
            default:
                notification.style.background = 'linear-gradient(135deg, #17a2b8 0%, #138496 100%)';
                icon = '<i class=\"fas fa-info-circle\"></i>';
        }
        
        notification.innerHTML = icon + ' ' + message;
        document.body.appendChild(notification);
        
        // Animation d'entrée
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Suppression automatique
        const autoRemove = setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, duration);
        
        // Permettre la fermeture manuelle
        notification.addEventListener('click', () => {
            clearTimeout(autoRemove);
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        });
    }
    
    // Animation des éléments au chargement
    document.addEventListener('DOMContentLoaded', function() {
        const elements = document.querySelectorAll('.solution-summary, .price-breakdown, .payment-method, .security-info');
        elements.forEach((el, index) => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(30px)';
            
            setTimeout(() => {
                el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            }, index * 100);
        });
        
        // Message de bienvenue
        setTimeout(() => {
            showNotification('Paiement sécurisé SSL 256 bits', 'info', 3000);
        }, 1000);
    });
    
    // Gestion des erreurs de réseau
    window.addEventListener('online', function() {
        document.getElementById('loading-overlay').style.display = 'none';
        document.getElementById('pay-button').disabled = false;
        showNotification('Connexion rétablie', 'success', 3000);
        " . ($debug_mode ? "console.log('DEBUG: Connexion rétablie');" : "") . "
    });
    
    window.addEventListener('offline', function() {
        showNotification('Connexion internet perdue. Veuillez vérifier votre connexion.', 'error', 8000);
        " . ($debug_mode ? "console.log('DEBUG: Connexion perdue');" : "") . "
    });
    
    // Prévention de la fermeture accidentelle pendant le paiement
    let paymentInProgress = false;
    
    document.getElementById('payment-form').addEventListener('submit', function() {
        paymentInProgress = true;
    });
    
    window.addEventListener('beforeunload', function(e) {
        if (paymentInProgress) {
            const message = 'Un paiement est en cours. Êtes-vous sûr de vouloir quitter cette page ?';
            e.returnValue = message;
            return message;
        }
    });
    
    // Raccourcis clavier
    document.addEventListener('keydown', function(e) {
        // Échap pour annuler
        if (e.key === 'Escape' && paymentInProgress) {
            if (confirm('Annuler le paiement en cours ?')) {
                window.location.href = 'user_feedback.php';
            }
        }
        
        // Entrée pour payer (si une méthode est sélectionnée)
        if (e.key === 'Enter' && e.ctrlKey) {
            const selectedMethod = document.querySelector('input[name=\"payment_method\"]:checked');
            if (selectedMethod && !paymentInProgress) {
                document.getElementById('pay-button').click();
            }
        }
    });
    
    // Fonction de test pour simuler un paiement (debug uniquement)
    " . ($debug_mode ? "
    window.simulatePayment = function(method = 'card') {
        console.log('🧪 Simulation de paiement avec méthode:', method);
        
        // Sélectionner la méthode
        selectPaymentMethod(method);
        
        if (method === 'card') {
            // Remplir les champs de test
            document.getElementById('card-number').value = '4111 1111 1111 1111';
            document.getElementById('card-name').value = 'Test User';
            document.getElementById('card-expiry').value = '12/25';
            document.getElementById('card-cvv').value = '123';
        }
        
        console.log('✅ Formulaire pré-rempli pour test');
        console.log('💡 Cliquez sur \"Payer\" pour tester la transaction');
    };
    
    console.log('🧪 Fonction de test disponible: simulatePayment(\"card\"|\"paypal\"|\"crypto\")');
    " : "") . "
    
    // Fonction utilitaire pour déboguer (disponible dans la console)
    window.debugPayment = function() {
        const stats = {
            solutionId: " . $solution_id . ",
            userId: " . $user_id . ",
            amount: " . ($solution['amount'] ?? 0) . ",
            currency: '" . ($solution['currency'] ?? 'EUR') . "',
            status: '" . ($solution['status'] ?? 'N/A') . "',
            formValid: document.querySelector('input[name=\"payment_method\"]:checked') !== null,
            googlePayLoaded: typeof google !== 'undefined'
        };
        
        console.log('🔧 Informations de débogage payment.php:', stats);
        return stats;
    };
    
    // Auto-focus sur le premier champ de carte si c'est la seule méthode
    setTimeout(() => {
        const paymentMethods = document.querySelectorAll('.payment-method');
        if (paymentMethods.length === 1) {
            paymentMethods[0].click();
        }
    }, 500);
    
    // Détection de la fraude basique
    let suspiciousActivity = 0;
    
    document.addEventListener('click', function(e) {
        if (e.target.closest('.payment-method')) {
            suspiciousActivity = 0; // Reset sur sélection normale
        }
    });
    
    // Monitoring des tentatives de manipulation
    document.addEventListener('keydown', function(e) {
        if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && e.key === 'I')) {
            suspiciousActivity++;
            if (suspiciousActivity > 3) {
                " . ($debug_mode ? "console.warn('DEBUG: Activité suspecte détectée');" : "") . "
            }
        }
    });
    
    // Message d'aide pour les développeurs
    " . ($debug_mode ? "console.log('💡 Fonctions de debug disponibles: debugPayment(), simulatePayment(method)');" : "") . "
";

include 'footer.php';
?>
