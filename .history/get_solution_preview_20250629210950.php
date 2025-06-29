<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non autorisé']);
    exit;
}

// Récupérer l'ID de la solution
$solution_id = isset($_GET['solution_id']) ? (int)$_GET['solution_id'] : 0;

if ($solution_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID de solution invalide']);
    exit;
}

try {
    $conn = connect();
    if (!$conn) {
        throw new Exception("Erreur de connexion à la base de données");
    }

    // Récupérer les détails de la solution
    $stmt = $conn->prepare("
        SELECT s.*, u.username, u.name as developer_name
        FROM solutions s
        JOIN users u ON s.user_id = u.id
        WHERE s.id = ?
    ");
    $stmt->execute([$solution_id]);
    $solution = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$solution) {
        throw new Exception("Solution non trouvée");
    }

    // Vérifier si l'utilisateur a déjà payé
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("
        SELECT COUNT(*) 
        FROM payments 
        WHERE solution_id = ? AND payer_id = ? AND status = 'completed'
    ");
    $stmt->execute([$solution_id, $user_id]);
    $has_paid = $stmt->fetchColumn() > 0;

    // Vérifier si l'utilisateur est le propriétaire
    $is_owner = ($solution['user_id'] == $user_id);

    if ($has_paid || $is_owner) {
        // Si payé ou propriétaire, retourner un message
        echo json_encode([
            'success' => false, 
            'error' => 'Vous avez déjà accès à cette solution complète'
        ]);
        exit;
    }

    // Générer l'aperçu limité
    $preview = [];
    
    // Aperçu du code (premières lignes seulement)
    if (!empty($solution['solution_code'])) {
        $code_lines = explode("\n", $solution['solution_code']);
        $preview_lines = array_slice($code_lines, 0, 5); // 5 premières lignes
        $preview_code = implode("\n", $preview_lines);
        
        if (count($code_lines) > 5) {
            $preview_code .= "\n\n// ... " . (count($code_lines) - 5) . " lignes supplémentaires cachées";
            $preview_code .= "\n// Achetez la solution complète pour voir tout le code";
        }
        
        $preview['code_preview'] = htmlspecialchars($preview_code);
        $preview['total_lines'] = count($code_lines);
    }

    // Aperçu de l'explication (premiers caractères seulement)
    if (!empty($solution['explanation'])) {
        $explanation_preview = substr($solution['explanation'], 0, 150);
        if (strlen($solution['explanation']) > 150) {
            $explanation_preview .= "...\n\n[Explication complète disponible après achat]";
        }
        $preview['explanation_preview'] = nl2br(htmlspecialchars($explanation_preview));
    }

    $preview['solution_id'] = $solution_id;
    $preview['price'] = number_format($solution['price'], 2);
    $preview['developer_name'] = htmlspecialchars($solution['developer_name']);

    echo json_encode([
        'success' => true,
        'preview' => $preview
    ]);

} catch (Exception $e) {
    error_log("Erreur get_solution_preview.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erreur serveur',
        'details' => $e->getMessage()
    ]);
}
?>