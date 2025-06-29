<?php
session_start();
require_once 'verification.php';

if (!isLoggedIn() || !isset($_SESSION['payment_data'])) {
    header('Location: premium_solutions.php');
    exit;
}

$payment_data = $_SESSION['payment_data'];
$page_title = "Paiement Stripe - Simulation";

$additional_css = "
    .stripe-container {
        max-width: 500px;
        margin: 50px auto;
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }

    .stripe-header {
        background: linear-gradient(135deg, #635bff, #4f46e5);
        color: white;
        padding: 25px;
        text-align: center;
    }

    .stripe-logo {
        font-size: 1.8em;
        font-weight: bold;
        margin-bottom: 10px;
    }

    .payment-form {
        padding: 30px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #374151;
    }

    .form-control {
        width: 100%;
        padding: 12px 16px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 16px;
        transition: border-color 0.3s;
        box-sizing: border-box;
    }

    .form-control:focus {
        outline: none;
        border-color: #635bff;
        box-shadow: 0 0 0 3px rgba(99, 91, 255, 0.1);
    }

    .card-row {
        display: flex;
        gap: 15px;
    }

    .card-row .form-group {
        flex: 1;
    }

    .payment-summary {
        background: #f9fafb;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
    }

    .summary-row:last-child {
        font-weight: bold;
        font-size: 1.1em;
        border-top: 1px solid #e5e7eb;
        padding-top: 10px;
        margin-bottom: 0;
    }

    .btn-stripe {
        width: 100%;
        background: #635bff;
        color: white;
        padding: 16px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .btn-stripe:hover {
        background: #5b54f0;
        transform: translateY(-1px);
    }

    .btn-stripe:disabled {
        background: #9ca3af;
        cursor: not-allowed;
        transform: none;
    }

    .security-badges {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #e5e7eb;
    }

    .security-badge {
        display: flex;
        align-items: center;
        gap: 5px;
        color: #6b7280;
        font-size: 12px;
    }

    .loading-spinner {
        width: 20px;
        height: 20px;
        border: 2px solid transparent;
        border-top: 2px solid white;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
";

include 'header.php';
?>

<div class="stripe-container">
    <div class="stripe-header">
        <div class="stripe-logo">
            <i class="fab fa-cc-stripe"></i> Stripe
        </div>
        <p>Paiement sécurisé par carte bancaire</p>
    </div>

    <form class="payment-form" id="paymentForm">
        <div class="payment-summary">
            <h4 style="margin-top: 0; color: #374151;">Résumé de la commande</h4>
            <div class="summary-row">
                <span>Solution:</span>
                <span><?= htmlspecialchars($payment_data['solution_title']) ?></span>
            </div>
            <div class="summary-row">
                <span>Prix:</span>
                <span><?= number_format($payment_data['amount'], 2) ?> €</span>
            </div>
            <div class="summary-row">
                <span>Frais de traitement:</span>
                <span>0,00 €</span>
            </div>
            <div class="summary-row">
                <span>Total:</span>
                <span><?= number_format($payment_data['amount'], 2) ?> €</span>
            </div>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" class="form-control" value="<?= htmlspecialchars(getCurrentUser()['email'] ?? '') ?>" required>
        </div>

        <div class="form-group">
            <label for="cardNumber">Numéro de carte</label>
            <input type="text" id="cardNumber" class="form-control" placeholder="1234 5678 9012 3456" maxlength="19" required>
        </div>

        <div class="card-row">
            <div class="form-group">
                <label for="expiry">MM/AA</label>
                <input type="text" id="expiry" class="form-control" placeholder="12/25" maxlength="5" required>
            </div>
            <div class="form-group">
                <label for="cvc">CVC</label>
                <input type="text" id="cvc" class="form-control" placeholder="123" maxlength="4" required>
            </div>
        </div>

        <div class="form-group">
            <label for="cardName">Nom sur la carte</label>
            <input type="text" id="cardName" class="form-control" value="<?= htmlspecialchars(getCurrentUser()['name'] ?? '') ?>" required>
        </div>

        <button type="submit" class="btn-stripe" id="payButton">
            <span id="buttonText">
                <i class="fas fa-lock"></i> Payer <?= number_format($payment_data['amount'], 2) ?> €
            </span>
            <div id="buttonSpinner" class="loading-spinner" style="display: none;"></div>
        </button>

        <div class="security-badges">
            <div class="security-badge">
                <i class="fas fa-shield-alt"></i>
                <span>SSL Sécurisé</span>
            </div>
            <div class="security-badge">
                <i class="fas fa-lock"></i>
                <span>Chiffrement 256-bit</span>
            </div>
            <div class="security-badge">
                <i class="fas fa-credit-card"></i>
                <span>PCI DSS</span>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('paymentForm');
    const payButton = document.getElementById('payButton');
    const buttonText = document.getElementById('buttonText');
    const buttonSpinner = document.getElementById('buttonSpinner');
    
    // Formatage automatique du numéro de carte
    const cardNumberInput = document.getElementById('cardNumber');
    cardNumberInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
        let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
        e.target.value = formattedValue;
    });
    
    // Formatage de la date d'expiration
    const expiryInput = document.getElementById('expiry');
    expiryInput.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length >= 2) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4);
        }
        e.target.value = value;
    });
    
    // Validation CVC
    const cvcInput = document.getElementById('cvc');
    cvcInput.addEventListener('input', function(e) {
        e.target.value = e.target.value.replace(/[^0-9]/g, '');
    });
    
    // Soumission du formulaire
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validation basique
        if (!validateForm()) {
            return;
        }
        
        // Désactiver le bouton et afficher le spinner
        payButton.disabled = true;
        buttonText.style.display = 'none';
        buttonSpinner.style.display = 'block';
        
        // Simuler le traitement du paiement
        setTimeout(function() {
            // Rediriger vers la page de confirmation
            window.location.href = 'complete_payment.php?payment_id=<?= $payment_data['payment_id'] ?>&status=success';
        }, 2500);
    });
    
    function validateForm() {
        const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g, '');
        const expiry = document.getElementById('expiry').value;
        const cvc = document.getElementById('cvc').value;
        const email = document.getElementById('email').value;
        const cardName = document.getElementById('cardName').value;
        
        if (cardNumber.length < 13 || cardNumber.length > 19) {
            alert('Numéro de carte invalide');
            return false;
        }
        
        if (!/^\d{2}\/\d{2}$/.test(expiry)) {
            alert('Date d\'expiration invalide (MM/AA)');
            return false;
        }
        
        if (cvc.length < 3 || cvc.length > 4) {
            alert('Code CVC invalide');
            return false;
        }
        
        if (!email || !email.includes('@')) {
            alert('Email invalide');
            return false;
        }
        
        if (!cardName.trim()) {
            alert('Nom sur la carte requis');
            return false;
        }
        
        return true;
    }
});

// Empêcher la fermeture accidentelle pendant le traitement
window.addEventListener('beforeunload', function(e) {
    if (document.getElementById('payButton').disabled) {
        e.preventDefault();
        e.returnValue = 'Votre paiement est en cours de traitement. Êtes-vous sûr de vouloir quitter cette page ?';
    }
});
</script>

<?php
$additional_scripts = "
    // Animation d'entrée
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.querySelector('.stripe-container');
        container.style.opacity = '0';
        container.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            container.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            container.style.opacity = '1';
            container.style.transform = 'translateY(0)';
        }, 100);
    });
";

include 'footer.php';
?>
