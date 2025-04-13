// Configuration de Google Pay
const googlePayClient = new google.payments.api.PaymentsClient({
  environment: 'PRODUCTION' // Environnement de production
});

// Configuration des paramètres de paiement
const baseRequest = {
  apiVersion: 2,
  apiVersionMinor: 0
};

// Configuration des méthodes de paiement acceptées
const allowedPaymentMethods = [{
  type: 'CARD',
  parameters: {
    allowedAuthMethods: ['PAN_ONLY', 'CRYPTOGRAM_3DS'],
    allowedCardNetworks: ['MASTERCARD', 'VISA', 'AMEX', 'DISCOVER']
  },
  tokenizationSpecification: {
    type: 'PAYMENT_GATEWAY',
    parameters: {
      'gateway': 'example', // Remplacer par votre gateway réelle
      'gatewayMerchantId': 'BCR2DN4T27J25DTI'
    }
  }
}];

// Fonction pour vérifier si Google Pay est disponible
function checkGooglePayAvailability(solutionId, price) {
  const isReadyToPayRequest = Object.assign({}, baseRequest);
  isReadyToPayRequest.allowedPaymentMethods = allowedPaymentMethods;
  
  googlePayClient.isReadyToPay(isReadyToPayRequest)
    .then(response => {
      if (response.result) {
        // Google Pay est disponible
        document.getElementById('google-pay-container').style.display = 'block';
        
        // Créer le bouton Google Pay
        const button = googlePayClient.createButton({
          onClick: () => onGooglePaymentButtonClicked(solutionId, price),
          buttonColor: 'black',
          buttonType: 'pay',
          buttonSizeMode: 'fill'
        });
        
        // Ajouter le bouton au conteneur
        const container = document.getElementById('google-pay-container');
        container.innerHTML = '';
        container.appendChild(button);
        
        // Rendre le conteneur visible
        container.style.display = 'block';
      } else {
        console.log('Google Pay n\'est pas disponible');
      }
    })
    .catch(error => {
      console.error('Erreur lors de la vérification de Google Pay:', error);
    });
}

// Fonction appelée lorsque l'utilisateur clique sur le bouton Google Pay
function onGooglePaymentButtonClicked(solutionId, price) {
  const paymentDataRequest = Object.assign({}, baseRequest);
  paymentDataRequest.allowedPaymentMethods = allowedPaymentMethods;
  paymentDataRequest.transactionInfo = {
    totalPriceStatus: 'FINAL',
    totalPrice: price.toString(),
    currencyCode: 'EUR',
    countryCode: 'FR'
  };
  paymentDataRequest.merchantInfo = {
    merchantId: 'BCR2DN4T27J25DTI',
    merchantName: 'Freelancer Platform'
  };

  googlePayClient.loadPaymentData(paymentDataRequest)
    .then(paymentData => {
      // Envoyer les données de paiement au serveur
      processPayment(paymentData, solutionId);
    })
    .catch(error => {
      console.error('Erreur lors du chargement des données de paiement:', error);
    });
}

// Fonction pour traiter le paiement
function processPayment(paymentData, solutionId) {
  // Afficher un indicateur de chargement
  const payButton = document.querySelector('.btn-pay');
  if (payButton) {
    payButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement en cours...';
    payButton.disabled = true;
  }

  // Envoyer les données au serveur
  fetch('api/process_google_pay.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      paymentData: paymentData,
      solutionId: solutionId
    })
  })
  .then(response => response.json())
  .then(result => {
    if (result.success) {
      // Rediriger vers la page de paiement avec un message de succès
      window.location.href = 'payment.php?success=1';
    } else {
      // Afficher l'erreur
      alert('Erreur: ' + (result.error || 'Une erreur est survenue lors du traitement du paiement'));
      
      // Réinitialiser le bouton
      if (payButton) {
        payButton.innerHTML = '<i class="fas fa-lock"></i> Payer';
        payButton.disabled = false;
      }
    }
  })
  .catch(error => {
    console.error('Erreur lors du traitement du paiement:', error);
    alert('Une erreur est survenue lors du traitement du paiement');
    
    // Réinitialiser le bouton
    if (payButton) {
      payButton.innerHTML = '<i class="fas fa-lock"></i> Payer';
      payButton.disabled = false;
    }
  });
}
