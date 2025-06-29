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
            
            <a href="cancel_payment.php