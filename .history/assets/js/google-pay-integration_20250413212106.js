// Configuration de Google Pay
const googlePayClient = new google.payments.api.PaymentsClient({
  environment: 'TEST' // Changer en 'PRODUCTION' pour l'environnement de production
});

// Configuration des paramètres de paiement
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
        'gateway': 'VOTRE_GATEWAY', // ex: 'stripe'
        'gatewayMerchantId': 'VOTRE_ID_MARCHAND'
      }
    }
  }],
  merchantInfo: {
    merchantId: 'VOTRE_MERCHANT_ID_GOOGLE',
    merchantName: 'VOTRE_NOM_MARCHAND'
  },
  transactionInfo: {
    totalPriceStatus: 'FINAL',
    totalPrice: '0.00', // Sera mis à jour dynamiquement
    currencyCode: 'EUR'
  }
};

// Fonction pour vérifier si Google Pay est disponible
function checkGooglePayAvailability(solutionId, price) {
  const isReadyToPayRequest = Object.assign({}, paymentDataRequest);
  isReadyToPayRequest.allowedPaymentMethods = [paymentDataRequest.allowedPaymentMethods[0]];
  
  googlePayClient.isReadyToPay(isReadyToPayRequest)
    .then(response => {
      if (response.result) {
        // Afficher le bouton Google Pay
        document.getElementById('google-pay-container').style.display = 'block';
        
        // Mettre à jour le prix
        paymentDataRequest.transactionInfo.totalPrice = price.toString();
        
        // Configurer le bouton
        const button = googlePayClient.createButton({
          onClick: () => processPayment(solutionId),
          buttonColor: 'black', // ou 'white'
          buttonType: 'pay'
        });
        
        document.getElementById('google-pay-container').appendChild(button);
      }
    })
    .catch(error => {
      console.error('Google Pay n\'est pas disponible', error);
    });
}

// Fonction pour traiter le paiement
function processPayment(solutionId) {
  googlePayClient.loadPaymentData(paymentDataRequest)
    .then(paymentData => {
      // Envoyer les données de paiement au serveur
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
          // Afficher un message de succès
          alert('Paiement effectué avec succès!');
          // Rediriger ou mettre à jour l'interface
          window.location.reload();
        } else {
          // Afficher l'erreur
          alert('Erreur: ' + (result.error || 'Une erreur est survenue'));
        }
      })
      .catch(error => {
        console.error('Erreur lors du traitement du paiement', error);
        alert('Une erreur est survenue lors du traitement du paiement');
      });
    })
    .catch(error => {
      console.error('Erreur lors du chargement des données de paiement', error);
      alert('Erreur lors du chargement des données de paiement');
    });
}
