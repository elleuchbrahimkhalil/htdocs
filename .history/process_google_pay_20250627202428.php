<?php
// Configuration de sécurité
header_remove('X-Powered-By');
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.use_strict_mode', 1);
session_start();

// Protection contre le clickjacking
header('X-Frame-Options: DENY');
// Protection XSS
header('X-XSS-Protection: 1; mode=block');
// Pas de MIME-sniffing
header('X-Content-Type-Options: nosniff');
// Définir le type de contenu
header('Content-Type: application/json');

// DIAGNOSTIC
error_log("DIAGNOSTIC: Accès à process_google_pay.php");
error_log("DIAGNOSTIC: Method: " . $_SERVER['REQUEST_METHOD']);
error_log("DIAGNOSTIC: Headers: " . print_r(getallheaders(), true));

require_once '../verification.php';
require_once '../db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    error_log("DIAGNOSTIC: Utilisateur non connecté");
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé', 'debug' => 'User not logged in']);
    exit;
}

// Vérifier si la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("DIAGNOSTIC: Méthode non POST: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée', 'debug' => 'Method: ' . $_SERVER['REQUEST_METHOD']]);
    exit;
}

// Récupérer les données JSON de la requête
$input_raw = file_get_contents('php://input');
error_log("DIAGNOSTIC: Raw input: " . $input_raw);

$input = json_decode($input_raw, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("DIAGNOSTIC: Erreur JSON: " . json_last_error_msg());
    http_response_code(400);
    echo json_encode(['error' => 'Données JSON invalides', 'debug' => json_last_error_msg()]);
    exit;
}

error_log("DIAGNOSTIC: Parsed input: " . print_r($input, true));

// Vérifier les données requises
if (!isset($input['paymentData']) || !isset($input['solutionId'])) {
    error_log("DIAGNOSTIC: Données manquantes - paymentData: " . (isset($input['paymentData']) ? 'OK' : 'MISSING') . ", solutionId: " . (isset($input['solutionId']) ? 'OK' : 'MISSING'));
    http_response_code(400);
    echo json_encode(['error' => 'Données manquantes', 'debug' => 'paymentData or solutionId missing']);
    exit;
}

// Récupérer l'ID de l'utilisateur connecté
$user_id = $_SESSION['user_id'];
$solution_id = (int)$input['solutionId'];

error_log("DIAGNOSTIC: user_id = $user_id, solution_id = $solution_id");

try {
    $pdo = connect();
    
    if (!$pdo) {
        throw new Exception("Impossible de se connecter à la base de données");
    }
    
    error_log("DIAGNOSTIC: Connexion DB réussie");
    
    // Vérifier que la solution existe et appartient à un problème de l'utilisateur
    $stmt = $pdo->prepare("
        SELECT s.*, p.user_id as problem_owner, u.id as solver_id,
               pr.amount as price, pr.currency
        FROM solutions s
        JOIN problems p ON s.problem_id = p.problem_id
        JOIN users u ON s.user_id = u.id
        LEFT JOIN prices pr ON s.price_id = pr.price_id
        WHERE s.id = ? AND p.user_id = ? AND s.status IN ('accepted', 'approved', 'approve', 'accept')
    ");
    
    $stmt->execute([$solution_id, $user_id]);
    $solution_to_pay = $stmt->fetch(PDO::FETCH_ASSOC);
    
    error_log("DIAGNOSTIC: Solution trouvée: " . ($solution_to_pay ? 'OUI' : 'NON'));
    
    if (!$solution_to_pay) {
        // Debug détaillé
        $debug_stmt = $pdo->prepare("SELECT s.*, p.user_id as problem_owner FROM solutions s JOIN problems p ON s.problem_id = p.problem_id WHERE s.id = ?");
        $debug_stmt->execute([$solution_id]);
        $debug_solution = $debug_stmt->fetch(PDO::FETCH_ASSOC);
        
        $debug_info = $debug_solution ? 
            "Solution exists but criteria not met. Status: " . $debug_solution['status'] . ", Problem owner: " . $debug_solution['problem_owner'] . ", Current user: " . $user_id :
            "Solution does not exist";
        
        error_log("DIAGNOSTIC: " . $debug_info);
        
        http_response_code(404);
        echo json_encode(['error' => 'Solution introuvable ou non autorisée', 'debug' => $debug_info]);
        exit;
    }
    
    // Vérifier si le paiement a déjà été effectué - CORRIGÉ pour votre structure
    $stmt = $pdo->prepare("SELECT payment_id FROM payments WHERE solution_id = ? AND payer_id = ?");
    $stmt->execute([$solution_id, $user_id]);
    $existing_payment = $stmt->fetch();
    
       if ($existing_payment) {
        error_log("DIAGNOSTIC: Paiement déjà effectué - ID " . $existing_payment['payment_id']);
        http_response_code(409);
        echo json_encode(['error' => 'Cette solution a déjà été payée', 'debug' => 'Payment ID: ' . $existing_payment['payment_id']]);
        exit;
    }
    
    // Récupérer le token de paiement Google Pay
    $paymentToken = $input['paymentData']['paymentMethodData']['tokenizationData']['token'] ?? null;
    $paymentMethodType = $input['paymentData']['paymentMethodData']['type'] ?? 'UNKNOWN';

    error_log("DIAGNOSTIC: Type de méthode de paiement: " . $paymentMethodType);
    error_log("DIAGNOSTIC: Token reçu: " . ($paymentToken ? substr($paymentToken, 0, 20) . "..." : 'NULL'));

    if (!$paymentToken) {
        error_log("DIAGNOSTIC: Token de paiement manquant");
        http_response_code(400);
        echo json_encode(['error' => 'Token de paiement manquant', 'debug' => 'No payment token in request']);
        exit;
    }

    // Traiter le paiement selon la méthode
    $payment_success = false;
    $transaction_id = 'GPAY_' . time() . '_' . uniqid();
    
    switch ($paymentMethodType) {
        case 'CARD':
            error_log("DIAGNOSTIC: Traitement paiement par carte via Google Pay");
            
            // SIMULATION pour le développement
            // En production, vous devriez intégrer avec votre processeur de paiement
            /*
            // Exemple avec Stripe
            require_once '../vendor/autoload.php';
            \Stripe\Stripe::setApiKey('sk_test_your_secret_key');
            
            try {
                $charge = \Stripe\Charge::create([
                    'amount' => $solution_to_pay['price'] * 100, // En centimes
                    'currency' => strtolower($solution_to_pay['currency'] ?? 'eur'),
                    'source' => $paymentToken,
                    'description' => 'Paiement Google Pay pour solution #' . $solution_id,
                    'metadata' => [
                        'solution_id' => $solution_id,
                        'user_id' => $user_id,
                        'payment_method' => 'google_pay'
                    ]
                ]);
                
                if ($charge->paid) {
                    $payment_success = true;
                    $transaction_id = $charge->id;
                } else {
                    throw new Exception('Le paiement n\'a pas été effectué');
                }
                
            } catch (\Stripe\Exception\CardException $e) {
                error_log("DIAGNOSTIC: Erreur Stripe: " . $e->getMessage());
                http_response_code(400);
                echo json_encode(['error' => 'Paiement refusé: ' . $e->getMessage(), 'debug' => 'Stripe card error']);
                exit;
            } catch (\Exception $e) {
                error_log("DIAGNOSTIC: Erreur générale Stripe: " . $e->getMessage());
                http_response_code(500);
                echo json_encode(['error' => 'Erreur de traitement du paiement', 'debug' => $e->getMessage()]);
                exit;
            }
            */
            
            // SIMULATION pour le développement
            $payment_success = true;
            error_log("DIAGNOSTIC: Paiement simulé avec succès");
            break;
            
        case 'PAYPAL':
            error_log("DIAGNOSTIC: Traitement paiement PayPal via Google Pay");
            // Intégration avec l'API PayPal
            $payment_success = true;
            break;
            
        default:
            error_log("DIAGNOSTIC: Méthode de paiement non prise en charge: " . $paymentMethodType);
            http_response_code(400);
            echo json_encode(['error' => 'Méthode de paiement non prise en charge', 'debug' => 'Method: ' . $paymentMethodType]);
            exit;
    }

    // Si le paiement a échoué
    if (!$payment_success) {
        error_log("DIAGNOSTIC: Le paiement a échoué");
        http_response_code(400);
        echo json_encode(['error' => 'Le paiement a échoué', 'debug' => 'Payment processing failed']);
        exit;
    }
    
    // Créer le paiement dans la base de données
    error_log("DIAGNOSTIC: Début de la transaction DB");
    $pdo->beginTransaction();
    
    try {
        // Insérer le paiement - CORRIGÉ pour votre structure de table
        $stmt = $pdo->prepare("
            INSERT INTO payments (
                solution_id, payer_id, payee_id, amount, payment_method, 
                transaction_id, status, payment_date, created_at, notes
            ) VALUES (
                ?, ?, ?, ?, 'google_pay', ?, 'completed', GETDATE(), GETDATE(), ?
            )
        ");
        
        $notes = "Paiement Google Pay - Type: " . $paymentMethodType . " - Token: " . substr($paymentToken, 0, 20) . "...";
        
        $result = $stmt->execute([
            $solution_id,
            $user_id,
            $solution_to_pay['solver_id'],
            $solution_to_pay['price'],
            $transaction_id,
            $notes
        ]);
        
        if (!$result) {
            throw new Exception("Erreur lors de l'insertion du paiement");
        }
        
        $payment_id = $pdo->lastInsertId();
        error_log("DIAGNOSTIC: Paiement inséré avec ID: " . $payment_id);
        
        // Mettre à jour la solution
        $stmt = $pdo->prepare("
            UPDATE solutions
            SET status = 'paid', updated_at = GETDATE()
            WHERE id = ?
        ");
        
        $result = $stmt->execute([$solution_id]);
        
        if (!$result) {
            throw new Exception("Erreur lors de la mise à jour de la solution");
        }
        
        error_log("DIAGNOSTIC: Solution mise à jour");
        
        // Mettre à jour le solde du développeur (si la colonne existe)
        try {
            $stmt = $pdo->prepare("
                UPDATE users
                SET balance = ISNULL(balance, 0) + ?, 
                    earnings = ISNULL(earnings, 0) + ?
                WHERE id = ?
            ");
            
            $stmt->execute([
                $solution_to_pay['price'],
                $solution_to_pay['price'],
                $solution_to_pay['solver_id']
            ]);
            
            error_log("DIAGNOSTIC: Solde du développeur mis à jour");
        } catch (Exception $e) {
            error_log("DIAGNOSTIC: Erreur mise à jour solde (non critique): " . $e->getMessage());
            // Non critique, on continue
        }
        
        // Créer une notification (si la table existe)
        try {
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, type, content, related_id, created_at)
                VALUES (?, 'payment_received', ?, ?, GETDATE())
            ");
            
            $notification_content = "Votre solution a été payée " . $solution_to_pay['price'] . "€ via Google Pay.";
            $stmt->execute([
                $solution_to_pay['solver_id'],
                $notification_content,
                $solution_id
            ]);
            
            error_log("DIAGNOSTIC: Notification créée");
        } catch (Exception $e) {
            error_log("DIAGNOSTIC: Erreur création notification (non critique): " . $e->getMessage());
            // Non critique, on continue
        }
        
        // Valider la transaction
        $pdo->commit();
        error_log("DIAGNOSTIC: Transaction validée avec succès");
        
        // Réponse de succès
        echo json_encode([
            'success' => true, 
            'message' => 'Paiement Google Pay effectué avec succès',
            'payment_id' => $payment_id,
            'transaction_id' => $transaction_id,
            'debug' => [
                'solution_id' => $solution_id,
                'amount' => $solution_to_pay['price'],
                'currency' => $solution_to_pay['currency'] ?? 'EUR',
                'payment_method' => 'google_pay'
            ]
        ]);
        
    } catch (Exception $e) {
        // Annuler la transaction
        $pdo->rollBack();
        error_log("DIAGNOSTIC: Erreur transaction DB: " . $e->getMessage());
        throw $e;
    }
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("DIAGNOSTIC: Erreur générale dans process_google_pay.php: " . $e->getMessage());
    error_log("DIAGNOSTIC: Stack trace: " . $e->getTraceAsString());
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Une erreur est survenue lors du traitement du paiement',
        'debug' => $e->getMessage()
    ]);
}
?>
