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

require_once '../verification.php';
require_once '../db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// Vérifier si la requête est en POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// Récupérer les données JSON de la requête
$input = json_decode(file_get_contents('php://input'), true);

// Vérifier les données requises
if (!isset($input['paymentData']) || !isset($input['solutionId'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Données manquantes']);
    exit;
}

// Récupérer l'ID de l'utilisateur connecté
$user_id = $_SESSION['user_id'];
$solution_id = (int)$input['solutionId'];

try {
    $pdo = connect();
    
    // Vérifier que la solution existe et appartient à un problème de l'utilisateur
    $stmt = $pdo->prepare("
        SELECT s.*, p.user_id as problem_owner, u.id as solver_id,
               pr.amount as price
        FROM solutions s
        JOIN problems p ON s.problem_id = p.problem_id
        JOIN users u ON s.user_id = u.id
        LEFT JOIN prices pr ON s.price_id = pr.price_id
        WHERE s.id = ? AND p.user_id = ? AND s.status = 'accepted' AND s.payment_id IS NULL
    ");
    
    $stmt->execute([$solution_id, $user_id]);
    $solution_to_pay = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$solution_to_pay) {
        http_response_code(404);
        echo json_encode(['error' => 'Solution introuvable ou non autorisée']);
        exit;
    }
    
    // Traiter le token de paiement avec votre passerelle
    $paymentToken = $input['paymentData']['paymentMethodData']['tokenizationData']['token'];

    // Si vous utilisez Stripe, par exemple:
    // require_once '../vendor/autoload.php';
    // \Stripe\Stripe::setApiKey('VOTRE_CLE_API_STRIPE');
    // 
    // try {
    //     $charge = \Stripe\Charge::create([
    //         'amount' => $solution_to_pay['price'] * 100, // En centimes
    //         'currency' => 'eur',
    //         'source' => $paymentToken,
    //         'description' => 'Paiement pour solution #' . $solution_id,
    //     ]);
    //     
    //     if (!$charge->paid) {
    //         throw new Exception('Le paiement n\'a pas été effectué');
    //     }
    // } catch (\Stripe\Exception\CardException $e) {
    //     http_response_code(400);
    //     echo json_encode(['error' => $e->getMessage()]);
    //     exit;
    // }
    
    // Créer un nouveau paiement
    $pdo->beginTransaction();
    
    // Insérer le paiement
    $stmt = $pdo->prepare("
        INSERT INTO payments (payer_id, payee_id, amount, payment_method, payment_date, status)
        VALUES (?, ?, ?, 'google_pay', GETDATE(), 'completed')
    ");
    
    $result = $stmt->execute([
        $user_id,
        $solution_to_pay['solver_id'],
        $solution_to_pay['price']
    ]);
    
    if ($result) {
        $stmt = $pdo->query("SELECT SCOPE_IDENTITY() AS id");
        $payment_id = $stmt->fetchColumn();
        
        // Mettre à jour la solution avec l'ID du paiement
        $stmt = $pdo->prepare("
            UPDATE solutions
            SET payment_id = ?, payment_date = GETDATE()
            WHERE id = ?
        ");
        
        $result = $stmt->execute([$payment_id, $solution_id]);
        
        if ($result) {
            // Mettre à jour le solde du développeur
            $stmt = $pdo->prepare("
                UPDATE users
                SET balance = balance + ?, earnings = earnings + ?
                WHERE id = ?
            ");
            
            $result = $stmt->execute([
                $solution_to_pay['price'],
                $solution_to_pay['price'],
                $solution_to_pay['solver_id']
            ]);
            
            if ($result) {
                // Créer une notification
                $stmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, type, content, related_id, created_at)
                    VALUES (?, 'payment_received', ?, ?, GETDATE())
                ");
                
                $notification_content = "Votre solution pour le problème a été payée " . $solution_to_pay['price'] . "€ via Google Pay.";
                $stmt->execute([
                    $solution_to_pay['solver_id'],
                    $notification_content,
                    $solution_id
                ]);
                
                $pdo->commit();
                echo json_encode(['success' => true, 'message' => 'Paiement effectué avec succès']);
                exit;
            }
        }
    }
    
    // En cas d'erreur
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors du traitement du paiement']);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erreur lors du traitement du paiement Google Pay: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Une erreur est survenue lors du traitement du paiement']);
    exit;
}
?>
