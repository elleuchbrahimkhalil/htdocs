<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Vérifier si un ID de paiement est fourni
$payment_id = isset($_GET['payment_id']) ? (int)$_GET['payment_id'] : 0;

if ($payment_id <= 0) {
    header('Location: user_feedback.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Récupérer les détails du paiement
try {
    $pdo = connect();
    
    if (!$pdo) {
        throw new Exception("Erreur de connexion à la base de données");
    }
    
  $stmt = $pdo->prepare("
    SELECT p.*, s.solution_code, s.explanation, 
           pr.title as problem_title, pr.description as problem_description,
           u.username as solver_username, u.name as solver_name
    FROM payments p
    JOIN solutions s ON p.solution_id = s.id
    JOIN problems pr ON s.problem_id = pr.problem_id
    JOIN users u ON s.user_id = u.id
    WHERE p.payment_id = ? AND p.payer_id = ?
");
    
    $stmt->execute([$payment_id, $user_id]);
    $payment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$payment) {
        throw new Exception("Paiement non trouvé");
    }
    
} catch (Exception $e) {
    error_log("Erreur lors de la récupération du paiement: " . $e->getMessage());
    $_SESSION['error_message'] = "Erreur lors de la récupération des détails du paiement";
    header('Location: user_feedback.php');
    exit;
}

// Configuration de la page
$page_title = "Paiement Réussi";
$additional_css = "
    .success-container {
        max-width: 800px;
        margin: 0 auto;
        text-align: center;
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
        font-size: 2.5em;
        margin-bottom: 15px;
        font-weight: bold;
    }

    .success-message {
        font-size: 1.2em;
        color: #666;
        margin-bottom: 30px;
        line-height: 1.6;
    }

    .payment-details {
        background: white;
        border-radius: 10px;
        padding: 25px;
        box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        margin-bottom: 30px;
        text-align: left;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .detail-row:last-child {
        border-bottom: none;
        font-weight: bold;
        font-size: 1.1em;
        color: #2c3e50;
    }

    .detail-label {
        color: #666;
        font-weight: 500;
    }

    .detail-value {
        color: #2c3e50;
        font-weight: 600;
    }

    .solution-preview {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
        border-left: 4px solid #27ae60;
    }

    .solution-code {
        background: #2c3e50;
        color: #ecf0f1;
        padding: 15px;
        border-radius: 6px;
        font-family: 'Courier New', monospace;
        overflow-x: auto;
        margin: 15px 0;
        line-height: 1.5;
    }

    .solution-explanation {
        background: #f0f7fb;
        padding: 15px;
        border-radius: 6px;
        border-left: 3px solid #3498db;
        margin: 15px 0;
    }

    .action-buttons {
        display: flex;
        gap: 15px;
        justify-content: center;
        flex-wrap: wrap;
        margin-top: 30px;
    }

    .btn {
        padding: 12px 24px;
        border-radius: 6px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
    }

    .btn-primary {
        background: #3498db;
        color: white;
    }

    .btn-primary:hover {
        background: #2980b9;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .btn-success {
        background: #27ae60;
        color: white;
    }

    .btn-success:hover {
        background: #219653;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .btn-outline {
        background: transparent;
        color: #666;
        border: 2px solid #ddd;
    }

    .btn-outline:hover {
        background: #f8f9fa;
        border-color: #bbb;
    }

    .download-section {
        background: #e8f5e8;
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
        border: 1px solid #c3e6cb;
    }

    .download-title {
        color: #155724;
        font-weight: bold;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .receipt-section {
        background: white;
        border: 2px dashed #ddd;
        border-radius: 8px;
        padding: 20px;
        margin: 20px 0;
        position: relative;
    }

    .receipt-section::before {
        content: '✂';
        position: absolute;
        top: -10px;
        left: 20px;
        background: white;
        padding: 0 10px;
        color: #ddd;
        font-size: 16px;
    }

    .print-btn {
        position: absolute;
        top: 15px;
        right: 15px;
        background: none;
        border: 1px solid #ddd;
        padding: 5px 10px;
        border-radius: 4px;
        cursor: pointer;
        color: #666;
    }

    .print-btn:hover {
        background: #f8f9fa;
    }

    @media (max-width: 768px) {
        .success-container {
            padding: 0 15px;
        }
        
        .success-title {
            font-size: 2em;
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
    
    .confetti {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 1000;
    }
    
    .confetti-piece {
        position: absolute;
        width: 10px;
        height: 10px;
        background: #f39c12;
        animation: confetti-fall 3s linear infinite;
    }
    
    @keyframes confetti-fall {
        0% {
            transform: translateY(-100vh) rotate(0deg);
            opacity: 1;
        }
        100% {
            transform: translateY(100vh) rotate(720deg);
            opacity: 0;
        }
    }
";

// Inclure l'en-tête
include 'header.php';
?>

<div class="success-container">
    <!-- Animation de confettis -->
    <div class="confetti" id="confetti"></div>
    
    <div class="success-icon">
        <i class="fas fa-check-circle"></i>
    </div>
    
    <h1 class="success-title">Paiement Réussi !</h1>
    
    <p class="success-message">
        Félicitations ! Votre paiement a été traité avec succès.<br>
        Vous avez maintenant accès à la solution complète.
    </p>
    
    <!-- Détails du paiement -->
    <div class="payment-details">
        <h3><i class="fas fa-receipt"></i> Détails du Paiement</h3>
        
        <div class="detail-row">
            <span class="detail-label">Numéro de transaction :</span>
            <span class="detail-value"><?php echo htmlspecialchars($payment['transaction_id']); ?></span>
        </div>
        
        <div class="detail-row">
            <span class="detail-label">Problème :</span>
            <span class="detail-value"><?php echo htmlspecialchars($payment['problem_title']); ?></span>
        </div>
        
        <div class="detail-row">
            <span class="detail-label">Développeur :</span>
            <span class="detail-value"><?php echo htmlspecialchars($payment['solver_name']); ?></span>
        </div>
        
        <div class="detail-row">
            <span class="detail-label">Date du paiement :</span>
            <span class="detail-value"><?php echo date('d/m/Y à H:i', strtotime($payment['payment_date'])); ?></span>
        </div>
        
        <div class="detail-row">
            <span class="detail-label">Montant payé :</span>
            <span class="detail-value"><?php echo number_format($payment['amount'], 2); ?> <?php echo htmlspecialchars($payment['currency']); ?></span>
        </div>
    </div>
    
    <!-- Section de téléchargement -->
    <div class="download-section">
        <div class="download-title">
            <i class="fas fa-download"></i>
            Votre Solution est Maintenant Disponible
        </div>
        <p>Vous pouvez maintenant consulter, télécharger et utiliser la solution complète.</p>
    </div>
    
    <!-- Aperçu de la solution -->
    <div class="solution-preview">
        <h3><i class="fas fa-code"></i> Solution Achetée</h3>
        
        <div class="solution-code">
            <pre><?php echo htmlspecialchars($payment['solution_code']); ?></pre>
        </div>
        
        <?php if (!empty($payment['explanation'])): ?>
            <div class="solution-explanation">
                <h4><i class="fas fa-comment-alt"></i> Explication du développeur</h4>
                <?php echo nl2br(htmlspecialchars($payment['explanation'])); ?>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Reçu imprimable -->
    <div class="receipt-section" id="receipt-section">
        <button class="print-btn" onclick="printReceipt()">
            <i class="fas fa-print"></i> Imprimer
        </button>
        
        <h3>Reçu de Paiement</h3>
        <div style="text-align: left; margin-top: 20px;">
            <p><strong>Transaction ID:</strong> <?php echo htmlspecialchars($payment['transaction_id']); ?></p>
            <p><strong>Date:</strong> <?php echo date('d/m/Y à H:i', strtotime($payment['payment_date'])); ?></p>
            <p><strong>Montant:</strong> <?php echo number_format($payment['amount'], 2); ?> <?php echo htmlspecialchars($payment['currency']); ?></p>
            <p><strong>Statut:</strong> Payé</p>
            <p><strong>Méthode:</strong> <?php echo htmlspecialchars(ucfirst($payment['payment_method'])); ?></p>
        </div>
    </div>
    
    <!-- Boutons d'action -->
    <div class="action-buttons">
        <button onclick="downloadSolution()" class="btn btn-success">
            <i class="fas fa-download"></i> Télécharger la Solution
        </button>
        
        <a href="problem.php?id=<?php echo $payment['problem_id']; ?>" class="btn btn-primary">
            <i class="fas fa-eye"></i> Voir le Problème
        </a>
        
        <button onclick="copyToClipboard()" class="btn btn-outline">
            <i class="fas fa-copy"></i> Copier la Solution
        </button>
        
        <a href="user_feedback.php" class="btn btn-outline">
            <i class="fas fa-list"></i> Mes Achats
        </a>
        
        <a href="exacueil.php" class="btn btn-outline">
            <i class="fas fa-home"></i> Accueil
        </a>
    </div>
</div>

<?php
// Scripts additionnels
$additional_scripts = "
    // Animation de confettis
    document.addEventListener('DOMContentLoaded', function() {
        createConfetti();
        
        // Supprimer les confettis après 5 secondes
        setTimeout(() => {
            const confetti = document.getElementById('confetti');
            if (confetti) {
                confetti.style.opacity = '0';
                setTimeout(() => confetti.remove(), 1000);
            }
        }, 5000);
    });
    
    function createConfetti() {
        const confettiContainer = document.getElementById('confetti');
        const colors = ['#f39c12', '#e74c3c', '#3498db', '#2ecc71', '#9b59b6', '#f1c40f'];
        
        for (let i = 0; i < 50; i++) {
            const confettiPiece = document.createElement('div');
            confettiPiece.className = 'confetti-piece';
            confettiPiece.style.left = Math.random() * 100 + '%';
            confettiPiece.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
            confettiPiece.style.animationDelay = Math.random() * 3 + 's';
            confettiPiece.style.animationDuration = (Math.random() * 3 + 2) + 's';
            confettiContainer.appendChild(confettiPiece);
        }
    }
    
    // Fonction pour télécharger la solution
    function downloadSolution() {
        const solutionCode = `" . addslashes($payment['solution_code']) . "`;
        const problemTitle = `" . addslashes($payment['problem_title']) . "`;
        const solverName = `" . addslashes($payment['solver_name']) . "`;
        const explanation = `" . addslashes($payment['explanation'] ?? '') . "`;
        
        let content = `// Solution pour: \${problemTitle}\\n`;
        content += `// Développeur: \${solverName}\\n`;
        content += `// Date d'achat: " . date('d/m/Y à H:i') . "\\n`;
        content += `// Transaction ID: " . htmlspecialchars($payment['transaction_id']) . "\\n\\n`;
        
        if (explanation) {
            content += `/*\\nExplication:\\n\${explanation}\\n*/\\n\\n`;
        }
        
        content += solutionCode;
        
        const blob = new Blob([content], { type: 'text/plain' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `solution_\${problemTitle.replace(/[^a-zA-Z0-9]/g, '_')}.txt`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
        showMessage('Solution téléchargée avec succès!', 'success');
    }
    
    // Fonction pour copier la solution dans le presse-papiers
    function copyToClipboard() {
        const solutionCode = `" . addslashes($payment['solution_code']) . "`;
        
        navigator.clipboard.writeText(solutionCode).then(function() {
            showMessage('Solution copiée dans le presse-papiers!', 'success');
        }).catch(function(err) {
            console.error('Erreur lors de la copie:', err);
            showMessage('Erreur lors de la copie', 'error');
        });
    }
    
    // Fonction pour imprimer le reçu
    function printReceipt() {
        const receiptContent = document.getElementById('receipt-section').innerHTML;
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <html>
                <head>
                    <title>Reçu de Paiement</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 20px; }
                        .print-btn { display: none; }
                        h3 { color: #2c3e50; }
                        p { margin: 8px 0; }
                    </style>
                </head>
                <body>
                    \${receiptContent}
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
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
        messageDiv.style.transition = 'opacity 0.3s ease';
        
        switch(type) {
            case 'success':
                messageDiv.style.backgroundColor = '#d4edda';
                messageDiv.style.color = '#155724';
                messageDiv.style.border = '1px solid #c3e6cb';
                messageDiv.innerHTML = '✅ ' + message;
                break;
            case 'error':
                messageDiv.style.backgroundColor = '#f8d7da';
                messageDiv.style.color = '#721c24';
                messageDiv.style.border = '1px solid #f5c6cb';
                messageDiv.innerHTML = '❌ ' + message;
                break;
            default:
                messageDiv.style.backgroundColor = '#d1ecf1';
                messageDiv.style.color = '#0c5460';
                messageDiv.style.border = '1px solid #bee5eb';
                messageDiv.innerHTML = 'ℹ️ ' + message;
        }
        
        document.body.appendChild(messageDiv);
        
        setTimeout(() => {
            messageDiv.style.opacity = '0';
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.parentNode.removeChild(messageDiv);
                }
            }, 300);
        }, 4000);
    }
    
    // Sauvegarder les détails du paiement dans le localStorage pour référence future
    const paymentDetails = {
        transaction_id: '" . htmlspecialchars($payment['transaction_id']) . "',
        problem_title: '" . addslashes($payment['problem_title']) . "',
        amount: '" . $payment['amount'] . "',
        currency: '" . htmlspecialchars($payment['currency']) . "',
        payment_date: '" . $payment['payment_date'] . "',
        solver_name: '" . addslashes($payment['solver_name']) . "'
    };
    
    localStorage.setItem('last_payment_' + paymentDetails.transaction_id, JSON.stringify(paymentDetails));
    
    // Animation d'entrée pour les éléments
    document.addEventListener('DOMContentLoaded', function() {
        const elements = document.querySelectorAll('.payment-details, .solution-preview, .receipt-section, .action-buttons');
        elements.forEach((element, index) => {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            setTimeout(() => {
                element.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            }, (index + 1) * 200);
        });
    });
    
    // Fonction pour partager le succès (optionnel)
    function shareSuccess() {
        if (navigator.share) {
            navigator.share({
                title: 'J\\'ai acheté une solution de code!',
                text: 'Je viens d\\'acheter une solution pour le problème: " . addslashes($payment['problem_title']) . "',
                url: window.location.href
            });
        } else {
            copyToClipboard();
        }
    }
    
    // Ajouter un bouton de partage si l'API est supportée
    if (navigator.share) {
        const shareButton = document.createElement('button');
        shareButton.className = 'btn btn-outline';
        shareButton.innerHTML = '<i class=\"fas fa-share\"></i> Partager';
        shareButton.onclick = shareSuccess;
        
        const actionButtons = document.querySelector('.action-buttons');
        if (actionButtons) {
            actionButtons.appendChild(shareButton);
        }
    }
    
       // Nettoyer les données de session après affichage
    " . (isset($_SESSION['payment_success']) ? "
    // Marquer le paiement comme affiché
    fetch('clear_payment_session.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            payment_id: " . $payment_id . "
        })
    });
    " : "") . "
";

// Inclure le pied de page
include 'footer.php';

// Nettoyer les variables de session
unset($_SESSION['payment_success']);
unset($_SESSION['payment_id']);
unset($_SESSION['transaction_id']);
?>
