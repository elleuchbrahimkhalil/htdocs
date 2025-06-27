<?php
session_start();

// Test de diagnostic au début
error_log("DIAGNOSTIC: Accès à payment.php");
error_log("DIAGNOSTIC: GET params: " . print_r($_GET, true));
error_log("DIAGNOSTIC: SESSION: " . print_r($_SESSION, true));

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
    $_SESSION['error_message'] = "DIAGNOSTIC: Vous devez être connecté pour accéder à cette page.";
    header('Location: user_feedback.php?payment_error=not_logged_in');
    exit;
}

// Vérifier si l'ID de la solution est fourni
if (!isset($_GET['solution_id']) || !is_numeric($_GET['solution_id'])) {
    error_log("DIAGNOSTIC: solution_id manquant ou invalide - " . ($_GET['solution_id'] ?? 'NULL'));
    $_SESSION['error_message'] = "DIAGNOSTIC: Solution non spécifiée ou invalide.";
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
    
    // Test de diagnostic - Vérifier d'abord si la solution existe
    $debug_stmt = $pdo->prepare("SELECT id, status FROM solutions WHERE id = ?");
    $debug_stmt->execute([$solution_id]);
    $debug_solution = $debug_stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("DIAGNOSTIC: Solution existe: " . ($debug_solution ? 'OUI' : 'NON'));
    if ($debug_solution) {
        error_log("DIAGNOSTIC: Status de la solution: " . $debug_solution['status']);
    }
    
    // Récupérer les détails de la solution - REQUÊTE COMPLÈTE AVEC DIAGNOSTIC
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
    
    error_log("DIAGNOSTIC: Solution complète trouvée: " . ($solution ? 'OUI' : 'NON'));
    if ($solution) {
        error_log("DIAGNOSTIC: Status = " . ($solution['status'] ?? 'NULL'));
        error_log("DIAGNOSTIC: Problem owner = " . ($solution['problem_owner_id'] ?? 'NULL'));
        error_log("DIAGNOSTIC: Amount = " . ($solution['amount'] ?? 'NULL'));
        error_log("DIAGNOSTIC: Problem title = " . ($solution['problem_title'] ?? 'NULL'));
    } else {
        // Debug détaillé pour comprendre pourquoi la solution n'est pas trouvée
        error_log("DIAGNOSTIC: Recherche détaillée...");
        
        // Vérifier si la solution existe
        $debug_stmt = $pdo->prepare("SELECT * FROM solutions WHERE id = ?");
        $debug_stmt->execute([$solution_id]);
        $debug_solution = $debug_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($debug_solution) {
            error_log("DIAGNOSTIC: Solution existe - problem_id = " . $debug_solution['problem_id']);
            
            // Vérifier le problème
            $debug_stmt2 = $pdo->prepare("SELECT * FROM problems WHERE problem_id = ?");
            $debug_stmt2->execute([$debug_solution['problem_id']]);
            $debug_problem = $debug_stmt2->fetch(PDO::FETCH_ASSOC);
            
            if ($debug_problem) {
                error_log("DIAGNOSTIC: Problème existe - owner = " . $debug_problem['user_id'] . ", current user = $user_id");
                if ($debug_problem['user_id'] != $user_id) {
                    error_log("DIAGNOSTIC: PROBLÈME - L'utilisateur n'est pas le propriétaire du problème");
                }
            } else {
                error_log("DIAGNOSTIC: PROBLÈME - Le problème n'existe pas");
            }
        } else {
            error_log("DIAGNOSTIC: PROBLÈME - La solution n'existe pas du tout");
        }
    }
    
    if (!$solution) {
        error_log("DIAGNOSTIC: Redirection - solution non trouvée");
        $_SESSION['error_message'] = "DIAGNOSTIC: Solution non trouvée ou vous n'êtes pas autorisé à y accéder. (solution_id=$solution_id, user_id=$user_id)";
        header('Location: user_feedback.php?payment_error=solution_not_found&solution_id=' . $solution_id . '&user_id=' . $user_id);
        exit;
    }
    
    // Vérifier le statut de la solution
    $valid_statuses = ['accepted', 'approved', 'approve', 'accept'];
    if (!in_array($solution['status'], $valid_statuses)) {
        error_log("DIAGNOSTIC: Statut invalide - " . $solution['status']);
        $_SESSION['error_message'] = "DIAGNOSTIC: Cette solution n'est pas prête pour le paiement (statut: " . $solution['status'] . "). Statuts valides: " . implode(', ', $valid_statuses);
        header('Location: user_feedback.php?payment_error=invalid_status&status=' . urlencode($solution['status']));
        exit;
    }
    
    // Vérifier si le paiement a déjà été effectué
    $stmt = $pdo->prepare("SELECT id, status FROM payments WHERE solution_id = ? AND payer_id = ?");
    $stmt->execute([$solution_id, $user_id]);
    $existing_payment = $stmt->fetch();
    
    if ($existing_payment) {
        error_log("DIAGNOSTIC: Paiement déjà effectué - ID " . $existing_payment['id']);
        $_SESSION['error_message'] = "DIAGNOSTIC: Cette solution a déjà été payée (Paiement #" . $existing_payment['id'] . ").";
        header('Location: user_feedback.php?payment_error=already_paid&payment_id=' . $existing_payment['id']);
        exit;
    }
    
    error_log("DIAGNOSTIC: Toutes les vérifications passées - affichage de la page de paiement");
    
    // Si on arrive ici, tout est OK pour afficher la page de paiement
    
} catch (Exception $e) {
    error_log("DIAGNOSTIC: Exception dans payment.php: " . $e->getMessage());
    error_log("DIAGNOSTIC: Stack trace: " . $e->getTraceAsString());
    $_SESSION['error_message'] = "DIAGNOSTIC: Erreur lors du chargement de la page de paiement - " . $e->getMessage();
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
        $error_message = "DIAGNOSTIC: Veuillez sélectionner une méthode de paiement.";
    } elseif ($amount <= 0) {
        $error_message = "DIAGNOSTIC: Montant invalide ($amount).";
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
            $error_message = "DIAGNOSTIC: Erreur lors du traitement du paiement - " . $e->getMessage();
        }
    }
}

// Configuration de la page
$page_title = "Paiement de Solution";
$additional_css = "
    .diagnostic-info {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
        font-family: monospace;
        font-size: 12px;
    }
    
    .diagnostic-info h4 {
        margin-top: 0;
        color: #856404;
    }
    
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
    
    .btn-outline {
        background: transparent;
        color: #3498db;
        border: 2px solid #3498db;
    }
    
    .btn-outline:hover {
        background: #3498db;
        color: white;
    }
";

// Include header
include 'header.php';
?>

<div class="payment-container">
    <!-- Informations de diagnostic -->
    <?php if ($test_mode || $debug_mode): ?>
        <div class="diagnostic-info">
            <h4>🔍 DIAGNOSTIC - payment.php</h4>
            <div><strong>✅ SUCCÈS:</strong> Vous êtes arrivé sur la page de paiement !</div>
            <div><strong>Solution ID:</strong> <?php echo $solution_id; ?></div>
            <div><strong>User ID:</strong> <?php echo $user_id; ?></div>
            <div><strong>Solution Status:</strong> <?php echo htmlspecialchars($solution['status'] ?? 'N/A'); ?></div>
            <div><strong>Problem Owner:</strong> <?php echo htmlspecialchars($solution['problem_owner_id'] ?? 'N/A'); ?></div>
            <div><strong>Amount:</strong> <?php echo htmlspecialchars($solution['amount'] ?? 'N/A'); ?> <?php echo htmlspecialchars($solution['currency'] ?? 'EUR'); ?></div>
            <div><strong>Problem Title:</strong> <?php echo htmlspecialchars($solution['problem_title'] ?? 'N/A'); ?></div>
            <div><strong>Solver:</strong> <?php echo htmlspecialchars($solution['solver_name'] ?? 'N/A'); ?></div>
            <div><strong>Test Mode:</strong> <?php echo $test_mode ? 'ACTIVÉ' : 'DÉSACTIVÉ'; ?></div>
            <div><strong>Debug Mode:</strong> <?php echo $debug_mode ? 'ACTIVÉ' : 'DÉSACTIVÉ'; ?></div>
            <div style="margin-top: 10px;">
                <a href="user_feedback.php?payment_debug=success_reached_payment_page" class="btn btn-outline" style="font-size: 12px;">
                    ← Retour avec diagnostic de succès
                </a>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($debug_mode): ?>
        <div class="debug-info">
            <h4>🔧 Informations de débogage détaillées</h4>
            <div><strong>GET Parameters:</strong> <?php echo htmlspecialchars(print_r($_GET, true)); ?></div>
            <div><strong>SESSION Data:</strong> <?php echo htmlspecialchars(print_r($_SESSION, true)); ?></div>
            <div><strong>Solution Data:</strong> <?php echo htmlspecialchars(print_r($solution, true)); ?></div>
        </div>
    <?php endif; ?>

    <div class="payment-card">
        <div class="payment-header">
            <h1><i class="fas fa-credit-card"></i> Paiement Sécurisé</h1>
            <p>Finalisation de votre achat de solution</p>
            <?php if ($test_mode): ?>
                <p style="background: rgba(255,255,255,0.2); padding: 10px; border-radius: 5px; margin-top: 10px;">
                    <strong>MODE TEST:</strong> La redirection depuis user_feedback.php a fonctionné !
                </p>
            <?php endif; ?>
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
                        Développeur: <?php echo htmlspecialchars($solution['solver_name'] ?? $solution['solver_username']); ?>
                    </span>
                    <span>
                        <i class="fas fa-calendar"></i>
                        Soumis le: <?php echo date('d/m/Y', strtotime($solution['created_at'])); ?>
                    </span>
                    <span>
                        <i class="fas fa-check-circle"></i>
                        Statut: <?php echo ucfirst($solution['status']); ?>
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
            
            <!-- Test de paiement simplifié -->
            <?php if ($test_mode): ?>
                <div style="background: #e8f5e8; border: 1px solid #27ae60; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                    <h3 style="color: #27ae60; margin-top: 0;">
                        <i class="fas fa-flask"></i> Mode Test - Paiement Simplifié
                    </h3>
                    <p>Cliquez sur le bouton ci-dessous pour simuler un paiement réussi :</p>
                    
                    <form method="POST" action="" style="margin-top: 15px;">
                        <input type="hidden" name="process_payment" value="1">
                        <input type="hidden" name="payment_method" value="test_card">
                        
                        <button type="submit" class="btn btn-primary" style="font-size: 18px; padding: 15px 30px;">
                            <i class="fas fa-credit-card"></i> 
                            TESTER LE PAIEMENT (<?php echo number_format($solution['amount'], 2); ?> €)
                        </button>
                    </form>
                    
                    <p style="margin-top: 15px; font-size: 14px; color: #666;">
                        <i class="fas fa-info-circle"></i> 
                        Ce bouton va simuler un paiement réussi et vous rediriger vers la page de confirmation.
                    </p>
                </div>
            <?php endif; ?>
            
            <!-- Formulaire de paiement normal (masqué en mode test) -->
            <?php if (!$test_mode): ?>
                <form method="POST" id="payment-form">
                    <input type="hidden" name="process_payment" value="1">
                    
                    <!-- Méthodes de paiement simplifiées -->
                    <div style="margin-bottom: 30px;">
                        <h3><i class="fas fa-credit-card"></i> Méthode de paiement</h3>
                        
                        <div style="border: 2px solid #3498db; border-radius: 8px; padding: 15px; margin-bottom: 15px; background: #f8f9fa;">
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                <input type="radio" name="payment_method" value="card" checked>
                                <i class="fas fa-credit-card" style="color: #3498db; font-size: 20px;"></i>
                                <span style="font-weight: bold;">Carte bancaire (Simulation)</span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div style="display: flex; gap: 15px; justify-content: space-between; margin-top: 30px;">
                        <a href="user_feedback.php<?php echo $debug_mode ? '?debug=1' : ''; ?>" class="btn btn-outline">
                            <i class="fas fa-arrow-left"></i> Retour
                        </a>
                        
                        <button type="submit" class="btn btn-primary" id="pay-button">
                            <i class="fas fa-lock"></i> Payer <?php echo number_format($solution['amount'], 2); ?> €
                        </button>
                    </div>
                </form>
            <?php endif; ?>
            
            <!-- Actions de diagnostic -->
            <?php if ($test_mode || $debug_mode): ?>
                <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <h4><i class="fas fa-tools"></i> Actions de Diagnostic</h4>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px;">
                        <a href="user_feedback.php?payment_debug=test_completed" class="btn btn-outline" style="font-size: 14px;">
                            <i class="fas fa-check"></i> Test Terminé - Retour
                        </a>
                        
                        <a href="payment_success.php?payment_id=test&test=1" class="btn btn-outline" style="font-size: 14px;">
                            <i class="fas fa-forward"></i> Tester Page Succès
                        </a>
                        
                        <button onclick="window.location.reload()" class="btn btn-outline" style="font-size: 14px;">
                            <i class="fas fa-redo"></i> Recharger
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$additional_scripts = "
    // Diagnostic au chargement de la page
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🔧 DIAGNOSTIC: payment.php chargé avec succès');
        console.log('Solution ID: " . $solution_id . "');
        console.log('User ID: " . $user_id . "');
        console.log('Solution Status: " . ($solution['status'] ?? 'N/A') . "');
        console.log('Amount: " . ($solution['amount'] ?? 'N/A') . "');
        console.log('Test Mode: " . ($test_mode ? 'true' : 'false') . "');
        console.log('Debug Mode: " . ($debug_mode ? 'true' : 'false') . "');
        
        // Afficher un message de succès si on arrive ici
        " . ($test_mode ? "
        setTimeout(() => {
            alert('✅ DIAGNOSTIC: SUCCÈS!\\n\\nLa redirection de user_feedback.php vers payment.php a fonctionné correctement.\\n\\nSolution ID: " . $solution_id . "\\nStatut: " . ($solution['status'] ?? 'N/A') . "');
        }, 500);
        " : "") . "
        
        // Fonction de test pour vérifier le fonctionnement
        window.testPaymentPage = function() {
            console.log('✅ Page de paiement fonctionnelle');
            console.log('📋 Données de la solution:', {
                id: " . $solution_id . ",
                status: '" . ($solution['status'] ?? 'N/A') . "',
                amount: " . ($solution['amount'] ?? 0) . ",
                title: '" . addslashes($solution['problem_title'] ?? 'N/A') . "'
            });
            return true;
        };
        
        console.log('💡 Tapez testPaymentPage() pour vérifier le fonctionnement');
    });
    
    // Gestion du formulaire de paiement
    const paymentForm = document.getElementById('payment-form');
    if (paymentForm) {
        paymentForm.addEventListener('submit', function(e) {
            console.log('🔧 DIAGNOSTIC: Soumission du formulaire de paiement');
            
            const paymentMethod = document.querySelector('input[name=\"payment_method\"]:checked');
            if (!paymentMethod) {
                e.preventDefault();
                alert('Veuillez sélectionner une méthode de paiement.');
                return false;
            }
            
            console.log('Méthode sélectionnée:', paymentMethod.value);
            
            // Afficher un indicateur de chargement
            const submitBtn = document.getElementById('pay-button');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class=\"fas fa-spinner fa-spin\"></i> Traitement...';
            }
        });
    }
    
    // Fonction pour tester la redirection retour
    window.testReturnToUserFeedback = function(message) {
        const url = 'user_feedback.php?payment_debug=' + encodeURIComponent(message || 'test_return');
        console.log('🧪 Test de retour vers:', url);
        window.location.href = url;
    };
    
    // Auto-diagnostic au bout de 3 secondes
    setTimeout(() => {
        console.log('🎉 DIAGNOSTIC FINAL: La page payment.php fonctionne correctement !');
        console.log('✅ Connexion DB: OK');
        console.log('✅ Récupération solution: OK');
        console.log('✅ Vérifications sécurité: OK');
        console.log('✅ Affichage page: OK');
        
        " . ($test_mode ? "
        console.log('🔧 MODE TEST: Vous pouvez maintenant tester le paiement ou retourner à user_feedback.php');
        " : "") . "
    }, 3000);
";

include 'footer.php';
?>
