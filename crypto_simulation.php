<?php
session_start();
require_once 'verification.php';

if (!isLoggedIn() || !isset($_SESSION['payment_data'])) {
    header('Location: premium_solutions.php');
    exit;
}

$payment_data = $_SESSION['payment_data'];
$page_title = "Paiement Crypto - Simulation";

// Générer une adresse Bitcoin fictive pour la démo
$btc_address = '1A1zP1eP5QGefi2DMPTfTL5SLmv7DivfNa';
$eth_address = '0x742d35Cc6634C0532925a3b8D4C7C4C4C4C4C4C4';

$additional_css = "
    .crypto-container {
        max-width: 600px;
        margin: 50px auto;
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }

    .crypto-header {
        background: linear-gradient(135deg, #f7931a, #ff6b35);
        color: white;
        padding: 25px;
        text-align: center;
    }

    .crypto-logo {
        font-size: 2em;
        margin-bottom: 10px;
    }

    .crypto-content {
        padding: 30px;
    }

    .crypto-selector {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
        margin-bottom: 30px;
    }

    .crypto-option {
        border: 2px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
        background: white;
    }

    .crypto-option:hover {
        border-color: #f7931a;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .crypto-option.selected {
        border-color: #f7931a;
        background: #fff7ed;
    }

    .crypto-icon {
        font-size: 2.5em;
        margin-bottom: 10px;
    }

    .bitcoin { color: #f7931a; }
    .ethereum { color: #627eea; }

    .payment-details {
        background: #f9fafb;
        border-radius: 12px;
        padding: 25px;
        margin: 20px 0;
        display: none;
    }

    .payment-details.active {
        display: block;
    }

    .qr-code {
        width: 200px;
        height: 200px;
        background: #f3f4f6;
        border: 2px dashed #d1d5db;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 20px auto;
        font-size: 3em;
        color: #9ca3af;
    }

    .address-container {
        background: white;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        padding: 15px;
        margin: 15px 0;
        position: relative;
    }

    .address-text {
        font-family: monospace;
        word-break: break-all;
        font-size: 14px;
        color: #374151;
        margin-right: 40px;
    }

    .copy-btn {
        position: absolute;
        top: 50%;
        right: 15px;
        transform: translateY(-50%);
        background: #f7931a;
        color: white;
        border: none;
        border-radius: 6px;
        padding: 8px 12px;
        cursor: pointer;
        font-size: 12px;
        transition: all 0.3s;
    }

    .copy-btn:hover {
        background: #e6820a;
    }

    .amount-display {
        text-align: center;
        margin: 20px 0;
        padding: 20px;
        background: white;
        border-radius: 8px;
        border: 2px solid #f7931a;
    }

    .crypto-amount {
        font-size: 1.8em;
        font-weight: bold;
        color: #f7931a;
        margin-bottom: 5px;
    }

    .fiat-amount {
        color: #6b7280;
        font-size: 1.1em;
    }

    .payment-instructions {
        background: #eff6ff;
        border-left: 4px solid #3b82f6;
        padding: 20px;
        margin: 20px 0;
        border-radius: 0 8px 8px 0;
    }

    .payment-instructions h4 {
        color: #1e40af;
        margin-top: 0;
    }

    .payment-instructions ol {
        margin: 10px 0;
        padding-left: 20px;
    }

    .payment-instructions li {
        margin: 8px 0;
        color: #374151;
    }

    .confirmation-section {
        text-align: center;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #e5e7eb;
    }

    .btn-confirm {
        background: #10b981;
        color: white;
        padding: 15px 30px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        margin-right: 15px;
    }

    .btn-confirm:hover {
        background: #059669;
        transform: translateY(-1px);
    }

    .btn-cancel {
        background: #6b7280;
        color: white;
        padding: 15px 30px;
        border: none;
        border-radius: 8px;
        font-size: 16px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        text-decoration: none;
        display: inline-block;
    }

    .btn-cancel:hover {
        background: #4b5563;
    }

    .timer {
        background: #fef3c7;
        color: #92400e;
        padding: 15px;
        border-radius: 8px;
        text-align: center;
        margin: 20px 0;
        font-weight: 600;
    }

    .timer-value {
        font-size: 1.2em;
        font-weight: bold;
    }
";

include 'header.php';
?>

<div class="crypto-container">
    <div class="crypto-header">
        <div class="crypto-logo">
            <i class="fab fa-bitcoin"></i> <i class="fab fa-ethereum"></i>
        </div>
        <h2>Paiement Cryptomonnaie</h2>
        <p>Paiement sécurisé et décentralisé</p>
    </div>

    <div class="crypto-content">
        <h3>Choisissez votre cryptomonnaie</h3>
        
        <div class="crypto-selector">
            <div class="crypto-option" data-crypto="bitcoin" onclick="selectCrypto('bitcoin')">
                <div class="crypto-icon bitcoin">
                    <i class="fab fa-bitcoin"></i>
                </div>
                <h4>Bitcoin</h4>
                <p>BTC</p>
            </div>
            
            <div class="crypto-option" data-crypto="ethereum" onclick="selectCrypto('ethereum')">
                <div class="crypto-icon ethereum">
                    <i class="fab fa-ethereum"></i>
                </div>
                <h4>Ethereum</h4>
                <p>ETH</p>
            </div>
        </div>

        <!-- Détails de paiement Bitcoin -->
        <div id="bitcoin-details" class="payment-details">
            <h4><i class="fab fa-bitcoin"></i> Paiement Bitcoin</h4>
            
            <div class="timer">
                <i class="fas fa-clock"></i> Temps restant pour effectuer le paiement: 
                <span class="timer-value" id="timer">15:00</span>
            </div>
            
            <div class="amount-display">
                <div class="crypto-amount">0.00123 BTC</div>
                <div class="fiat-amount"><?= number_format($payment_data['amount'], 2) ?> €</div>
            </div>
            
            <div class="qr-code">
                <i class="fas fa-qrcode"></i>
            </div>
            
            <div class="address-container">
                <div class="address-text"><?= $btc_address ?></div>
                <button class="copy-btn" onclick="copyAddress('<?= $btc_address ?>')">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
            
            <div class="payment-instructions">
                <h4><i class="fas fa-info-circle"></i> Instructions de paiement</h4>
                <ol>
                    <li>Copiez l'adresse Bitcoin ci-dessus</li>
                    <li>Ouvrez votre portefeuille Bitcoin</li>
                    <li>Envoyez exactement <strong>0.00123 BTC</strong> à cette adresse</li>
                    <li>Attendez la confirmation de la transaction</li>
                    <li>Cliquez sur "J'ai effectué le paiement" ci-dessous</li>
                </ol>
            </div>
        </div>

        <!-- Détails de paiement Ethereum -->
        <div id="ethereum-details" class="payment-details">
            <h4><i class="fab fa-ethereum"></i> Paiement Ethereum</h4>
            
            <div class="timer">
                <i class="fas fa-clock"></i> Temps restant pour effectuer le paiement: 
                <span class="timer-value" id="timer-eth">15:00</span>
            </div>
            
            <div class="amount-display">
                <div class="crypto-amount">0.0456 ETH</div>
                <div class="fiat-amount"><?= number_format($payment_data['amount'], 2) ?> €</div>
            </div>
            
            <div class="qr-code">
                <i class="fas fa-qrcode"></i>
            </div>
            
            <div class="address-container">
                <div class="address-text"><?= $eth_address ?></div>
                <button class="copy-btn" onclick="copyAddress('<?= $eth_address ?>')">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
            
            <div class="payment-instructions">
                <h4><i class="fas fa-info-circle"></i> Instructions de paiement</h4>
                <ol>
                    <li>Copiez l'adresse Ethereum ci-dessus</li>
                    <li>Ouvrez votre portefeuille Ethereum (MetaMask, etc.)</li>
                    <li>Envoyez exactement <strong>0.0456 ETH</strong> à cette adresse</li>
                    <li>Attendez la confirmation de la transaction</li>
                    <li>Cliquez sur "J'ai effectué le paiement" ci-dessous</li>
                </ol>
            </div>
        </div>

        <div class="confirmation-section" id="confirmation-section" style="display: none;">
            <button class="btn-confirm" onclick="confirmPayment()">
                <i class="fas fa-check"></i> J'ai effectué le paiement
            </button>
            
            <a href="cancel_payment.php?payment_id=<?= $payment_data['payment_id'] ?>" class="btn-cancel">
                <i class="fas fa-times"></i> Annuler
            </a>
            
            <div style="margin-top: 20px; font-size: 14px; color: #6b7280;">
                <p><i class="fas fa-shield-alt"></i> Votre paiement sera automatiquement vérifié sur la blockchain</p>
                <p><i class="fas fa-clock"></i> Les confirmations prennent généralement 10-30 minutes</p>
            </div>
        </div>
    </div>
</div>

<script>
let selectedCrypto = null;
let countdownTimer = null;
let timeLeft = 15 * 60; // 15 minutes en secondes

function selectCrypto(crypto) {
    // Désélectionner toutes les options
    document.querySelectorAll('.crypto-option').forEach(option => {
        option.classList.remove('selected');
    });
    
    // Masquer tous les détails
    document.querySelectorAll('.payment-details').forEach(details => {
        details.classList.remove('active');
    });
    
    // Sélectionner l'option choisie
    document.querySelector(`[data-crypto="${crypto}"]`).classList.add('selected');
    document.getElementById(`${crypto}-details`).classList.add('active');
    document.getElementById('confirmation-section').style.display = 'block';
    
    selectedCrypto = crypto;
    
    // Démarrer le compte à rebours
    startCountdown(crypto);
}

function startCountdown(crypto) {
    if (countdownTimer) {
        clearInterval(countdownTimer);
    }
    
    const timerElement = document.getElementById(crypto === 'bitcoin' ? 'timer' : 'timer-eth');
    
    countdownTimer = setInterval(() => {
        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        
        timerElement.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        
        if (timeLeft <= 0) {
            clearInterval(countdownTimer);
            alert('Le temps de paiement a expiré. Veuillez recommencer.');
            window.location.href = 'premium_solutions.php';
        }
        
        // Changer la couleur quand il reste moins de 5 minutes
        if (timeLeft <= 5 * 60) {
            timerElement.parentElement.style.background = '#fecaca';
            timerElement.parentElement.style.color = '#dc2626';
        }
        
        timeLeft--;
    }, 1000);
}

function copyAddress(address) {
    navigator.clipboard.writeText(address).then(() => {
        showMessage('Adresse copiée dans le presse-papiers!', 'success');
        
        // Animation du bouton
        const btn = event.target.closest('.copy-btn');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        btn.style.background = '#10b981';
        
        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.style.background = '#f7931a';
        }, 2000);
    }).catch(() => {
        showMessage('Erreur lors de la copie', 'error');
    });
}

function confirmPayment() {
    if (!selectedCrypto) {
        alert('Veuillez sélectionner une cryptomonnaie');
        return;
    }
    
    // Confirmation de l'utilisateur
    const confirmed = confirm(
        `Confirmez-vous avoir envoyé le paiement en ${selectedCrypto.toUpperCase()} ?\n\n` +
        'Attention: Ne confirmez que si vous avez réellement effectué la transaction. ' +
        'Les paiements en cryptomonnaie sont irréversibles.'
    );
    
    if (confirmed) {
        // Afficher un message de traitement
        showProcessingMessage();
        
        // Simuler la vérification de la transaction (3-5 secondes)
        setTimeout(() => {
            // Rediriger vers la page de succès
            window.location.href = `complete_payment.php?payment_id=<?= $payment_data['payment_id'] ?>&status=success&method=${selectedCrypto}`;
        }, Math.random() * 2000 + 3000); // Entre 3 et 5 secondes
    }
}

function showProcessingMessage() {
    const confirmationSection = document.getElementById('confirmation-section');
    confirmationSection.innerHTML = `
        <div style="text-align: center; padding: 30px;">
            <div style="font-size: 3em; color: #f7931a; margin-bottom: 20px;">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
            <h3 style="color: #374151; margin-bottom: 15px;">Vérification en cours...</h3>
            <p style="color: #6b7280; margin-bottom: 20px;">
                Nous vérifions votre transaction sur la blockchain.<br>
                Cela peut prendre quelques instants.
            </p>
            <div style="background: #eff6ff; padding: 15px; border-radius: 8px; border-left: 4px solid #3b82f6;">
                <p style="margin: 0; color: #1e40af; font-size: 14px;">
                    <i class="fas fa-info-circle"></i> 
                    Ne fermez pas cette page pendant la vérification
                </p>
            </div>
        </div>
    `;
}

function showMessage(message, type = 'info') {
    const messageDiv = document.createElement('div');
    messageDiv.style.position = 'fixed';
    messageDiv.style.top = '20px';
    messageDiv.style.right = '20px';
    messageDiv.style.padding = '15px 20px';
    messageDiv.style.borderRadius = '8px';
    messageDiv.style.zIndex = '10000';
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

// Empêcher la fermeture accidentelle
window.addEventListener('beforeunload', function(e) {
    if (selectedCrypto && timeLeft > 0) {
        e.preventDefault();
        e.returnValue = 'Votre session de paiement est active. Êtes-vous sûr de vouloir quitter ?';
    }
});

// Animation d'entrée
document.addEventListener('DOMContentLoaded', function() {
    const container = document.querySelector('.crypto-container');
    container.style.opacity = '0';
    container.style.transform = 'translateY(30px)';
    
    setTimeout(() => {
        container.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        container.style.opacity = '1';
        container.style.transform = 'translateY(0)';
    }, 100);
});
</script>

<?php
$additional_scripts = "
    // Simulation de mise à jour des prix crypto en temps réel
    setInterval(function() {
        if (selectedCrypto) {
            // Simuler de légères variations de prix (±2%)
            const variation = (Math.random() - 0.5) * 0.04; // ±2%
            
            if (selectedCrypto === 'bitcoin') {
                const currentAmount = 0.00123;
                const newAmount = currentAmount * (1 + variation);
                const amountElement = document.querySelector('#bitcoin-details .crypto-amount');
                if (amountElement) {
                    amountElement.textContent = newAmount.toFixed(8) + ' BTC';
                }
            } else if (selectedCrypto === 'ethereum') {
                const currentAmount = 0.0456;
                const newAmount = currentAmount * (1 + variation);
                const amountElement = document.querySelector('#ethereum-details .crypto-amount');
                if (amountElement) {
                    amountElement.textContent = newAmount.toFixed(6) + ' ETH';
                }
            }
        }
    }, 30000); // Mise à jour toutes les 30 secondes
";

include 'footer.php';
?>
