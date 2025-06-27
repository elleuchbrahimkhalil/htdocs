<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Vérifier si l'ID de la solution est fourni
if (!isset($_GET['solution_id']) || !is_numeric($_GET['solution_id'])) {
    $_SESSION['error_message'] = "Solution non spécifiée.";
    header('Location: user_feedback.php');
    exit;
}

$solution_id = (int)$_GET['solution_id'];
$user_id = $_SESSION['user_id'];

// Configuration de la page
$page_title = "Paiement de Solution";
$additional_css = "
    .payment-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .payment-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        overflow: hidden;
        margin-bottom: 30px;
    }
    
    .payment-header {
        background: linear-gradient(135deg, #3498db, #2980b9);
        color: white;
        padding: 30px;
        text-align: center;
    }
    
    .payment-header h1 {
        margin: 0 0 10px 0;
        font-size: 28px;
    }
    
    .payment-header p {
        margin: 0;
        opacity: 0.9;
        font-size: 16px;
    }
    
    .payment-body {
        padding: 30px;
    }
    
    .solution-summary {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
        border-left: 4px solid #3498db;
    }
    
    .solution-title {
        font-size: 20px;
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 10px;
    }
    
    .solution-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 15px;
        font-size: 14px;
        color: #666;
    }
    
    .solution-meta span {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .solution-preview {
        background: #f1f2f6;
        padding: 15px;
        border-radius: 6px;
        font-family: 'Courier New', monospace;
        font-size: 13px;
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid #e0e0e0;
    }
    
    .price-breakdown {
        background: white;
        border: 2px solid #27ae60;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
    }
    
    .price-breakdown h3 {
        color: #27ae60;
        margin-top: 0;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .price-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #eee;
    }
    
    .price-row:last-child {
        border-bottom: none;
        font-weight: bold;
        font-size: 18px;
        color: #27ae60;
        border-top: 2px solid #27ae60;
        margin-top: 10px;
        padding-top: 15px;
    }
    
    .payment-methods {
        margin-bottom: 30px;
    }
    
    .payment-methods h3 {
        color: #2c3e50;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .payment-method {
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .payment-method:hover {
        border-color: #3498db;
        background: #f8f9fa;
    }
    
    .payment-method.selected {
        border-color: #3498db;
        background: #ebf3fd;
    }
    
    .payment-method input[type='radio'] {
        margin: 0;
    }
    
    .payment-method-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        color: white;
    }
    
    .payment-method-icon.card {
        background: #3498db;
    }
    
    .payment-method-icon.paypal {
        background: #0070ba;
    }
    
    .payment-method-icon.crypto {
        background: #f7931a;
    }
    
    .payment-method-info {
        flex: 1;
    }
    
    .payment-method-name {
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 5px;
    }
    
    .payment-method-desc {
        color: #666;
        font-size: 14px;
    }
    
    .card-form {
        display: none;
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-top: 15px;
        border: 1px solid #e0e0e0;
    }
    
    .card-form.active {
        display: block;
        animation: slideDown 0.3s ease;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .form-row {
        display: flex;
        gap: 15px;
        margin-bottom: 15px;
    }
    
    .form-group {
        flex: 1;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 600;
        color: #2c3e50;
    }
    
    .form-group input {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 14px;
        transition: border-color 0.3s;
    }
    
    .form-group input:focus {
        border-color: #3498db;
        outline: none;
        box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
    }
    
    .security-info {
        background: #e8f5e8;
        border: 1px solid #27ae60;
        border-radius: 6px;
        padding: 15px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .security-info i {
        color: #27ae60;
        font-size: 18px;
    }
    
    .security-info-text {
        flex: 1;
        font-size: 14px;
        color: #2c3e50;
    }
    
    .payment-actions {
        display: flex;
        gap: 15px;
        justify-content: space-between;
        margin-top: 30px;
    }
    
    .btn {
        padding: 12px 30px;
        border-radius: 6px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.3s;
        border: none;
        font-size: 16px;
    }
    
    .btn-primary {
        background: #27ae60;
        color: white;
    }
    
    .btn-primary:hover {
        background: #219653;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
    }
    
    .btn-secondary {
        background: #95a5a6;
        color: white;
    }
    
    .btn-secondary:hover {
        background: #7f8c8d;
    }
    
    .btn-outline {
        background: transparent;
        color: #3498db;
        border: 2px solid #3498db;
    }
    
    .btn-outline:hover {
        background: #3498db;
        color: white;
    }
    
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }
    
    .loading-content {
        background: white;
        padding: 30px;
        border-radius: 8px;
        text-align: center;
        box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    }
    
    .loading-spinner {
        width: 40px;
        height: 40px;
        border: 4px solid #f3f3f3;
        border-top: 4px solid #3498db;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 15px;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    @media (max-width: 768px) {
        .payment-container {
            padding: 10px;
        }
        
        .payment-body {
            padding: 20px;
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
    }
";

try {
    $pdo = connect();
    
    if (!$pdo) {
        throw new Exception("Erreur de connexion à la base de données");
    }
    
    // Récupérer les détails de la solution et vérifier les permissions
    $stmt = $pdo->prepare("
        SELECT s.*, p.title as problem_title, p.description as problem_description, 
               p.user_id as problem_owner_id, pr.amount, pr.currency,
               u.username as solver_username, u.name as solver_name
        FROM solutions s
        JOIN problems p ON s.problem_id = p.problem_id
        LEFT JOIN prices pr ON s.price_id = pr.price_id
        JOIN users u ON s.user_id = u.id
        WHERE s.id = ? AND p.user_id = ? AND s.status = 'accepted'
    ");
    
    $stmt->execute([$solution_id, $user_id]);
    $solution = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$solution) {
        $_SESSION['error_message'] = "Solution non trouvée ou non autorisée.";
        header('Location: user_feedback.php');
        exit;
    }
    
    // Vérifier si le paiement a déjà été effectué
    $stmt = $pdo->prepare("SELECT id FROM payments WHERE solution_id = ? AND payer_id = ?");
    $stmt->execute([$solution_id, $user_id]);
    $existing_payment = $stmt->fetch();
    
    if ($existing_payment) {
        $_SESSION['error_message'] = "Cette solution a déjà été payée.";
        header('Location: user_feedback.php');
        exit;
    }
    
} catch (Exception $e) {
    error_log("Erreur lors de la récupération de la solution: " . $e->getMessage());
    $_SESSION['error_message'] = "Une erreur est survenue.";
    header('Location: user_feedback.php');
    exit;
}

// Traitement du paiement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    $payment_method = $_POST['payment_method'] ?? '';
    $amount = $solution['amount'] ?? 0;
    
    if (empty($payment_method)) {
        $error_message = "Veuillez sélectionner une méthode de paiement.";
    } elseif ($amount <= 0) {
        $error_message = "Montant invalide.";
    } else {
        try {
            // Simuler le traitement du paiement
            $transaction_id = 'TXN_' . time() . '_' . rand(1000, 9999);
            
            // Insérer le paiement dans la base de données
            $stmt = $pdo->prepare("
                INSERT INTO payments (
                    solution_id, payer_id, amount, currency, payment_method, 
                    transaction_id, status, payment_date
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, 'completed', CURRENT_TIMESTAMP
                )
            ");
            
            $result = $stmt->execute([
                $solution_id,
                $user_id,
                $amount,
                $solution['currency'] ?? 'EUR',
                $payment_method,
                $transaction_id
            ]);
            
            if ($result) {
                $payment_id = $pdo->lastInsertId();
                
                // Mettre à jour le statut de la solution
                $stmt = $pdo->prepare("UPDATE solutions SET status = 'paid' WHERE id = ?");
                $stmt->execute([$solution_id]);
                
                // Rediriger vers la page de succès
                $_SESSION['payment_success'] = true;
                $_SESSION['payment_id'] = $payment_id;
                $_SESSION['transaction_id'] = $transaction_id;
                
                header('Location: payment_success.php?payment_id=' . $payment_id);
                exit;
            } else {
                throw new Exception("Erreur lors de l'enregistrement du paiement");
            }
            
        } catch (Exception $e) {
            error_log("Erreur lors du traitement du paiement: " . $e->getMessage());
            $error_message = "Une erreur est survenue lors du traitement du paiement.";
        }
    }
}

// Inclure l'en-tête
include 'header.php';
?>

<div class="payment-container">
    <div class="payment-card">
        <div class="payment-header">
            <h1><i class="fas fa-credit-card"></i> Paiement Sécurisé</h1>
            <p>Finalisation de votre achat de solution</p>
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
                    <i class="fas fa-code"></i> <?php echo htmlspecialchars($solution['problem_title']); ?>
                </div>
                
                <div class="solution-meta">
                    <span>
                        <i class="fas fa-user"></i>
                        Développeur: <?php echo htmlspecialchars($solution['solver_name']); ?>
                    </span>
                    <span>
                        <i class="fas fa-calendar"></i>
                        Soumis le: <?php echo date('d/m/Y', strtotime($solution['created_at'])); ?>
                    </span>
                    <span>
                        <i class="fas fa-check-circle"></i>
                        Statut: Solution acceptée
                    </span>
                </div>
                
                <div class="solution-preview">
                    <strong>Aperçu de la solution:</strong><br>
                    <?php echo htmlspecialchars(substr($solution['solution_code'], 0, 200)) . (strlen($solution['solution_code']) > 200 ? '...' : ''); ?>
                </div>
            </div>
            
            <!-- Détail des prix -->
            <div class="price-breakdown">
                <h3><i class="fas fa-calculator"></i> Détail du paiement</h3>
                
                <div class="price-row">
                    <span>Prix de la solution</span>
                    <span><?php echo number_format($solution['amount'], 2); ?> €</span>
                </div>
                
                <div class="price-row">
                    <span>Frais de traitement</span>
                    <span>0,00 €</span>
                </div>
                
                <div class="price-row">
                    <span>TVA (0%)</span>
                    <span>0,00 €</span>
                </div>
                
                <div class="price-row">
                    <span><strong>Total à payer</strong></span>
                    <span><strong><?php echo number_format($solution['amount'], 2); ?> €</strong></span>
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
                            <div class="payment-method-desc">Visa, Mastercard, American Express</div>
                        </div>
                    </div>
                    
                    <div class="card-form" id="card-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="card-number">Numéro de carte</label>
                                <input type="text" id="card-number" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="card-name">Nom sur la carte</label>
                                <input type="text" id="card-name" name="card_name" placeholder="Jean Dupont">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="card-expiry">Date d'expiration</label>
                                <input type="text" id="card-expiry" name="card_expiry" placeholder="MM/AA" maxlength="5">
                            </div>
                            <div class="form-group">
                                <label for="card-cvv">CVV</label>
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
                            <div class="payment-method-desc">Paiement sécurisé avec votre compte PayPal</div>
                        </div>
                    </div>
                    
                    <div class="payment-method" onclick="selectPaymentMethod('crypto')">
                        <input type="radio" name="payment_method" value="crypto" id="payment-crypto">
                        <div class="payment-method-icon crypto">
                            <i class="fab fa-bitcoin"></i>
                        </div>
                        <div class="payment-method-info">
                            <div class="payment-method-name">Cryptomonnaie</div>
                            <div class="payment-method-desc">Bitcoin, Ethereum, autres crypto</div>
                        </div>
                    </div>
                </div>
                
                <!-- Informations de sécurité -->
                <div class="security-info">
                    <i class="fas fa-shield-alt"></i>
                    <div class="security-info-text">
                        <strong>Paiement 100% sécurisé</strong><br>
                        Vos informations sont protégées par un cryptage SSL 256 bits.
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="payment-actions">
                    <a href="user_feedback.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                    
                    <button type="submit" class="btn btn-primary" id="pay-button">
                        <i class="fas fa-lock"></i> Payer <?php echo number_format($solution['amount'], 2); ?> €
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
    </div>
</div>

<?php
$additional_scripts = "
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
        }
    }
    
    // Formatage automatique des champs de carte
    document.addEventListener('DOMContentLoaded', function() {
        const cardNumber = document.getElementById('card-number');
        const cardExpiry = document.getElementById('card-expiry');
        const cardCvv = document.getElementById('card-cvv');
        
        // Formatage du numéro de carte
        cardNumber.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formattedValue;
        });
        
        // Formatage de la date d'expiration
        cardExpiry.addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            e.target.value = value;
        });
        
        // Validation CVV
        cardCvv.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/[^0-9]/g, '');
        });
    });
    
    // Validation du formulaire
    document.getElementById('payment-form').addEventListener('submit', function(e) {
        const paymentMethod = document.querySelector('input[name=\"payment_method\"]:checked');
        
        if (!paymentMethod) {
            e.preventDefault();
            alert('Veuillez sélectionner une méthode de paiement.');
            return false;
        }
        
        if (paymentMethod.value === 'card') {
            const cardNumber = document.getElementById('card-number').value.replace(/\s/g, '');
            const cardName = document.getElementById('card-name').value.trim();
            const cardExpiry = document.getElementById('card-expiry').value;
            const cardCvv = document.getElementById('card-cvv').value;
            
            if (cardNumber.length < 13 || cardNumber.length > 19) {
                e.preventDefault();
                alert('Numéro de carte invalide.');
                return false;
            }
            
            if (cardName.length < 2) {
                e.preventDefault();
                alert('Nom sur la carte requis.');
                return false;
            }
            
            if (!/^\d{2}\/\d{2}$/.test(cardExpiry)) {
                e.preventDefault();
                alert('Date d\\'expiration invalide (MM/AA).');
                return false;
            }
            
            if (cardCvv.length < 3 || cardCvv.length > 4) {
                e.preventDefault();
                alert('CVV invalide.');
                return false;
            }
        }
        
        // Afficher l'overlay de chargement
        document.getElementById('loading-overlay').style.display = 'flex';
        document.getElementById('pay-button').disabled = true;
        
        // Simuler un délai de traitement
        setTimeout(() => {
            // Le formulaire sera soumis normalement
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
    
    // Détection du type de carte
    function getCardType(number) {
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
    
    // Animation des éléments
    document.addEventListener('DOMContentLoaded', function() {
        const elements = document.querySelectorAll('.payment-method, .price-breakdown, .solution-summary');
        elements.forEach((el, index) => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });
    
    // Gestion des erreurs de réseau
    window.addEventListener('online', function() {
        document.getElementById('loading-overlay').style.display = 'none';
        document.getElementById('pay-button').disabled = false;
    });
    
    window.addEventListener('offline', function() {
        alert('Connexion internet perdue. Veuillez vérifier votre connexion.');
    });
";

include 'footer.php';
?>
