// Configuration de Google Pay
const googlePayClient = new google.payments.api.PaymentsClient({
  environment: 'PRODUCTION'
});

// Configuration des paramètres de paiement
function checkGooglePayAvailability(solutionId, price) {
  console.log('Checking Google Pay for solution:', solutionId, 'price:', price);
  
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
      console.log('Google Pay availability response:', response);
      if (response.result) {
        // Google Pay est disponible
        const container = document.getElementById('google-pay-container');
        container.style.display = 'block';
        
        const button = googlePayClient.createButton({
          onClick: () => onGooglePaymentButtonClicked(solutionId, price),
          buttonColor: 'black',
          buttonType: 'pay'
        });
        
        container.innerHTML = '';
        container.appendChild(button);
      }
    })
    .catch(error => {
      console.error('Google Pay error:', error);
    });
}

function onGooglePaymentButtonClicked(solutionId, price) {
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
          'gateway': 'example',
          'gatewayMerchantId': 'BCR2DN4T27J25DTI'
        }
      }
    }],
    merchantInfo: {
      merchantId: 'BCR2DN4T27J25DTI',
      merchantName: 'Freelancer Platform'
    },
    transactionInfo: {
      totalPriceStatus: 'FINAL',
      totalPrice: price.toString(),
      currencyCode: 'EUR',
      countryCode: 'FR'
    }
  };

  googlePayClient.loadPaymentData(paymentDataRequest)
    .then(paymentData => {
      processPayment(paymentData, solutionId);
    })
    .catch(error => {
      console.error('Error loading payment data:', error);
    });
}

function processPayment(paymentData, solutionId) {
  fetch('api/process_google_pay.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      paymentData: paymentData,
      solutionId: solutionId
    })
  })
  .then(response => response.json())
  .then(result => {
    if (result.success) {
      window.location.href = 'payment.php?success=1';
    } else {
      alert('Erreur: ' + (result.error || 'Une erreur est survenue'));
    }
  })
  .catch(error => {
    console.error('Payment processing error:', error);
    alert('Une erreur est survenue lors du traitement du paiement');
  });
}
