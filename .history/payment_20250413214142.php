<?php
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
    
    .payment-method[data-method=\"google_pay\"] {
        background-color: #f8f9fa;
    }

    .payment-method[data-method=\"google_pay\"] img {
        height: 24px;
        margin-bottom: 10px;
    }

    .payment-method[data-method=\"google_pay\"].selected {
        border-color: #4285F4;
        background-color: #e8f0fe;
    }

    .gpay-button {
        height: 40px;
        width: 100%;
        border-radius: 4px;
        background-origin: content-box;
        background-position: center center;
        background-repeat: no-repeat;
        background-size: contain;
        outline: 0;
        border: 0;
        cursor: pointer;
        padding: 0;
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
                // Si la méthode est Google Pay
                if ($payment_method === 'google_pay') {
                    // Vérifier si des données de paiement Google Pay sont présentes
                    // Dans un environnement réel, vous recevriez un token de paiement
                    // que vous traiteriez avec votre passerelle de paiement
                    
                    // Pour cet exemple, nous traitons simplement comme un paiement normal
                    // Le reste du code reste identique
                }
                
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

// Afficher l'avatar utilisateur
require_once('exavatar.php');
displayUserAvatar();
?>

<h1><i class="fas fa-credit-card"></i> Espace de Paiement</h1>

<?php if (!empty($_SESSION['error_message'])): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error_message']; ?>
    </div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['success_message'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_message']; ?>
    </div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> Paiement effectué avec succès via Google Pay !
    </div>
<?php endif; ?>

<div class="payment-container">
    <div class="payment-header">
        <h2>Gérez vos paiements</h2>
        <p>Effectuez des paiements pour les solutions acceptées et consultez votre historique</p>
    </div>
    
    <div class="balance-card">
        <div class="balance-title">Votre solde actuel</div>
        <div class="balance-amount"><?php echo number_format(floatval($user_balance), 2); ?> €</div>
        <div class="balance-actions">
            <button class="btn-outline-light" onclick="alert('Fonctionnalité à venir')">
                <i class="fas fa-plus"></i> Ajouter des fonds
            </button>
            <button class="btn-outline-light" onclick="alert('Fonctionnalité à venir')">
                <i class="fas fa-history"></i> Historique
            </button>
        </div>
    </div>
    
    <div class="payment-tabs">
        <div class="payment-tab active" data-tab="pending">Paiements en attente</div>
        <div class="payment-tab" data-tab="history">Historique des paiements</div>
        <?php if ($solution): ?>
            <div class="payment-tab" data-tab="specific">Paiement spécifique</div>
        <?php endif; ?>
    </div>
    
    <!-- Section des paiements en attente -->
    <div class="payment-section active" id="pending-section">
        <?php if (empty($pending_payments)): ?>
            <div class="payment-history-empty">
                <i class="fas fa-check-circle"></i>
                <h3>Aucun paiement en attente</h3>
                <p>Vous n'avez pas de solutions acceptées en attente de paiement.</p>
            </div>
        <?php else: ?>
            <?php foreach ($pending_payments as $payment): ?>
                <div class="payment-card">
                    <div class="payment-card-header">
                        <h3 class="payment-card-title"><?php echo htmlspecialchars($payment['problem_title']); ?></h3>
                        <div class="payment-card-price"><?php echo number_format(floatval($payment['price']), 2); ?> €</div>
                    </div>
                    
                    <div class="payment-card-details">
                        <p><strong>Développeur:</strong> <?php echo htmlspecialchars($payment['solver_name'] ?? $payment['solver_username']); ?></p>
                        <p><strong>Acceptée le:</strong> <?php echo date('d/m/Y à H:i', strtotime($payment['evaluated_at'])); ?></p>
                    </div>
                    
                    <div class="payment-card-footer">
                        <div class="payment-card-date">
                            Solution soumise le <?php echo date('d/m/Y', strtotime($payment['created_at'])); ?>
                        </div>
                        
                        <button class="btn btn-primary" onclick="showPaymentForm(<?php echo $payment['solution_id']; ?>, '<?php echo htmlspecialchars($payment['problem_title']); ?>', <?php echo $payment['price']; ?>)">
                            <i class="fas fa-credit-card"></i> Payer maintenant
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Section de l'historique des paiements -->
    <div class="payment-section" id="history-section">
        <?php if (empty($payment_history)): ?>
            <div class="payment-history-empty">
                <i class="fas fa-history"></i>
                <h3>Aucun historique de paiement</h3>
                <p>Vous n'avez pas encore effectué de paiements.</p>
            </div>
        <?php else: ?>
            <?php foreach ($payment_history as $history): ?>
                <div class="payment-card">
                    <div class="payment-card-header">
                        <h3 class="payment-card-title"><?php echo htmlspecialchars($history['problem_title']); ?></h3>
                        <div class="payment-card-price"><?php echo number_format(floatval($history['amount']), 2); ?> €</div>
                    </div>
                    
                    <div class="payment-card-details">
                        <p><strong>Payé à:</strong> <?php echo htmlspecialchars($history['payee_name'] ?? $history['payee_username']); ?></p>
                        <p><strong>Méthode:</strong> <?php echo htmlspecialchars(ucfirst($history['payment_method'])); ?></p>
                    </div>
                    
                    <div class="payment-card-footer">
                        <div class="payment-card-date">
                            Payé le <?php echo date('d/m/Y à H:i', strtotime($history['payment_date'])); ?>
                        </div>
                        
                        <span class="badge badge-success">
                            <i class="fas fa-check-circle"></i> Complété
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Section pour un paiement spécifique -->
    <?php if ($solution): ?>
    <div class="payment-section" id="specific-section">
        <div class="payment-card">
            <div class="payment-card-header">
                <h3 class="payment-card-title"><?php echo htmlspecialchars($solution['problem_title']); ?></h3>
                        <div class="payment-card-price"><?php echo number_format(floatval($solution['price']), 2); ?> €</div>
            </div>
            
            <div class="payment-card-details">
                <p><strong>Développeur:</strong> <?php echo htmlspecialchars($solution['solver_name'] ?? $solution['solver_username']); ?></p>
                <p><strong>Acceptée le:</strong> <?php echo date('d/m/Y à H:i', strtotime($solution['evaluated_at'])); ?></p>
            </div>
        </div>
        
        <div class="payment-form">
            <form method="POST" action="" id="payment-form">
                <input type="hidden" name="solution_id" value="<?php echo $solution['id']; ?>">
                <input type="hidden" name="process_payment" value="1">
                
                <div class="payment-summary">
                    <div class="summary-row">
                        <div>Solution pour "<?php echo htmlspecialchars($solution['problem_title']); ?>"</div>
                        <div><?php echo number_format($solution['price'], 2); ?> €</div>
                    </div>
                    <div class="summary-row">
                        <div>Frais de service</div>
                        <div>0.00 €</div>
                    </div>
                    <div class="summary-row">
                        <div>Total à payer</div>
                        <div><?php echo number_format($solution['price'], 2); ?> €</div>
                    </div>
                </div>
                
                <div class="secure-badge">
                    <i class="fas fa-lock"></i> Paiement sécurisé
                </div>
                
                <div class="form-group">
                    <label>Choisissez votre méthode de paiement</label>
                    <div class="payment-methods">
                        <div class="payment-method" data-method="credit_card">
                            <img src="https://cdn-icons-png.flaticon.com/512/179/179457.png" alt="Carte de crédit">
                            <div class="payment-method-title">Carte bancaire</div>
                        </div>
                        <div class="payment-method" data-method="paypal">
                            <img src="https://cdn-icons-png.flaticon.com/512/174/174861.png" alt="PayPal">
                            <div class="payment-method-title">PayPal</div>
                        </div>
                        <div class="payment-method" data-method="bank_transfer">
                            <img src="https://cdn-icons-png.flaticon.com/512/2830/2830284.png" alt="Virement bancaire">
                            <div class="payment-method-title">Virement</div>
                        </div>
                        <!-- Nouvelle méthode Google Pay -->
                        <div class="payment-method" data-method="google_pay">
                            <img src="https://developers.google.com/static/pay/api/images/brand-guidelines/google-pay-mark.png" alt="Google Pay">
                            <div class="payment-method-title">Google Pay</div>
                        </div>
                    </div>
                    <input type="hidden" name="payment_method" id="payment_method" value="">
                </div>
                
                <div id="credit-card-form" class="credit-card-form" style="display: none;">
                    <div class="form-group form-group-card">
                        <label for="card_number">Numéro de carte</label>
                        <input type="text" id="card_number" class="form-control" placeholder="1234 5678 9012 3456">
                        <i class="fas fa-credit-card card-icon"></i>
                    </div>
                    
                    <div class="card-row">
                        <div class="form-group">
                            <label for="expiry_date">Date d'expiration</label>
                            <input type="text" id="expiry_date" class="form-control" placeholder="MM/AA">
                        </div>
                        <div class="form-group">
                            <label for="cvv">CVV</label>
                            <input type="text" id="cvv" class="form-control" placeholder="123">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="card_name">Nom sur la carte</label>
                        <input type="text" id="card_name" class="form-control" placeholder="John Doe">
                    </div>
                </div>
                
                <button type="submit" class="btn-pay" id="submit-payment">
                <i class="fas fa-lock"></i> Payer <?php echo number_format(floatval($solution['price']), 2); ?> €
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Formulaire de paiement modal (caché par défaut) -->
    <div id="payment-modal" style="display: none;">
        <div class="payment-form">
            <h3 id="modal-title">Payer la solution</h3>
            
            <form method="POST" action="" id="modal-payment-form">
            <input type="hidden" name="solution_id" id="modal-solution-id" value="">
                <input type="hidden" name="process_payment" value="1">
                
                <div class="payment-summary">
                    <div class="summary-row">
                        <div>Solution pour "<span id="modal-problem-title"></span>"</div>
                        <div><span id="modal-price"></span> €</div>
                    </div>
                    <div class="summary-row">
                        <div>Frais de service</div>
                        <div>0.00 €</div>
                    </div>
                    <div class="summary-row">
                        <div>Total à payer</div>
                        <div><span id="modal-total"></span> €</div>
                    </div>
                </div>
                
                <div class="secure-badge">
                    <i class="fas fa-lock"></i> Paiement sécurisé
                </div>
                
                <div class="form-group">
                    <label>Choisissez votre méthode de paiement</label>
                    <div class="payment-methods">
                        <div class="payment-method" data-method="credit_card">
                            <img src="https://cdn-icons-png.flaticon.com/512/179/179457.png" alt="Carte de crédit">
                            <div class="payment-method-title">Carte bancaire</div>
                        </div>
                        <div class="payment-method" data-method="paypal">
                            <img src="https://cdn-icons-png.flaticon.com/512/174/174861.png" alt="PayPal">
                            <div class="payment-method-title">PayPal</div>
                        </div>
                        <div class="payment-method" data-method="bank_transfer">
                            <img src="https://cdn-icons-png.flaticon.com/512/2830/2830284.png" alt="Virement bancaire">
                            <div class="payment-method-title">Virement</div>
                        </div>
                    </div>
                    <input type="hidden" name="payment_method" id="modal-payment-method" value="">
                </div>
                
                <div id="modal-credit-card-form" class="credit-card-form" style="display: none;">
                    <div class="form-group form-group-card">
                        <label for="modal_card_number">Numéro de carte</label>
                        <input type="text" id="modal_card_number" class="form-control" placeholder="1234 5678 9012 3456">
                        <i class="fas fa-credit-card card-icon"></i>
                    </div>
                    
                    <div class="card-row">
                        <div class="form-group">
                            <label for="modal_expiry_date">Date d'expiration</label>
                            <input type="text" id="modal_expiry_date" class="form-control" placeholder="MM/AA">
                        </div>
                        <div class="form-group">
                            <label for="modal_cvv">CVV</label>
                            <input type="text" id="modal_cvv" class="form-control" placeholder="123">
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="modal_card_name">Nom sur la carte</label>
                        <input type="text" id="modal_card_name" class="form-control" placeholder="John Doe">
                    </div>
                </div>
                
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="hidePaymentModal()">Annuler</button>
                    <button type="submit" class="btn-pay" id="modal-submit-payment">
                        <i class="fas fa-lock"></i> Payer <span id="modal-button-price"></span> €
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Scripts additionnels
$additional_scripts = "
    // Gestion des onglets
    document.querySelectorAll('.payment-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            // Retirer la classe active de tous les onglets
            document.querySelectorAll('.payment-tab').forEach(t => t.classList.remove('active'));
            // Ajouter la classe active à l'onglet cliqué
            tab.classList.add('active');
            
            // Masquer toutes les sections
            document.querySelectorAll('.payment-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Afficher la section correspondante
            const tabId = tab.getAttribute('data-tab');
            document.getElementById(tabId + '-section').classList.add('active');
        });
    });
    
    // Gestion des méthodes de paiement
    document.querySelectorAll('.payment-method').forEach(method => {
        method.addEventListener('click', () => {
            // Retirer la classe selected de toutes les méthodes
            document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
            // Ajouter la classe selected à la méthode cliquée
            method.classList.add('selected');
            
            // Mettre à jour le champ caché
            const paymentMethod = method.getAttribute('data-method');
            document.getElementById('payment_method').value = paymentMethod;
            if (document.getElementById('modal-payment-method')) {
                document.getElementById('modal-payment-method').value = paymentMethod;
            }
            
            // Afficher/masquer le formulaire de carte de crédit
            const creditCardForm = document.getElementById('credit-card-form');
            if (creditCardForm) {
                creditCardForm.style.display = paymentMethod === 'credit_card' ? 'block' : 'none';
            }
            
            const modalCreditCardForm = document.getElementById('modal-credit-card-form');
            if (modalCreditCardForm) {
                modalCreditCardForm.style.display = paymentMethod === 'credit_card' ? 'block' : 'none';
            }
        });
    });
    
    // Validation du formulaire de paiement
    document.querySelectorAll('#payment-form, #modal-payment-form').forEach(form => {
        form.addEventListener('submit', (e) => {
            const paymentMethod = form.querySelector('input[name=\"payment_method\"]').value;
            
            if (!paymentMethod) {
                e.preventDefault();
                alert('Veuillez sélectionner une méthode de paiement.');
                return;
            }
            
            if (paymentMethod === 'credit_card') {
                // Simuler la validation de carte (dans un environnement réel, utilisez une bibliothèque comme Stripe)
                const cardNumber = form.querySelector('#card_number') || form.querySelector('#modal_card_number');
                const expiryDate = form.querySelector('#expiry_date') || form.querySelector('#modal_expiry_date');
                const cvv = form.querySelector('#cvv') || form.querySelector('#modal_cvv');
                const cardName = form.querySelector('#card_name') || form.querySelector('#modal_card_name');
                
                if (!cardNumber.value || !expiryDate.value || !cvv.value || !cardName.value) {
                    e.preventDefault();
                    alert('Veuillez remplir tous les champs de la carte de crédit.');
                    return;
                }
                
                // Ici, vous pourriez ajouter une validation plus poussée des champs de carte
            }
            
            // Si tout est valide, afficher un message de chargement
            const submitButton = form.querySelector('button[type=\"submit\"]');
            submitButton.innerHTML = '<i class=\"fas fa-spinner fa-spin\"></i> Traitement en cours...';
            submitButton.disabled = true;
        });
    });
    
    // Fonction pour afficher le formulaire de paiement modal
    function showPaymentForm(solutionId, problemTitle, price) {
        // Mettre à jour les valeurs du formulaire
        document.getElementById('modal-solution-id').value = solutionId;
        document.getElementById('modal-problem-title').textContent = problemTitle;
        document.getElementById('modal-price').textContent = price.toFixed(2);
        document.getElementById('modal-total').textContent = price.toFixed(2);
        document.getElementById('modal-button-price').textContent = price.toFixed(2);
        
        // Afficher le modal avec animation
        const modal = document.getElementById('payment-modal');
        modal.style.display = 'block';
        
        // Ajouter une classe pour l'animation
        setTimeout(() => {
            modal.classList.add('active');
        }, 10);
        
        // Ajouter un overlay pour le fond
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        overlay.onclick = hidePaymentModal;
        document.body.appendChild(overlay);
        
        // Empêcher le défilement du body
        document.body.style.overflow = 'hidden';
    }
    
    // Fonction pour masquer le formulaire de paiement modal
    function hidePaymentModal() {
        const modal = document.getElementById('payment-modal');
        modal.classList.remove('active');
        
        // Supprimer l'overlay
        const overlay = document.querySelector('.modal-overlay');
        if (overlay) {
            overlay.remove();
        }
        
        // Réactiver le défilement du body
        document.body.style.overflow = '';
        
        // Masquer le modal après l'animation
        setTimeout(() => {
            modal.style.display = 'none';
        }, 300);
    }
    
    // Formater les champs de carte de crédit
    function setupCardFormatting() {
        // Formater le numéro de carte
        const cardNumberInputs = document.querySelectorAll('#card_number, #modal_card_number');
        cardNumberInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                let value = this.value.replace(/\\D/g, '');
                if (value.length > 16) value = value.slice(0, 16);
                let formattedValue = '';
                for (let i = 0; i < value.length; i++) {
                    if (i > 0 && i % 4 === 0) formattedValue += ' ';
                    formattedValue += value[i];
                }
                this.value = formattedValue;
            });
        });
        
        // Formater la date d'expiration
        const expiryDateInputs = document.querySelectorAll('#expiry_date, #modal_expiry_date');
        expiryDateInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                let value = this.value.replace(/\\D/g, '');
                if (value.length > 4) value = value.slice(0, 4);
                let formattedValue = '';
                for (let i = 0; i < value.length; i++) {
                    if (i === 2) formattedValue += '/';
                    formattedValue += value[i];
                }
                this.value = formattedValue;
            });
        });
        
        // Limiter le CVV à 3 ou 4 chiffres
        const cvvInputs = document.querySelectorAll('#cvv, #modal_cvv');
        cvvInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                let value = this.value.replace(/\\D/g, '');
                if (value.length > 4) value = value.slice(0, 4);
                this.value = value;
            });
        });
    }
    
    // Initialiser le formatage des cartes
    setupCardFormatting();
    
    // Animation des cartes de paiement
    document.querySelectorAll('.payment-card').forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-5px)';
            card.style.boxShadow = '0 8px 20px rgba(0,0,0,0.15)';
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(-3px)';
            card.style.boxShadow = '0 5px 15px rgba(0,0,0,0.1)';
        });
    });
    
    // Configuration de Google Pay
    const googlePayClient = new google.payments.api.PaymentsClient({
        environment: 'TEST' // Utilisez 'PRODUCTION' pour l'environnement de production
    });

    // Vérifier si Google Pay est disponible
    function checkGooglePayAvailability() {
        const isReadyToPayRequest = {
            apiVersion: 2,
            apiVersionMinor: 0,
            allowedPaymentMethods: [{
                type: 'CARD',
                parameters: {
                    allowedAuthMethods: ['PAN_ONLY', 'CRYPTOGRAM_3DS'],
                    allowedCardNetworks: ['MASTERCARD', 'VISA']
                }
            }]
        };

        googlePayClient.isReadyToPay(isReadyToPayRequest)
            .then(function(response) {
                if (response.result) {
                    // Google Pay est disponible, afficher le bouton
                    document.querySelectorAll('.payment-method[data-method=\"google_pay\"]').forEach(method => {
                        method.style.display = 'block';
                    });
                } else {
                    // Google Pay n'est pas disponible, masquer le bouton
                    document.querySelectorAll('.payment-method[data-method=\"google_pay\"]').forEach(method => {
                        method.style.display = 'none';
                    });
                }
            })
            .catch(function(err) {
                console.error('Erreur lors de la vérification de Google Pay:', err);
                // Masquer le bouton en cas d'erreur
                document.querySelectorAll('.payment-method[data-method=\"google_pay\"]').forEach(method => {
                    method.style.display = 'none';
                });
            });
    }

    // Fonction pour créer la requête de paiement Google Pay
    function createGooglePayRequest(price) {
        return {
            apiVersion: 2,
            apiVersionMinor: 0,
            allowedPaymentMethods: [{
                type: 'CARD',
                parameters: {
                    allowedAuthMethods: ['PAN_ONLY', 'CRYPTOGRAM_3DS'],
                    allowedCardNetworks: ['MASTERCARD', 'VISA']
                },
                tokenizationSpecification: {
                    type: 'PAYMENT_GATEWAY',
                    parameters: {
                        'gateway': 'example',
                        'gatewayMerchantId': 'exampleGatewayMerchantId'
                    }
                }
            }],
            merchantInfo: {
                merchantId: 'votre-merchant-id',
                merchantName: 'Votre Nom Marchand'
            },
            transactionInfo: {
                totalPriceStatus: 'FINAL',
                totalPrice: price.toString(),
                currencyCode: 'EUR',
                countryCode: 'FR'
            }
        };
    }

    // Fonction pour traiter le paiement Google Pay
    function processGooglePayPayment(paymentData, solutionId) {
        return fetch('api/process_google_pay.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                paymentData: paymentData,
                solutionId: solutionId
            })
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(data => {
                    throw new Error(data.error || 'Erreur lors du traitement du paiement');
                });
            }
            return response.json();
        });
    }

    // Ajouter un gestionnaire d'événements pour Google Pay
    document.querySelectorAll('.payment-method[data-method=\"google_pay\"]').forEach(method => {
        method.addEventListener('click', () => {
            // Retirer la classe selected de toutes les méthodes
            document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
            // Ajouter la classe selected à la méthode cliquée
            method.classList.add('selected');
            
            // Mettre à jour le champ caché
            document.getElementById('payment_method').value = 'google_pay';
            if (document.getElementById('modal-payment-method')) {
                document.getElementById('modal-payment-method').value = 'google_pay';
            }
            
            // Masquer le formulaire de carte de crédit
            const creditCardForm = document.getElementById('credit-card-form');
            if (creditCardForm) {
                creditCardForm.style.display = 'none';
            }
            
            const modalCreditCardForm = document.getElementById('modal-credit-card-form');
            if (modalCreditCardForm) {
                modalCreditCardForm.style.display = 'none';
            }
        });
    });

    // Modifier la validation du formulaire pour gérer Google Pay
    document.querySelectorAll('#payment-form, #modal-payment-form').forEach(form => {
        form.addEventListener('submit', (e) => {
            const paymentMethod = form.querySelector('input[name=\"payment_method\"]').value;
            
            if (paymentMethod === 'google_pay') {
                e.preventDefault();
                
                // Récupérer l'ID de la solution
                let solutionId;
                if (form.id === 'payment-form') {
                    solutionId = form.querySelector('input[name=\"solution_id\"]').value;
                } else {
                    solutionId = document.getElementById('modal-solution-id').value;
                }
                
                // Récupérer le prix
                let price = 0;
                if (form.id === 'payment-form') {
                    price = parseFloat(document.querySelector('.payment-card-price').textContent);
                } else {
                    price = parseFloat(document.getElementById('modal-total').textContent);
                }
                
                // Afficher un indicateur de chargement
                const submitButton = form.querySelector('button[type=\"submit\"]');
                submitButton.innerHTML = '<i class=\"fas fa-spinner fa-spin\"></i> Traitement en cours...';
                submitButton.disabled = true;
                
                // Créer la requête de paiement
                const paymentRequest = createGooglePayRequest(price);
                
                // Lancer le flux de paiement Google Pay
                googlePayClient.loadPaymentData(paymentRequest)
                    .then(paymentData => {
                        // Traiter le paiement via notre API
                        return processGooglePayPayment(paymentData, solutionId);
                    })
                    .then(response => {
                        // Rediriger vers la page de paiement avec un message de succès
                        window.location.href = 'payment.php?success=1';
                    })
                    .catch(err => {
                        console.error('Erreur lors du paiement Google Pay:', err);
                        alert('Une erreur est survenue lors du paiement avec Google Pay: ' + err.message);
                        
                        // Réinitialiser le bouton
                        submitButton.innerHTML = '<i class=\"fas fa-lock\"></i> Payer';
                        submitButton.disabled = false;
                    });
            }
        });
    });

    // Vérifier la disponibilité de Google Pay au chargement de la page
    document.addEventListener('DOMContentLoaded', checkGooglePayAvailability);
";

// Inclure le pied de page
include 'footer.php';
?>
<div id="google-pay-container" style="display: none; margin-top: 20px; text-align: center;"></div>

<script src="https://pay.google.com/gp/p/js/pay.js"></script>
<script src="assets/js/google-pay-integration.js"></script>
<script>
  // Appeler cette fonction lorsque la page est chargée
  document.addEventListener('DOMContentLoaded', function() {
    <?php if ($solution): ?>
      // Pour le paiement spécifique
      checkGooglePayAvailability(
        <?php echo $solution['id']; ?>, 
        <?php echo floatval($solution['price']); ?>
      );
    <?php endif; ?>
    
    // Pour les paiements en attente
    document.querySelectorAll('.payment-card').forEach(card => {
      const payButton = card.querySelector('.btn-primary');
      if (payButton) {
        const solutionId = payButton.getAttribute('onclick').match(/showPaymentForm\((\d+)/)[1];
        const priceElement = card.querySelector('.payment-card-price');
        if (priceElement) {
          const price = parseFloat(priceElement.textContent);
          
          // Ajouter un bouton Google Pay à chaque carte de paiement
          const gpayContainer = document.createElement('div');
          gpayContainer.className = 'gpay-button-container';
          gpayContainer.style.marginTop = '10px';
          
          card.querySelector('.payment-card-footer').appendChild(gpayContainer);
          
          // Initialiser le bouton Google Pay pour cette carte
          const tempClient = new google.payments.api.PaymentsClient({
            environment: 'PRODUCTION'
          });
          
          const gpayButton = tempClient.createButton({
            onClick: () => onGooglePaymentButtonClicked(solutionId, price),
            buttonColor: 'black',
            buttonType: 'pay',
            buttonSizeMode: 'static'
          });
          
          gpayContainer.appendChild(gpayButton);
        }
      }
    });
  });
  
  // Fonction pour afficher le formulaire de paiement modal avec Google Pay
  function showPaymentForm(solutionId, problemTitle, price) {
    // Code existant pour afficher le modal
    document.getElementById('modal-solution-id').value = solutionId;
    document.getElementById('modal-problem-title').textContent = problemTitle;
    document.getElementById('modal-price').textContent = price.toFixed(2);
    document.getElementById('modal-total').textContent = price.toFixed(2);
    document.getElementById('modal-button-price').textContent = price.toFixed(2);
    
    const modal = document.getElementById('payment-modal');
    modal.style.display = 'block';
    
    setTimeout(() => {
      modal.classList.add('active');
    }, 10);
    
    const overlay = document.createElement('div');
    overlay.className = 'modal-overlay';
    overlay.onclick = hidePaymentModal;
    document.body.appendChild(overlay);
    
    document.body.style.overflow = 'hidden';
    
    // Initialiser Google Pay pour ce paiement spécifique
    checkGooglePayAvailability(solutionId, price);
  }
</script>
