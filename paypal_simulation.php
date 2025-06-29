<?php
session_start();
require_once 'verification.php';

if (!isLoggedIn() || !isset($_SESSION['payment_data'])) {
    header('Location: premium_solutions.php');
    exit;
}

$payment_data = $_SESSION['payment_data'];
$page_title = "Paiement PayPal - Simulation";

$additional_css = "
    .payment-simulator {
        max-width: 600px;
        margin: 50px auto;
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }

    .paypal-header {
        background: linear-gradient(135deg, #0070ba, #003087);
        color: white;
        padding: 25px;
        text-align: center;
    }

    .paypal-logo {
        font-size: 2em;
        font-weight: bold;
        margin-bottom: 10px;
    }

    .payment-details {
        padding: 30px;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .detail-row:last-child {
        border-bottom: none;
        font-weight: bold;
        font-size: 1.2em;
        color: #0070ba;
    }

    .payment-actions {
        padding: 0 30px 30px;
        display: flex;
        gap: 15px;
    }

    .btn {
        flex: 1;
        padding: 15px;
        border: none;
        border-radius: 8px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .btn-paypal {
        background: #0070ba;
        color: white;
    }

    .btn-paypal:hover {
        background: #005ea6;
        transform: translateY(-2px);
    }

    .btn-cancel {
        background: #6c757d;
        color: white;
    }

    .btn-cancel:hover {
        background: #5a6268;
    }

    .security-info {
        background: #f8f9fa;
        padding: 20px;
        margin: 20px 0;
        border-radius: 8px;
        border-left: 4px solid #28a745;
    }

    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.8);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }

    .loading-content {
        background: white;
        padding: 40px;
        border-radius: 15px;
        text-align: center;
    }

    .spinner {
        width: 50px;
        height: 50px;
        border: 5px solid #f3f3f3;
        border-top: 5px solid #0070ba;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 20px;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
";

include 'header.php';
?>

<div class="payment-simulator">
    <div class="paypal-header">
        <div class="paypal-logo">
            <i class="fab fa-paypal"></i> PayPal
        </div>
        <p>Paiement sécurisé</p>
    </div>

    <div class="payment-details">
        <h3><i class="fas fa-shopping-cart"></i> Détails de votre achat</h3>
        
        <div class="detail-row">
            <span>Article :</span>
            <span>Solution - <?= htmlspecialchars($payment_data['solution_title']) ?></span>
        </div>
        
        <div class="detail-row">
            <span>Transaction ID :</span>
            <span><?= htmlspecialchars($payment_data['transaction_id']) ?></span>
        </div>
        
        <div class="detail-row">
            <span>Vendeur :</span>
            <span>CodeChallenge Platform</span>
        </div>
        
        <div class="detail-row">
            <span>Total à payer :</span>
            <span><?= number_format($payment_data['amount'], 2) ?> €</span>
        </div>

        <div class="security-info">
            <h5><i class="fas fa-shield-alt"></i> Paiement sécurisé</h5>
            <p>Vos informations de paiement sont protégées par le chiffrement SSL de PayPal. 
               Cette transaction est sécurisée et vos données bancaires ne sont jamais partagées.</p>
        </div>
    </div>

    <div class="payment-actions">
        <button onclick="processPayment()" class="btn btn-paypal">
            <i class="fas fa-credit-card"></i> Payer maintenant
        </button>
        
        <a href="cancel_payment.php?payment_id=<?= $payment_data['payment_id'] ?>" class="btn btn-cancel">
            <i class="fas fa-times"></i> Annuler
        </a>
    </div>
</div>

<!-- Overlay de chargement -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-content">
        <div class="spinner"></div>
        <h3>Traitement du paiement...</h3>
        <p>Veuillez patienter, nous finalisons votre transaction.</p>
    </div>
</div>

<script>
function processPayment() {
    // Afficher l'overlay de chargement
    document.getElementById('loadingOverlay').style.display = 'flex';
    
    // Simuler le traitement du paiement (3 secondes)
    setTimeout(function() {
        // Rediriger vers la page de confirmation
        window.location.href = 'complete_payment.php?payment_id=<?= $payment_data['payment_id'] ?>&status=success';
    }, 3000);
}

// Empêcher la fermeture accidentelle de la page
window.addEventListener('beforeunload', function(e) {
    if (document.getElementById('loadingOverlay').style.display === 'flex') {
        e.preventDefault();
        e.returnValue = 'Votre paiement est en cours de traitement. Êtes-vous sûr de vouloir quitter cette page ?';
    }
});
</script>

<?php
$additional_scripts = "
    // Animation d'entrée
    document.addEventListener('DOMContentLoaded', function() {
        const simulator = document.querySelector('.payment-simulator');
        simulator.style.opacity = '0';
        simulator.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            simulator.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            simulator.style.opacity = '1';
            simulator.style.transform = 'translateY(0)';
        }, 100);
    });
";

include 'footer.php';
?>