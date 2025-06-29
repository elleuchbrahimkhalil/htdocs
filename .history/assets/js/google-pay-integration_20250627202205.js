// Configuration de Google Pay - CORRIGÉE
const googlePayClient = new google.payments.api.PaymentsClient({
  environment: 'TEST' // Changé en TEST pour le développement
});

// Configuration des paramètres de paiement
function checkGooglePayAvailability(solutionId, price) {
  console.log('🔧 DIAGNOSTIC: Checking Google Pay for solution:', solutionId, 'price:', price);
  
  const isReadyToPayRequest = {
    apiVersion: 2,
    apiVersionMinor: 0,
    allowedPaymentMethods: [{
      type: 'CARD',
      parameters: {
        allowedAuthMethods: ['PAN_ONLY', 'CRYPTOGRAM_3DS'],
        allowedCardNetworks: ['MASTERCARD', 'VISA', 'AMEX']
      }
    }]
  };

  googlePayClient.isReadyToPay(isReadyToPayRequest)
    .then(response => {
      console.log('🔧 DIAGNOSTIC: Google Pay availability response:', response);
      if (response.result) {
        // Google Pay est disponible
        const container = document.getElementById('google-pay-container');
        if (container) {
          container.style.display = 'block';
          
          const button = googlePayClient.createButton({
            onClick: () => onGooglePaymentButtonClicked(solutionId, price),
            buttonColor: 'black',
            buttonType: 'pay'
          });
          
          container.innerHTML = '';
          container.appendChild(button);
          console.log('✅ DIAGNOSTIC: Google Pay button created successfully');
        } else {
          console.error('❌ DIAGNOSTIC: google-pay-container not found in DOM');
        }
      } else {
        console.log('ℹ️ DIAGNOSTIC: Google Pay not available on this device/browser');
      }
    })
    .catch(error => {
      console.error('❌ DIAGNOSTIC: Google Pay error:', error);
    });
}

function onGooglePaymentButtonClicked(solutionId, price) {
  console.log('🔧 DIAGNOSTIC: Google Pay button clicked', { solutionId, price });
  
  const paymentDataRequest = {
    apiVersion: 2,
    apiVersionMinor: 0,
    allowedPaymentMethods: [{
      type: 'CARD',
      parameters: {
        allowedAuthMethods: ['PAN_ONLY', 'CRYPTOGRAM_3DS'],
        allowedCardNetworks: ['MASTERCARD', 'VISA', 'AMEX']
      },
      tokenizationSpecification: {
        type: 'PAYMENT_GATEWAY',
        parameters: {
          // Configuration pour un environnement de test
          'gateway': 'stripe', // Changé de 'example' à 'stripe'
          'stripe:version': '2020-08-27',
          'stripe:publishableKey': 'pk_test_your_publishable_key_here' // À remplacer par votre clé
        }
      }
    }],
    merchantInfo: {
      merchantId: '12345678901234567890', // ID de test Google Pay
      merchantName: 'Freelancer Platform'
    },
    transactionInfo: {
      totalPriceStatus: 'FINAL',
      totalPrice: price.toString(),
      currencyCode: 'EUR',
      countryCode: 'FR'
    }
  };

  console.log('🔧 DIAGNOSTIC: Payment data request:', paymentDataRequest);

  googlePayClient.loadPaymentData(paymentDataRequest)
    .then(paymentData => {
      console.log('✅ DIAGNOSTIC: Payment data received:', paymentData);
      processPayment(paymentData, solutionId);
    })
    .catch(error => {
      console.error('❌ DIAGNOSTIC: Error loading payment data:', error);
      
      // Afficher une erreur utilisateur-friendly
      if (error.statusCode === 'CANCELED') {
        console.log('ℹ️ DIAGNOSTIC: User canceled the payment');
      } else {
        alert('Erreur lors du chargement des données de paiement: ' + error.statusMessage);
      }
    });
}

function processPayment(paymentData, solutionId) {
  console.log('🔧 DIAGNOSTIC: Processing payment', { solutionId, paymentData });
  
  // Afficher un indicateur de chargement
  showLoadingIndicator(true);
  
  fetch('api/process_google_pay.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: JSON.stringify({
      paymentData: paymentData,
      solutionId: solutionId
    })
  })
  .then(response => {
    console.log('🔧 DIAGNOSTIC: Server response status:', response.status);
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    return response.json();
  })
  .then(result => {
    console.log('🔧 DIAGNOSTIC: Server response:', result);
    showLoadingIndicator(false);
    
    if (result.success) {
      console.log('✅ DIAGNOSTIC: Payment successful, redirecting...');
      // Redirection vers la page de succès
      window.location.href = 'payment_success.php?payment_id=' + (result.payment_id || 'unknown');
    } else {
      console.error('❌ DIAGNOSTIC: Payment failed:', result.error);
      alert('Erreur: ' + (result.error || 'Une erreur est survenue lors du paiement'));
    }
  })
  .catch(error => {
    console.error('❌ DIAGNOSTIC: Payment processing error:', error);
    showLoadingIndicator(false);
    alert('Une erreur est survenue lors du traitement du paiement: ' + error.message);
  });
}

// Fonction pour afficher/masquer l'indicateur de chargement
function showLoadingIndicator(show) {
  let indicator = document.getElementById('payment-loading');
  
  if (show) {
    if (!indicator) {
      indicator = document.createElement('div');
      indicator.id = 'payment-loading';
      indicator.innerHTML = `
        <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
                    background: rgba(0,0,0,0.5); display: flex; justify-content: center; 
                    align-items: center; z-index: 9999;">
          <div style="background: white; padding: 20px; border-radius: 8px; text-align: center;">
            <div style="width: 40px; height: 40px; border: 4px solid #f3f3f3; 
                        border-top: 4px solid #3498db; border-radius: 50%; 
                        animation: spin 1s linear infinite; margin: 0 auto 15px;"></div>
            <p>Traitement du paiement Google Pay...</p>
          </div>
        </div>
        <style>
          @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
          }
        </style>
      `;
      document.body.appendChild(indicator);
    }
    indicator.style.display = 'block';
  } else {
    if (indicator) {
      indicator.style.display = 'none';
    }
  }
}

// Fonction d'initialisation
function initializeGooglePay(solutionId, price) {
  console.log('🔧 DIAGNOSTIC: Initializing Google Pay', { solutionId, price });
  
  // Vérifier que l'API Google Pay est chargée
  if (typeof google === 'undefined' || !google.payments) {
    console.error('❌ DIAGNOSTIC: Google Pay API not loaded');
    return;
  }
  
  // Vérifier les paramètres
  if (!solutionId || !price || price <= 0) {
    console.error('❌ DIAGNOSTIC: Invalid parameters', { solutionId, price });
    return;
  }
  
  checkGooglePayAvailability(solutionId, price);
}

// Export pour utilisation globale
window.initializeGooglePay = initializeGooglePay;
window.checkGooglePayAvailability = checkGooglePayAvailability;

console.log('✅ DIAGNOSTIC: Google Pay integration script loaded');
