<?php
// Démarrer la session si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure les fichiers nécessaires
require_once 'verification.php';
require_once 'db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    $_SESSION['error_message'] = 'Vous devez être connecté pour accéder à cette page.';
    header('Location: exlogin.php');
    exit;
}

// Récupérer l'ID de la solution depuis l'URL
$solution_id = isset($_GET['solution_id']) ? (int)$_GET['solution_id'] : 0;
$user_id = $_SESSION['user_id'] ?? null;

// Mode debug
$debug_mode = isset($_GET['debug']) && $_GET['debug'] == '1';
$debug_info = [];

// Variables pour les données
$solution = null;
$problem = null;
$solver = null;
$price_info = null;
$error_message = '';
$success_message = '';

// Traitement du paiement (simulation)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    try {
        $pdo = connect();
        
        if (!$pdo) {
            throw new Exception("Impossible de se connecter à la base de données");
        }
        
        // Vérifier que la solution existe et que l'utilisateur est autorisé
        $stmt = $pdo->prepare("
            SELECT s.*, p.title as problem_title, p.user_id as problem_owner_id,
                   u.username as solver_username, u.name as solver_name,
                   pr.amount as price, pr.currency
            FROM solutions s
            INNER JOIN problems p ON s.problem_id = p.problem_id
            INNER JOIN users u ON s.user_id = u.id
            LEFT JOIN prices pr ON s.price_id = pr.price_id
            WHERE s.id = ? AND p.user_id = ?
        ");
        
        $stmt->execute([$solution_id, $user_id]);
        $payment_solution = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$payment_solution) {
            throw new Exception("Solution non trouvée ou vous n'êtes pas autorisé à effectuer ce paiement.");
        }
        
        if (!in_array($payment_solution['status'], ['approved', 'accepted', 'approve', 'accept'])) {
            throw new Exception("Cette solution n'a pas été acceptée et ne peut pas être payée.");
        }
        
        // Simuler le traitement du paiement
        $payment_method = $_POST['payment_method'] ?? '';
        $amount = $payment_solution['price'] ?? 0;
        
        if ($amount <= 0) {
            throw new Exception("Montant invalide pour le paiement.");
        }
        
        if (empty($payment_method)) {
            throw new Exception("Veuillez sélectionner une méthode de paiement.");
        }
        
        // Créer un enregistrement de paiement
        $payment_reference = 'PAY_' . date('Ymd') . '_' . $solution_id . '_' . rand(1000, 9999);
        
        $stmt = $pdo->prepare("
            INSERT INTO payments (
                solution_id, payer_id, payee_id, amount, currency, 
                payment_method, payment_reference, status, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, 'completed', GETDATE()
            )
        ");
        
        $result = $stmt->execute([
            $solution_id,
            $user_id,
            $payment_solution['user_id'],
            $amount,
            $payment_solution['currency'] ?? 'EUR',
            $payment_method,
            $payment_reference
        ]);
        
        if ($result) {
            // Mettre à jour le statut de la solution
            $stmt = $pdo->prepare("
                UPDATE solutions 
                SET status = 'paid', payment_date = GETDATE()
                WHERE id = ?
            ");
            $stmt->execute([$solution_id]);
            
            // Mettre à jour le solde du développeur (simulation)
            $stmt = $pdo->prepare("
                UPDATE users 
                SET balance = COALESCE(balance, 0) + ?
                WHERE id = ?
            ");
            $stmt->execute([$amount, $payment_solution['user_id']]);
            
            $_SESSION['success_message'] = "Paiement effectué avec succès ! Référence: $payment_reference";
            header("Location: user_feedback.php?payment_success=1");
            exit;
        } else {
            throw new Exception("Erreur lors de l'enregistrement du paiement.");
        }
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        $debug_info[] = "❌ Erreur de paiement: " . $e->getMessage();
        error_log("Erreur de paiement dans payment.php: " . $e->getMessage());
    }
}

// Récupération des données de la solution
if ($solution_id <= 0) {
    $error_message = "ID de solution invalide.";
    $debug_info[] = "❌ ID de solution invalide: $solution_id";
} else {
    try {
        $pdo = connect();
        
        if (!$pdo) {
            throw new Exception("Impossible de se connecter à la base de données");
        }
        
        $debug_info[] = "✅ Connexion à la base de données réussie";
        $debug_info[] = "🔍 Recherche de la solution ID: $solution_id";
        $debug_info[] = "👤 User ID: $user_id";
        
        // Récupérer les détails complets de la solution
        $stmt = $pdo->prepare("
            SELECT s.*, 
                   p.title as problem_title, p.description as problem_description,
                   p.user_id as problem_owner_id, p.points,
                   u.username as solver_username, u.name as solver_name, u.email as solver_email,
                   pr.amount as price, pr.currency,
                   owner.username as owner_username, owner.name as owner_name
            FROM solutions s
            INNER JOIN problems p ON s.problem_id = p.problem_id
            INNER JOIN users u ON s.user_id = u.id
            INNER JOIN users owner ON p.user_id = owner.id
            LEFT JOIN prices pr ON s.price_id = pr.price_id
            WHERE s.id = ?
        ");
        
        $stmt->execute([$solution_id]);
        $solution = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$solution) {
            $error_message = "Solution non trouvée.";
            $debug_info[] = "❌ Solution non trouvée avec l'ID: $solution_id";
        } else {
            $debug_info[] = "✅ Solution trouvée: " . $solution['problem_title'];
            $debug_info[] = "💰 Prix: " . ($solution['price'] ?? 0) . " " . ($solution['currency'] ?? 'EUR');
            $debug_info[] = "📊 Statut: " . $solution['status'];
            $debug_info[] = "👤 Propriétaire du problème: " . $solution['owner_username'];
            $debug_info[] = "🔧 Développeur: " . $solution['solver_username'];
            
            // Vérifier que l'utilisateur connecté est le propriétaire du problème
            if ($solution['problem_owner_id'] != $user_id) {
                $error_message = "Vous n'êtes pas autorisé à payer cette solution.";
                $debug_info[] = "❌ Utilisateur non autorisé - Owner ID: " . $solution['problem_owner_id'] . " vs User ID: $user_id";
            } else {
                $debug_info[] = "✅ Utilisateur autorisé pour le paiement";
                
                // Vérifier le statut de la solution
                if (!in_array($solution['status'], ['approved', 'accepted', 'approve', 'accept'])) {
                    $error_message = "Cette solution n'a pas été acceptée et ne peut pas être payée (statut: " . $solution['status'] . ").";
                    $debug_info[] = "❌ Statut invalide pour paiement: " . $solution['status'];
                } else {
                    $debug_info[] = "✅ Statut valide pour paiement";
                    
                    // Vérifier si le paiement n'a pas déjà été effectué
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE solution_id = ? AND status = 'completed'");
                    $stmt->execute([$solution_id]);
                    $payment_exists = $stmt->fetchColumn();
                    
                    if ($payment_exists > 0) {
                        $error_message = "Cette solution a déjà été payée.";
                        $debug_info[] = "❌ Paiement déjà effectué";
                    } else {
                        $debug_info[] = "✅ Aucun paiement précédent trouvé";
                    }
                }
            }
        }
        
    } catch (Exception $e) {
        $error_message = "Erreur lors de la récupération des données: " . $e->getMessage();
        $debug_info[] = "❌ Exception: " . $e->getMessage();
        error_log("Erreur dans payment.php: " . $e->getMessage());
    }
}

// Récupérer les messages de session
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    if (empty($error_message)) {
        $error_message = $_SESSION['error_message'];
    }
    unset($_SESSION['error_message']);
}

// Configuration de la page
$page_title = "Paiement de Solution";

// CSS spécifique à cette page
$additional_css = "
    .payment-container {
        max-width: 800px;
        margin: 0 auto;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .payment-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        text-align: center;
    }

    .payment-header h1 {
        margin: 0 0 10px 0;
        font-size: 2em;
    }

    .payment-header p {
        margin: 0;
        opacity: 0.9;
        font-size: 1.1em;
    }

    .payment-content {
        padding: 30px;
    }

    .solution-summary {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 30px;
        border-left: 4px solid #007bff;
    }

    .solution-summary h3 {
        margin-top: 0;
        color: #2c3e50;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 10px 0;
        padding: 8px 0;
        border-bottom: 1px solid #e9ecef;
    }

    .summary-row:last-child {
        border-bottom: none;
        font-weight: bold;
        font-size: 1.2em;
        color: #28a745;
    }

    .payment-methods {
        margin-bottom: 30px;
    }

    .payment-methods h3 {
        color: #2c3e50;
        margin-bottom: 20px;
    }

    .payment-method {
        display: flex;
        align-items: center;
        padding: 15px;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        margin-bottom: 15px;
        cursor: pointer;
        transition: all 0.3s;
    }

    .payment-method:hover {
        border-color: #007bff;
        background-color: #f8f9fa;
    }

    .payment-method.selected {
        border-color: #007bff;
        background-color: #e7f3ff;
    }

    .payment-method input[type='radio'] {
        margin-right: 15px;
        transform: scale(1.2);
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
        color: #6c757d;
        font-size: 0.9em;
    }

    .payment-method-icon {
        font-size: 2em;
        margin-right: 15px;
        color: #007bff;
    }

    .payment-actions {
        display: flex;
        gap: 15px;
        justify-content: space-between;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #e9ecef;
    }

    .btn-cancel {
        background-color: #6c757d;
        color: white;
        border: none;
    }

    .btn-cancel:hover {
        background-color: #5a6268;
    }

    .btn-pay {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        border: none;
        font-weight: bold;
        font-size: 1.1em;
        padding: 12px 30px;
    }

    .btn-pay:hover {
        background: linear-gradient(135deg, #218838 0%, #1ea085 100%);
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    }

    .security-info {
        background: #e8f5e8;
        border: 1px solid #c3e6c3;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
    }

    .security-info h4 {
        color: #155724;
        margin-top: 0;
        margin-bottom: 10px;
    }

    .security-info ul {
        margin: 0;
        padding-left: 20px;
        color: #155724;
    }

    .debug-info {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
        font-family: monospace;
        font-size: 12px;
        max-height: 400px;
        overflow-y: auto;
    }

    .debug-info h4 {
        margin-top: 0;
        color: #495057;
        font-family: sans-serif;
    }

    .debug-info div {
        margin: 5px 0;
        padding: 2px 0;
    }

    .error-state {
        text-align: center;
        padding: 40px 20px;
        color: #dc3545;
    }

    .error-state i {
        font-size: 3em;
        margin-bottom: 20px;
    }

    .error-state h3 {
        color: #dc3545;
        margin-bottom: 15px;
    }

    @media (max-width: 768px) {
        .payment-container {
            margin: 10px;
        }
        
        .payment-header {
            padding: 20px;
        }
        
        .payment-content {
            padding: 20px;
        }
        
        .payment-actions {
            flex-direction: column;
        }
        
        .payment-actions .btn {
            width: 100%;
            margin-bottom: 10px;
        }
        
        .summary-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
        }
    }
";

// Include header
include 'header.php';
?>

<div class="payment-container">
    <div class="payment-header">
        <h1><i class="fas fa-credit-card"></i> Paiement de Solution</h1>
        <p>Finalisez le paiement pour la solution acceptée</p>
    </div>
    
    <div class="payment-content">
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($debug_mode && !empty($debug_info)): ?>
            <div class="debug-info">
                <h4>🔍 Informations de débogage</h4>
                <?php foreach ($debug_info as $info): ?>
                    <div><?php echo htmlspecialchars($info); ?></div>
                <?php endforeach; ?>
                <div style="margin-top: 10px;">
                    <a href="?solution_id=<?php echo $solution_id; ?>" class="btn btn-outline" style="font-size: 12px;">Désactiver le débogage</a>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if (!$debug_mode): ?>
            <div style="margin-bottom: 20px;">
                <a href="?solution_id=<?php echo $solution_id; ?>&debug=1" class="btn btn-outline" style="font-size: 12px;">
                    <i class="fas fa-bug"></i> Mode débogage
                </a>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message) && !$solution): ?>
            <div class="error-state">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Impossible de traiter le paiement</h3>
                <p><?php echo htmlspecialchars($error_message); ?></p>
                <div style="margin-top: 20px;">
                    <a href="user_feedback.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Retour aux notifications
                    </a>
                </div>
            </div>
        <?php elseif ($solution): ?>
            <!-- Résumé de la solution -->
            <div class="solution-summary">
                <h3><i class="fas fa-file-code"></i> Détails de la solution</h3>
                
                <div class="summary-row">
                    <span><strong>Problème :</strong></span>
                    <span><?php echo htmlspecialchars($solution['problem_title']); ?></span>
                </div>
                
                <div class="summary-row">
                    <span><strong>Développeur :</strong></span>
                    <span><?php echo htmlspecialchars($solution['solver_name'] ?? $solution['solver_username']); ?></span>
                </div>
                
                <div class="summary-row">
                    <span><strong>Date de soumission :</strong></span>
                    <span><?php echo date('d/m/Y à H:i', strtotime($solution['created_at'])); ?></span>
                </div>
                
                <div class="summary-row">
                    <span><strong>Statut :</strong></span>
                    <span style="color: #28a745; font-weight: bold;">
                        <i class="fas fa-check-circle"></i> Solution acceptée
                    </span>
                </div>
                
                <div class="summary-row">
                    <span><strong>Montant à payer :</strong></span>
                    <span style="font-size: 1.3em; color: #28a745;">
                        <?php echo number_format($solution['price'] ?? 0, 2); ?> <?php echo htmlspecialchars($solution['currency'] ?? 'EUR'); ?>
                    </span>
                </div>
            </div>
            
            <!-- Informations de sécurité -->
            <div class="security-info">
                <h4><i class="fas fa-shield-alt"></i> Paiement sécurisé</h4>
                <ul>
                    <li>Toutes les transactions sont sécurisées et cryptées</li>
                    <li>Vos informations de paiement ne sont pas stockées sur nos serveurs</li>
                    <li>Vous recevrez une confirmation par email après le paiement</li>
                    <li>Le développeur sera notifié automatiquement du paiement</li>
                </ul>
            </div>
            
            <?php if (empty($error_message) || strpos($error_message, 'déjà été payée') === false): ?>
                <!-- Formulaire de paiement -->
                <form method="POST" action="" id="payment-form">
                    <input type="hidden" name="solution_id" value="<?php echo $solution_id; ?>">
                    
                    <div class="payment-methods">
                        <h3><i class="fas fa-credit-card"></i> Choisissez votre méthode de paiement</h3>
                        
                        <div class="payment-method" onclick="selectPaymentMethod('credit_card')">
                            <input type="radio" name="payment_method" value="credit_card" id="credit_card" required>
                            <div class="payment-method-icon">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <div class="payment-method-info">
                                <div class="payment-method-name">Carte de crédit/débit</div>
                                <div class="payment-method-desc">Visa, MasterCard, American Express</div>
                            </div>
                        </div>
                        
                        <div class="payment-method" onclick="selectPaymentMethod('paypal')">
                            <input type="radio" name="payment_method" value="paypal" id="paypal" required>
                            <div class="payment-method-icon">
                                <i class="fab fa-paypal"></i>
                            </div>
                            <div class="payment-method-info">
                                <div class="payment-method-name">PayPal</div>
                                <div class="payment-method-desc">Paiement rapide et sécurisé avec PayPal</div>
                            </div>
                        </div>
                        
                        <div class="payment-method" onclick="selectPaymentMethod('bank_transfer')">
                            <input type="radio" name="payment_method" value="bank_transfer" id="bank_transfer" required>
                            <div class="payment-method-icon">
                                <i class="fas fa-university"></i>
                            </div>
                            <div class="payment-method-info">
                                <div class="payment-method-name">Virement bancaire</div>
                                <div class="payment-method-desc">Transfert direct depuis votre compte bancaire</div>
                            </div>
                        </div>
                        
                        <div class="payment-method" onclick="selectPaymentMethod('crypto')">
                            <input type="radio" name="payment_method" value="crypto" id="crypto" required>
                            <div class="payment-method-icon">
                                <i class="fab fa-bitcoin"></i>
                            </div>
                            <div class="payment-method-info">
                                <div class="payment-method-name">Cryptomonnaie</div>
                                <div class="payment-method-desc">Bitcoin, Ethereum, Litecoin</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="payment-actions">
                        <a href="user_feedback.php" class="btn btn-cancel">
                            <i class="fas fa-times"></i> Annuler
                        </a>
                        
                        <button type="submit" name="process_payment" class="btn btn-pay" id="pay-button" disabled>
                            <i class="fas fa-lock"></i> Payer <?php echo number_format($solution['price'] ?? 0, 2); ?> <?php echo htmlspecialchars($solution['currency'] ?? 'EUR'); ?>
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php
// Scripts additionnels
$additional_scripts = "
    // Gestion de la sélection des méthodes de paiement
    function selectPaymentMethod(method) {
        // Désélectionner toutes les méthodes
        document.querySelectorAll('.payment-method').forEach(el => {
            el.classList.remove('selected');
        });
        
        // Sélectionner la méthode choisie
        const selectedMethod = document.getElementById(method);
        if (selectedMethod) {
            selectedMethod.checked = true;
            selectedMethod.closest('.payment-method').classList.add('selected');
            
            // Activer le bouton de paiement
            const payButton = document.getElementById('pay-button');
            if (payButton) {
                payButton.disabled = false;
                payButton.style.opacity = '1';
                payButton.style.cursor = 'pointer';
            }
        }
    }
    
    // Gestion du formulaire de paiement
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('payment-form');
        const payButton = document.getElementById('pay-button');
        
        if (form && payButton) {
            // Style initial du bouton désactivé
            payButton.style.opacity = '0.6';
            payButton.style.cursor = 'not-allowed';
            
            form.addEventListener('submit', function(e) {
                const selectedMethod = form.querySelector('input[name=\"payment_method\"]:checked');
                
                if (!selectedMethod) {
                    e.preventDefault();
                    alert('⚠️ Veuillez sélectionner une méthode de paiement.');
                    return false;
                }
                
                // Confirmation avant paiement
                const amount = '" . number_format($solution['price'] ?? 0, 2) . "';
                const currency = '" . ($solution['currency'] ?? 'EUR') . "';
                const methodName = getPaymentMethodName(selectedMethod.value);
                
                const confirmMessage = '💳 CONFIRMER LE PAIEMENT\\n\\n' +
                    '💰 Montant: ' + amount + ' ' + currency + '\\n' +
                    '💳 Méthode: ' + methodName + '\\n' +
                    '📝 Solution: " . addslashes($solution['problem_title'] ?? '') . "\\n' +
                    '👤 Développeur: " . addslashes($solution['solver_name'] ?? $solution['solver_username'] ?? '') . "\\n\\n' +
                    '⚠️ Cette action est irréversible.\\n' +
                    'Voulez-vous continuer ?';
                
                if (!confirm(confirmMessage)) {
                    e.preventDefault();
                    return false;
                }
                
                // Animation de traitement
                payButton.disabled = true;
                payButton.innerHTML = '<i class=\"fas fa-spinner fa-spin\"></i> Traitement en cours...';
                payButton.style.backgroundColor = '#ffc107';
                payButton.style.color = '#212529';
                
                // Désactiver tous les autres éléments
                const allInputs = form.querySelectorAll('input, button');
                allInputs.forEach(input => {
                    if (input !== payButton) {
                        input.disabled = true;
                        input.style.opacity = '0.5';
                    }
                });
                
                return true;
            });
        }
        
        // Gestion des clics sur les méthodes de paiement
        document.querySelectorAll('.payment-method').forEach(method => {
            method.addEventListener('click', function() {
                const radio = this.querySelector('input[type=\"radio\"]');
                if (radio) {
                    selectPaymentMethod(radio.value);
                }
            });
        });
    });
    
    // Fonction utilitaire pour obtenir le nom de la méthode de paiement
    function getPaymentMethodName(value) {
        const methods = {
            'credit_card': 'Carte de crédit/débit',
            'paypal': 'PayPal',
            'bank_transfer': 'Virement bancaire',
            'crypto': 'Cryptomonnaie'
        };
        return methods[value] || value;
    }
    
    // Animation des éléments
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.querySelector('.payment-container');
        if (container) {
            container.style.opacity = '0';
            container.style.transform = 'translateY(30px)';
            setTimeout(() => {
                container.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, 100);
        }
        
        // Animation des méthodes de paiement
        const methods = document.querySelectorAll('.payment-method');
        methods.forEach((method, index) => {
            method.style.opacity = '0';
            method.style.transform = 'translateX(-30px)';
            setTimeout(() => {
                method.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                method.style.opacity = '1';
                method.style.transform = 'translateX(0)';
            }, 200 + (index * 100));
        });
    });
    
    // Gestion des erreurs de réseau
    window.addEventListener('online', () => {
        showNotification('Connexion rétablie', 'success');
    });
    
    window.addEventListener('offline', () => {
        showNotification('Connexion perdue - Le paiement peut échouer', 'warning');
    });
    
    // Fonction de notification
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            z-index: 9999;
            max-width: 350px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateX(100%);
            transition: transform 0.3s ease;
        `;
        
        switch(type) {
            case 'success':
                notification.style.backgroundColor = '#28a745';
                notification.innerHTML = '<i class=\"fas fa-check-circle\"></i> ' + message;
                break;
            case 'error':
                notification.style.backgroundColor = '#dc3545';
                notification.innerHTML = '<i class=\"fas fa-exclamation-circle\"></i> ' + message;
                break;
            case 'warning':
                notification.style.backgroundColor = '#ffc107';
                notification.style.color = '#212529';
                notification.innerHTML = '<i class=\"fas fa-exclamation-triangle\"></i> ' + message;
                break;
            default:
                notification.style.backgroundColor = '#17a2b8';
                notification.innerHTML = '<i class=\"fas fa-info-circle\"></i> ' + message;
        }
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 5000);
    }
    
    // Prévention de la fermeture accidentelle pendant le paiement
    let paymentInProgress = false;
    
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('payment-form');
        if (form) {
            form.addEventListener('submit', function() {
                paymentInProgress = true;
            });
        }
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
        if (e.key === 'Escape') {
            if (!paymentInProgress) {
                if (confirm('Voulez-vous annuler et retourner aux notifications ?')) {
                    window.location.href = 'user_feedback.php';
                }
            }
        }
        
        // Touches numériques pour sélectionner les méthodes de paiement
        if (e.key >= '1' && e.key <= '4' && !paymentInProgress) {
            e.preventDefault();
            const methods = ['credit_card', 'paypal', 'bank_transfer', 'crypto'];
            const selectedMethod = methods[parseInt(e.key) - 1];
            if (selectedMethod) {
                selectPaymentMethod(selectedMethod);
            }
        }
        
        // Entrée pour confirmer le paiement
        if (e.key === 'Enter' && e.ctrlKey && !paymentInProgress) {
            const payButton = document.getElementById('pay-button');
            if (payButton && !payButton.disabled) {
                payButton.click();
            }
        }
    });
    
    // Validation en temps réel
    document.addEventListener('DOMContentLoaded', function() {
        const paymentMethods = document.querySelectorAll('input[name=\"payment_method\"]');
        const payButton = document.getElementById('pay-button');
        
        paymentMethods.forEach(method => {
            method.addEventListener('change', function() {
                if (this.checked && payButton) {
                    payButton.disabled = false;
                    payButton.style.opacity = '1';
                    payButton.style.cursor = 'pointer';
                    
                    // Animation du bouton
                    payButton.style.transform = 'scale(1.05)';
                    setTimeout(() => {
                        payButton.style.transform = 'scale(1)';
                    }, 200);
                }
            });
        });
    });
    
    // Simulation de progression du paiement
    function simulatePaymentProgress() {
        if (!paymentInProgress) return;
        
        const messages = [
            'Vérification des informations...',
            'Connexion au processeur de paiement...',
            'Traitement de la transaction...',
            'Confirmation en cours...',
            'Finalisation du paiement...'
        ];
        
        let currentStep = 0;
        const payButton = document.getElementById('pay-button');
        
        const interval = setInterval(() => {
            if (currentStep < messages.length && payButton) {
                payButton.innerHTML = '<i class=\"fas fa-spinner fa-spin\"></i> ' + messages[currentStep];
                currentStep++;
            } else {
                clearInterval(interval);
            }
        }, 1500);
    }
    
    // Démarrer la simulation si le formulaire est soumis
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('payment-form');
        if (form) {
            form.addEventListener('submit', function() {
                setTimeout(simulatePaymentProgress, 500);
            });
        }
    });
    
    // Fonction de débogage (disponible dans la console)
    window.debugPayment = function() {
        console.log('🔧 Informations de débogage du paiement:');
        console.log('💳 Solution ID:', '" . $solution_id . "');
        console.log('👤 User ID:', '" . $user_id . "');
        console.log('💰 Montant:', '" . ($solution['price'] ?? 0) . "');
        console.log('💱 Devise:', '" . ($solution['currency'] ?? 'EUR') . "');
        console.log('📊 Statut solution:', '" . ($solution['status'] ?? 'N/A') . "');
        console.log('🔒 Paiement en cours:', paymentInProgress);
        
        const selectedMethod = document.querySelector('input[name=\"payment_method\"]:checked');
        console.log('💳 Méthode sélectionnée:', selectedMethod ? selectedMethod.value : 'Aucune');
        
        return {
            solutionId: '" . $solution_id . "',
            userId: '" . $user_id . "',
            amount: '" . ($solution['price'] ?? 0) . "',
            currency: '" . ($solution['currency'] ?? 'EUR') . "',
            status: '" . ($solution['status'] ?? 'N/A') . "',
            paymentInProgress: paymentInProgress,
            selectedMethod: selectedMethod ? selectedMethod.value : null
        };
    };
    
    // Logs de débogage
    console.log('💳 Page de paiement chargée');
    console.log('🔍 Solution ID:', '" . $solution_id . "');
    console.log('💰 Montant:', '" . ($solution['price'] ?? 0) . " " . ($solution['currency'] ?? 'EUR') . "');
    console.log('💡 Tapez debugPayment() pour plus d\'informations');
    
    // Raccourcis clavier - aide
    console.log('⌨️ Raccourcis disponibles:');
    console.log('  • 1-4: Sélectionner méthode de paiement');
    console.log('  • Ctrl+Entrée: Confirmer le paiement');
    console.log('  • Échap: Annuler et retourner');
    
    // Vérification de la connectivité au chargement
    if (!navigator.onLine) {
        showNotification('Vous êtes hors ligne. Le paiement ne fonctionnera pas.', 'error');
    }
    
    // Affichage d'informations utiles
    " . ($debug_mode ? "
    console.log('🧪 Mode debug activé');
    console.log('📋 Informations de debug:', " . json_encode($debug_info) . ");
    " : "") . "
    
    // Message de bienvenue
    setTimeout(() => {
        if (document.querySelector('.payment-container') && !document.querySelector('.error-state')) {
            showNotification('Sélectionnez une méthode de paiement pour continuer', 'info');
        }
    }, 2000);
";

// Include footer
include 'footer.php';
?>
