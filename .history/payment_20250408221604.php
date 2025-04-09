<UPDATED_CODE><UPDATED_CODE><?php
declare(strict_types=1);
// Toujours en haut du fichier
header_remove('X-Powered-By'); // Cache le header PHP

// Configuration de sécurité
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // En HTTPS seulement
ini_set('session.use_strict_mode', 1);
session_start();

// Protection contre le clickjacking
header('X-Frame-Options: DENY');
// Protection XSS
header('X-XSS-Protection: 1; mode=block');
// Pas de MIME-sniffing
header('X-Content-Type-Options: nosniff');

require_once 'verification.php';
require_once 'db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: exlogin.php');
    exit;
}

// Récupérer l'ID de l'utilisateur connecté
$user_id = $_SESSION['user_id'];

// Définir le titre de la page
$page_title = "Espace de Paiement";

// CSS spécifique à cette page
$additional_css = "
    .payment-container {
        max-width: 800px;
        margin: 0 auto;
        background: white;
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 3px 15px rgba(0,0,0,0.1);
    }
    
    .payment-header {
        text-align: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid #eee;
    }
    
    .payment-header h2 {
        color: #2c3e50;
        margin-bottom: 10px;
    }
    
    .payment-header p {
        color: #7f8c8d;
    }
    
    .payment-tabs {
        display: flex;
        border-bottom: 1px solid #eee;
        margin-bottom: 20px;
    }
    
    .payment-tab {
        padding: 12px 20px;
        cursor: pointer;
        border-bottom: 3px solid transparent;
        color: #7f8c8d;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .payment-tab.active {
        border-bottom-color: #3498db;
        color: #3498db;
    }
    
    .payment-tab:hover {
        background-color: #f9f9f9;
    }
    
    .payment-section {
        display: none;
    }
    
    .payment-section.active {
        display: block;
        animation: fadeIn 0.5s;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }
    
    .payment-card {
        border: 1px solid #eee;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .payment-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .payment-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .payment-card-title {
        font-weight: 600;
        color: #2c3e50;
        font-size: 1.1em;
        margin: 0;
    }
    
    .payment-card-price {
        font-weight: bold;
        color: #27ae60;
        font-size: 1.2em;
    }
    
    .payment-card-details {
        margin-bottom: 15px;
        color: #666;
    }
    
    .payment-card-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .payment-card-date {
        color: #7f8c8d;
        font-size: 0.9em;
    }
    
    .payment-form {
        margin-top: 20px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
    }
    
    .form-control {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 16px;
        transition: border-color 0.3s, box-shadow 0.3s;
    }
    
    .form-control:focus {
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
        outline: none;
    }
    
    .payment-methods {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .payment-method {
        flex: 1;
        min-width: 120px;
        border: 2px solid #eee;
        border-radius: 8px;
        padding: 15px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .payment-method:hover {
        border-color: #3498db;
        background-color: #f7fbfe;
    }
    
    .payment-method.selected {
        border-color: #3498db;
        background-color: #ebf5fb;
    }
    
    .payment-method img {
        height: 40px;
        margin-bottom: 10px;
    }
    
    .payment-method-title {
        font-weight: 600;
        color: #2c3e50;
    }
    
    .btn-pay {
        background: linear-gradient(135deg, #3498db, #2980b9);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 6px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
    }
    
    .btn-pay:hover {
        background: linear-gradient(135deg, #2980b9, #3498db);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .payment-summary {
        background-color: #f9f9f9;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .summary-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #eee;
    }
    
    .summary-row:last-child {
        border-bottom: none;
        font-weight: bold;
        color: #2c3e50;
    }
    
    .payment-history-empty {
        text-align: center;
        padding: 40px 20px;
        color: #7f8c8d;
    }
    
    .payment-history-empty i {
        font-size: 3em;
        color: #ddd;
        margin-bottom: 15px;
    }
    
    .balance-card {
        background: linear-gradient(135deg, #3498db, #2980b9);
        color: white;
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .balance-title {
        font-size: 1.1em;
        margin-bottom: 5px;
        opacity: 0.9;
    }
    
    .balance-amount {
        font-size: 2.5em;
        font-weight: bold;
        margin-bottom: 15px;
    }
    
    .balance-actions {
        display: flex;
        gap: 10px;
    }
    
    .btn-outline-light {
        background: transparent;
        border: 2px solid rgba(255,255,255,0.8);
        color: white;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .btn-outline-light:hover {
        background: rgba(255,255,255,0.2);
    }
    
    .credit-card-form {
        background-color: #f9f9f9;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .card-row {
        display: flex;
        gap: 15px;
    }
    
    .card-row .form-group {
        flex: 1;
    }
    
    .card-icon {
        position: absolute;
        right: 12px;
        top: 12px;
        color: #7f8c8d;
    }
    
    .form-group-card {
        position: relative;
    }
    
    .secure-badge {
        display: flex;
        align-items: center;
        gap: 8px;
        background-color: #f0f7fb;
        padding: 10px 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        color: #3498db;
        font-size: 0.9em;
    }
";

// Initialiser les variables
$solution_id = isset($_GET['solution_id']) ? (int)$_GET['solution_id'] : 0;
$solution = null;
$pending_payments = [];
$payment_history = [];
$user_balance = 0;

try {
    $pdo = connect();
    
    // Récupérer le solde de l'utilisateur
    $stmt = $pdo->prepare("SELECT balance FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user_balance = $stmt->fetchColumn() ?: 0;
    
    // Si un ID de solution est fourni, récupérer les détails de la solution
    if ($solution_id > 0) {
        $stmt = $pdo->prepare("
            SELECT s.*, p.title as problem_title, u.username as solver_username, u.name as solver_name,
               pr.amount as price
            FROM solutions s
            JOIN problems p ON s.problem_id = p.problem_id
            JOIN users u ON s.user_id = u.id
            LEFT JOIN prices pr ON s.price_id = pr.price_id
            WHERE s.id = ? AND p.user_id = ?
        ");
        
        $stmt->execute([$solution_id, $user_id]);
        $solution = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$solution) {
            $_SESSION['error_message'] = "Solution introuvable ou vous n'êtes pas autorisé à effectuer ce paiement.";
            header('Location: user_feedback.php');
            exit;
        }
    }
    
    // Récupérer les paiements en attente (solutions acceptées mais non payées)
    $stmt = $pdo->prepare("
        SELECT s.id as solution_id, pr.amount as price, s.created_at, s.evaluated_at,
               p.problem_id, p.title as problem_title,
               u.username as solver_username, u.name as solver_name
        FROM solutions s
        JOIN problems p ON s.problem_id = p.problem_id
        JOIN users u ON s.user_id = u.id
        LEFT JOIN prices pr ON s.price_id = pr.price_id
        WHERE p.user_id = ? AND s.status = 'accepted' AND s.payment_id IS NULL
        ORDER BY s.evaluated_at DESC
    ");
    
    $stmt->execute([$user_id]);
    $pending_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer l'historique des paiements
    $stmt = $pdo->prepare("
        SELECT TOP 10 pm.*, s.id as solution_id, p.title as problem_title,
               u.username as payee_username, u.name as payee_name
        FROM payments pm
        JOIN solutions s ON pm.payment_id = s.payment_id
        JOIN problems p ON s.problem_id = p.problem_id
        JOIN users u ON s.user_id = u.id
        WHERE pm.payer_id = ?
        ORDER BY pm.payment_date DESC
    ");
    
    $stmt->execute([$user_id]);
    $payment_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Erreur détaillée lors de la récupération des données de paiement: " . $e->getMessage());
    $_SESSION['error_message'] = "Une erreur est survenue lors de la récupération des données de paiement: " . $e->getMessage();
    $payment_history = [];
}

// Traiter le paiement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    $payment_solution_id = isset($_POST['solution_id']) ? (int)$_POST['solution_id'] : 0;
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
    
    if ($payment_solution_id <= 0) {
        $_SESSION['error_message'] = "ID de solution invalide.";
    } elseif (empty($payment_method)) {
        $_SESSION['error_message'] = "Veuillez sélectionner une méthode de paiement.";
    } else {
        try {
            // Vérifier que la solution existe et appartient à un problème de l'utilisateur
            $stmt = $pdo->prepare("
                SELECT s.*, p.user_id as problem_owner, u.id as solver_id,
                       pr.amount as price
                FROM solutions s
                JOIN problems p ON s.problem_id = p.problem_id
                JOIN users u ON s.user_id = u.id
                LEFT JOIN prices pr ON s.price_id = pr.price_id
                WHERE s.id = ? AND p.user_id = ? AND s.status = 'accepted' AND s.payment_id IS NULL
            ");
            
            $stmt->execute([$payment_solution_id, $user_id]);
            $stmt->execute([$payment_solution_id, $user_id]);
            $solution_to_pay = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$solution_to_pay) {
                $_SESSION['error_message'] = "Solution introuvable ou vous n'êtes pas autorisé à effectuer ce paiement.";
            } else {
                // Créer un nouveau paiement
                $pdo->beginTransaction();
                
                // Insérer le paiement
                $stmt = $pdo->prepare("
                    INSERT INTO payments (payer_id, payee_id, amount, payment_method, payment_date, status)
                    VALUES (?, ?, ?, ?, GETDATE(), 'completed')
                ");
                
                $result = $stmt->execute([
                    $user_id,
                    $solution_to_pay['solver_id'],
                    $solution_to_pay['price'],
                    $payment_method
                ]);
                
                if ($result) {
                    $stmt = $pdo->query("SELECT SCOPE_IDENTITY() AS id");
                    $payment_id = $stmt->fetchColumn();                    
                    // Mettre à jour la solution avec l'ID du paiement
                    $stmt = $pdo->prepare("
                        UPDATE solutions
                        SET payment_id = ?, payment_date = GETDATE()
                        WHERE id = ?
                    ");
                    
                    $result = $stmt->execute([$payment_id, $payment_solution_id]);
                    
                    if ($result) {
                        // Mettre à jour le solde du développeur qui a résolu le problème
                        $stmt = $pdo->prepare("
                            UPDATE users
                            SET balance = balance + ?, earnings = earnings + ?
                            WHERE id = ?
                        ");
                        
                        $result = $stmt->execute([
                            $solution_to_pay['price'],
                            $solution_to_pay['price'],
                            $solution_to_pay['solver_id']
                        ]);
                        
                        if ($result) {
                            // Créer une notification pour le développeur
                            $stmt = $pdo->prepare("
                                INSERT INTO notifications (user_id, type, content, related_id, created_at)
                                VALUES (?, 'payment_received', ?, ?, GETDATE())
                            ");
                            
                            $notification_content = "Votre solution pour le problème a été payée " . $solution_to_pay['price'] . "€.";
                            $stmt->execute([
                                $solution_to_pay['solver_id'],
                                $notification_content,
                                $payment_solution_id
                            ]);
                            
                            $pdo->commit();
                            $_SESSION['success_message'] = "Paiement effectué avec succès !";
                            header('Location: payment.php');
                            exit;
                        } else {
                            $pdo->rollBack();
                            $_SESSION['error_message'] = "Erreur lors de la mise à jour du solde du développeur.";
                        }
                    } else {
                        $pdo->rollBack();
                        $_SESSION['error_message'] = "Erreur lors de la mise à jour de la solution.";
                    }
                } else {
                    $pdo->rollBack();
                    $_SESSION['error_message'] = "Erreur lors de la création du paiement.";
                }
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Erreur lors du traitement du paiement: " . $e->getMessage());
            $_SESSION['error_message'] = "Une erreur est survenue lors du traitement du paiement.";
        }
    }
}

// Inclure l'en-tête
include 'header.php';

// Afficher l