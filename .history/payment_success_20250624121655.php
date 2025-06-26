<?php
// Démarrer la session
session_start();

// Inclure les fichiers nécessaires
require_once 'verification.php';
require_once 'db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: exlogin.php');
    exit;
}

// Récupérer l'utilisateur connecté
$user = getCurrentUser();
$user_id = $user['id'];

// Vérifier si l'ID de transaction est fourni
if (!isset($_GET['transaction_id'])) {
    $_SESSION['error_message'] = "ID de transaction manquant";
    header('Location: user_feedback.php');
    exit;
}

$transaction_id = $_GET['transaction_id'];

// Variables pour les données
$payment = null;
$solution = null;
$problem = null;
$solver = null;
$error_message = '';

try {
    $pdo = connect();
    
    if (!$pdo) {
        throw new Exception("Erreur de connexion à la base de données");
    }
    
    // Récupérer les détails du paiement
    $stmt = $pdo->prepare("
        SELECT pay.*, s.solution_code, s.explanation, p.title as problem_title,
               u.username as solver_username, u.name as solver_name
        FROM payments pay
        JOIN solutions s ON pay.solution_id = s.id
        JOIN problems p ON s.problem_id = p.problem_id
        JOIN users u ON s.user_id = u.id
        WHERE pay.transaction_id = ? AND pay.payer_id = ?
    ");
    
    $stmt->execute([$transaction_id, $user_id]);
    $payment_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment_data) {
        throw new Exception("Transaction non trouvée ou non autorisée");
    }
    
    // Organiser les données
    $payment = [
        'id' => $payment_data['id'],
        'amount' => $payment_data['amount'],
        'currency' => $payment_data['currency'],
        'payment_method' => $payment_data['payment_method'],
        'status' => $payment_data['status'],
        'transaction_id' => $payment_data['transaction_id'],
        'created_at' => $payment_data['created_at']
    ];
    
    $solution = [
        'id' => $payment_data['solution_id'],
        'code' => $payment_data['solution_code'],
        'explanation' => $payment_data['explanation']
    ];
    
    $problem = [
        'title' => $payment_data['problem_title']
    ];
    
    $solver = [
        'username' => $payment_data['solver_username'],
        'name' => $payment_data['solver_name']
    ];
    
} catch (Exception $e) {
    error_log("Erreur dans payment_success.php: " . $e->getMessage());
    $error_message = $e->getMessage();
}

// Configuration de la page
$page_title = "Paiement Réussi";

$additional_css = "
    .success-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }

    .success-card {
        background: white;
        border-radius: 12px;
        padding: 30px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        text-align: center;
        margin-bottom: 20px;
    }

    .success-icon {
        font-size: 4em;
        color: #27ae60;
        margin-bottom: 20px;
        animation: checkmark 0.6s ease-in-out;
    }

    @keyframes checkmark {
        0% { transform: scale(0); }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }

    .success-title {
        color: #27ae60;
        font-size: 2em;
        margin-bottom: 15px;
        font-weight: bold;
    }

    .success-message {
        font-size: 1.2em;
        color: #666;
        margin-bottom: 25px;
    }

    .transaction-details {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin: 25px 0;
        text-align: left;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid #e0e0e0;
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .detail-label {
        font-weight: 600;
        color: #333;
    }

    .detail-value {
        color: #666;
        font-family: monospace;
    }

    .amount-highlight {
        font-size: 1.5em;
        font-weight: bold;
        color: #27ae60;
    }

    .solution-preview {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin: 25px 0;
        text-align: left;
    }

    .solution-code {
        background: #2c3e50;
        color: #ecf0f1;
        padding: 15px;
        border-radius: 6px;
        font-family: 'Courier New', monospace;
        overflow-x: auto;
        margin: 15px 0;
        max-height: 200px;
        overflow-y: auto;
    }

    .action-buttons {
        display: flex;
        justify-content: center;
        gap: 15px;
        margin-top: 30px;
        flex-wrap: wrap;
    }

    .btn {
        padding: 12px 24px;
        border-radius: 6px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        border: 1px solid;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-primary {
        background: #3498db;
        color: white;
        border-color: #3498db;
    }

    .btn-primary:hover {
        background: #2980b9;
        border-color: #2980b9;
        transform: translateY(-2px);
    }

    .btn-success {
        background: #27ae60;
        color: white;
        border-color: #27ae60;
    }

    .btn-success:hover {
        background: #219653;
        border-color: #219653;
        transform: translateY(-2px);
    }

    .btn-outline {
        background: transparent;
        color: #666;
        border-color: #ddd;
    }

    .btn-outline:hover {
        background: #f5f5f5;
        transform: translateY(-2px);
    }

    .download-section {
        background: #e8f5e8;
        border: 1px solid #c3e6c3;
        border-radius: 8px;
        padding: 20px;
        margin: 25px 0;
        text-align: center;
    }

    .download-section i {
        color: #27ae60;
        font-size: 2em;
        margin-bottom: 15px;
    }

    .receipt-info {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 6px;
        padding: 15px;
        margin: 20px 0;
        text-align: center;
    }

    .receipt-info i {
        color: #f39c12;
        margin-right: 8px;
    }

    @media (max-width: 768px) {
        .success-container {
            padding: 10px;
        }
        
        .success-card {
            padding: 20px;
        }
        
        .action-buttons {
            flex-direction: column;
        }
        
        .btn {
            width: 100%;
            justify-content: center;
        }
        
        .detail-row {
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
        }
    }
";

// Include header
include 'header.php';
?>

<div class="success-container">
    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
        </div>
        <div class="action-buttons">
            <a href="user_feedback.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Retour aux notifications
            </a>
        </div>
    <?php elseif ($payment && $solution && $problem && $solver): ?>
        <div class="success-card">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            
            <h1 class="success-title">Paiement Réussi !</h1>
            
            <p class="success-message">
                Votre paiement a été traité avec succès. Vous avez maintenant accès à la solution complète.
            </p>
            
            <div class="transaction-details">
                <h3><i class="fas fa-receipt"></i> Détails de la transaction</h3>
                
                <div class="detail-row">
                    <span class="detail-label">ID de transaction:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($payment['transaction_id']); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Montant payé:</span>
                    <span class="detail-value amount-highlight">
                        <?php echo number_format($payment['amount'], 2); ?> <?php echo htmlspecialchars($payment['currency']); ?>
                    </span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Méthode de paiement:</span>
                    <span class="detail-value">
                        <?php 
                        $payment_methods = [
                            'card' => 'Carte bancaire',
                            'paypal' => 'PayPal',
                            'bank_transfer' => 'Virement bancaire'
                        ];
                        echo $payment_methods[$payment['payment_method']] ?? $payment['payment_method'];
                        ?>
                    </span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Date et heure:</span>
                    <span class="detail-value">
                        <?php echo date('d/m/Y à H:i:s', strtotime($payment['created_at'])); ?>
                    </span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Problème:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($problem['title']); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Développeur:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($solver['name'] ?? $solver['username']); ?></span>
                </div>
                
                <div class="detail-row">
                    <span class="detail-label">Statut:</span>
                    <span class="detail-value" style="color: #27ae60; font-weight: bold;">
                        <i class="fas fa-check"></i> Confirmé
                    </span>
                </div>
            </div>
            
            <div class="solution-preview">
                <h3><i class="fas fa-code"></i> Votre solution</h3>
                
                <?php if (!empty($solution['explanation'])): ?>
                    <div style="margin-bottom: 15px;">
                        <strong>Explication:</strong>
                        <p><?php echo nl2br(htmlspecialchars($solution['explanation'])); ?></p>
                    </div>
                <?php endif; ?>
                
                <strong>Code de la solution:</strong>
                <div class="solution-code">
                    <pre><?php echo htmlspecialchars($solution['code']); ?></pre>
                </div>
            </div>
            
            <div class="download-section">
                <i class="fas fa-download"></i>
                <h3>Télécharger votre solution</h3>
                <p>Vous pouvez télécharger le code de la solution pour l'utiliser dans vos projets.</p>
                <button onclick="downloadSolution()" class="btn btn-success">
                    <i class="fas fa-download"></i> Télécharger le code
                </button>
            </div>
            
            <div class="receipt-info">
                <i class="fas fa-envelope"></i>
                Un reçu détaillé a été envoyé à votre adresse email.
            </div>
            
            <div class="action-buttons">
                <a href="user_feedback.php" class="btn btn-primary">
                    <i class="fas fa-bell"></i> Mes notifications
                </a>
                
                <a href="exacueil.php" class="btn btn-outline">
                    <i class="fas fa-home"></i> Accueil
                </a>
                
                <button onclick="printReceipt()" class="btn btn-outline">
                    <i class="fas fa-print"></i> Imprimer le reçu
                </button>
                
                <button onclick="copyTransactionId()" class="btn btn-outline">
                    <i class="fas fa-copy"></i> Copier l'ID de transaction
                </button>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$additional_scripts = "
    // Fonction pour télécharger la solution
    function downloadSolution() {
        const solutionCode = `" . addslashes($solution['code'] ?? '') . "`;
        const problemTitle = `" . addslashes($problem['title'] ?? '') . "`;
        const filename = 'solution_' + problemTitle.replace(/[^a-z0-9]/gi, '_').toLowerCase() + '.txt';
        
        const element = document.createElement('a');
        const file = new Blob([solutionCode], {type: 'text/plain'});
        element.href = URL.createObjectURL(file);
        element.download = filename;
        document.body.appendChild(element);
        element.click();
        document.body.removeChild(element);
        
        // Afficher un message de confirmation
        showMessage('Solution téléchargée avec succès!', 'success');
    }
    
    // Fonction pour imprimer le reçu
    function printReceipt() {
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Reçu de Paiement - ${document.title}</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .success-icon { display: none; }
                    .action-buttons { display: none; }
                    .download-section { display: none; }
                    .solution-code { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; }
                    .transaction-details { border: 1px solid #ddd; padding: 15px; }
                    .detail-row { margin: 10px 0; }
                    @media print {
                        body { margin: 0; }
                        .no-print { display: none; }
                    }
                </style>
            </head>
            <body>
                <h1>Reçu de Paiement</h1>
                ${printContent}
                <div style='margin-top: 30px; text-align: center; font-size: 12px; color: #666;'>
                    Imprimé le ${new Date().toLocaleDateString('fr-FR')} à ${new Date().toLocaleTimeString('fr-FR')}
                </div>
            </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
    }
    
    // Fonction pour copier l'ID de transaction
    function copyTransactionId() {
        const transactionId = '" . htmlspecialchars($payment['transaction_id'] ?? '') . "';
        navigator.clipboard.writeText(transactionId).then(() => {
            showMessage('ID de transaction copié dans le presse-papiers!', 'success');
        }).catch(err => {
            console.error('Erreur lors de la copie:', err);
            // Fallback pour les navigateurs plus anciens
            const textArea = document.createElement('textarea');
            textArea.value = transactionId;
            document.body.appendChild(textArea);
            textArea.select();
            try {
                document.execCommand('copy');
                showMessage('ID de transaction copié!', 'success');
            } catch (err) {
                showMessage('Impossible de copier l\\'ID de transaction', 'error');
            }
            document.body.removeChild(textArea);
        });
    }
    
    // Fonction pour afficher des messages
    function showMessage(message, type = 'info') {
        const messageDiv = document.createElement('div');
        messageDiv.style.position = 'fixed';
        messageDiv.style.top = '20px';
        messageDiv.style.right = '20px';
        messageDiv.style.padding = '15px 20px';
        messageDiv.style.borderRadius = '6px';
        messageDiv.style.zIndex = '9999';
        messageDiv.style.maxWidth = '300px';
        messageDiv.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
        messageDiv.style.transition = 'all 0.3s ease';
        messageDiv.style.transform = 'translateX(100%)';
        
        switch(type) {
            case 'success':
                messageDiv.style.backgroundColor = '#d4edda';
                messageDiv.style.color = '#155724';
                messageDiv.style.border = '1px solid #c3e6cb';
                messageDiv.innerHTML = '<i class=\"fas fa-check-circle\"></i> ' + message;
                break;
            case 'error':
                messageDiv.style.backgroundColor = '#f8d7da';
                messageDiv.style.color = '#721c24';
                messageDiv.style.border = '1px solid #f5c6cb';
                messageDiv.innerHTML = '<i class=\"fas fa-exclamation-circle\"></i> ' + message;
                break;
            default:
                messageDiv.style.backgroundColor = '#d1ecf1';
                messageDiv.style.color = '#0c5460';
                messageDiv.style.border = '1px solid #bee5eb';
                messageDiv.innerHTML = '<i class=\"fas fa-info-circle\"></i> ' + message;
        }
        
        document.body.appendChild(messageDiv);
        
        // Animation d'entrée
        setTimeout(() => {
            messageDiv.style.transform = 'translateX(0)';
        }, 100);
        
        // Supprimer le message après 4 secondes
        setTimeout(() => {
            messageDiv.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.parentNode.removeChild(messageDiv);
                }
            }, 300);
        }, 4000);
    }
    
    // Animation d'entrée pour les éléments
    document.addEventListener('DOMContentLoaded', function() {
        const elements = document.querySelectorAll('.success-card > *');
        elements.forEach((element, index) => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            setTimeout(() => {
                element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, index * 150);
        });
        
        // Animation des boutons
        const buttons = document.querySelectorAll('.btn');
        buttons.forEach(button => {
            button.addEventListener('mouseenter', () => {
                button.style.transform = 'translateY(-2px)';
                button.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
            });
            
            button.addEventListener('mouseleave', () => {
                button.style.transform = '';
                button.style.boxShadow = '';
            });
        });
    });
    
    // Confetti animation (optionnel)
    function createConfetti() {
        const colors = ['#f39c12', '#e74c3c', '#9b59b6', '#3498db', '#2ecc71'];
        const confettiCount = 50;
        
        for (let i = 0; i < confettiCount; i++) {
            const confetti = document.createElement('div');
            confetti.style.position = 'fixed';
            confetti.style.width = '10px';
            confetti.style.height = '10px';
            confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            confetti.style.left = Math.random() * window.innerWidth + 'px';
            confetti.style.top = '-10px';
            confetti.style.zIndex = '10000';
            confetti.style.borderRadius = '50%';
            confetti.style.pointerEvents = 'none';
            
            document.body.appendChild(confetti);
            
            const fallDuration = Math.random() * 3000 + 2000;
            const fallDistance = window.innerHeight + 20;
            
            confetti.animate([
                { transform: 'translateY(0) rotate(0deg)', opacity: 1 },
                { transform: `translateY(${fallDistance}px) rotate(360deg)`, opacity: 0 }
            ], {
                duration: fallDuration,
                easing: 'cubic-bezier(0.25, 0.46, 0.45, 0.94)'
            }).onfinish = () => {
                confetti.remove();
            };
        }
    }
    
    // Lancer les confettis après le chargement
    document.addEventListener('DOMContentLoaded', function() {
        setTimeout(createConfetti, 500);
    });
    
    // Fonction pour partager le succès (optionnel)
    function shareSuccess() {
        if (navigator.share) {
            navigator.share({
                title: 'Paiement réussi!',
                text: 'J\\'ai acheté une solution de code avec succès!',
                url: window.location.href
            }).catch(err => console.log('Erreur lors du partage:', err));
        } else {
            // Fallback pour les navigateurs qui ne supportent pas l'API Web Share
            const url = window.location.href;
            navigator.clipboard.writeText(url).then(() => {
                showMessage('Lien copié dans le presse-papiers!', 'success');
            });
        }
    }
    
    // Ajouter un bouton de partage si l'API est supportée
    document.addEventListener('DOMContentLoaded', function() {
        if (navigator.share || navigator.clipboard) {
            const actionButtons = document.querySelector('.action-buttons');
            if (actionButtons) {
                const shareButton = document.createElement('button');
                shareButton.className = 'btn btn-outline';
                shareButton.onclick = shareSuccess;
                shareButton.innerHTML = '<i class=\"fas fa-share-alt\"></i> Partager';
                actionButtons.appendChild(shareButton);
            }
        }
    });
    
    // Auto-redirection après un certain temps (optionnel)
    let redirectTimer = null;
    
    function startRedirectTimer() {
        let countdown = 30; // 30 secondes
        const timerElement = document.createElement('div');
        timerElement.style.position = 'fixed';
        timerElement.style.bottom = '20px';
        timerElement.style.right = '20px';
        timerElement.style.background = 'rgba(0,0,0,0.8)';
        timerElement.style.color = 'white';
        timerElement.style.padding = '10px 15px';
        timerElement.style.borderRadius = '6px';
        timerElement.style.fontSize = '14px';
        timerElement.style.zIndex = '9999';
        
        document.body.appendChild(timerElement);
        
        redirectTimer = setInterval(() => {
            countdown--;
            timerElement.innerHTML = `<i class=\"fas fa-clock\"></i> Redirection automatique dans ${countdown}s <button onclick=\"cancelRedirect()\" style=\"background:none;border:none;color:#3498db;cursor:pointer;margin-left:10px;\">Annuler</button>`;
            
            if (countdown <= 0) {
                clearInterval(redirectTimer);
                window.location.href = 'user_feedback.php';
            }
        }, 1000);
        
        // Fonction globale pour annuler la redirection
        window.cancelRedirect = function() {
            clearInterval(redirectTimer);
            timerElement.remove();
        };
    }
    
    // Démarrer le timer de redirection après 5 secondes
    setTimeout(startRedirectTimer, 5000);
";

// Include footer
include 'footer.php';
?>