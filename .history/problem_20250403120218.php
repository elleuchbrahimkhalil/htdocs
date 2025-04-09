<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';
require_once 'exmenu.php';

// Vérification robuste de l'ID du problème
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    $_SESSION['error_message'] = "ID de problème invalide";
    header('Location: exacueil.php');
    exit;
}

$problem_id = (int)$_GET['id'];
$user_id = isLoggedIn() ? (int)$_SESSION['user_id'] : 0;

try {
    $pdo = connect();
    
    if (!$pdo) {
        throw new Exception("Erreur de connexion à la base de données");
    }

    // Activer le mode erreur PDO
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Requête pour récupérer le problème
    $sql = "
        SELECT 
            p.problem_id, 
            p.title, 
            p.description, 
            p.code, 
            p.solution, 
            p.difficulty, 
            p.language, 
            p.points, 
            p.tags, 
            p.created_at,
            p.user_id,
            u.username, 
            u.name as author_name, 
            u.avatar_url,
            " . ($user_id ? "(SELECT COUNT(*) FROM solutions 
                WHERE problem_id = p.problem_id 
                AND user_id = :user_id 
                AND status = 'accepted') AS is_solved_by_me,
               (SELECT COUNT(*) FROM favorites 
                WHERE problem_id = p.problem_id 
                AND user_id = :user_id) AS is_favorite" : "0 AS is_solved_by_me, 0 AS is_favorite") . "
        FROM problems p
        JOIN users u ON p.user_id = u.id
        WHERE p.problem_id = :problem_id
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':problem_id', $problem_id, PDO::PARAM_INT);
    
    if ($user_id) {
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    $problem = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$problem) {
        $_SESSION['error_message'] = "Problème non trouvé";
        header('Location: exacueil.php');
        exit;
    }
    
    // Si l'utilisateur est connecté, vérifier s'il a déjà soumis une solution
    $user_solution = null;
    if (isLoggedIn()) {
        $stmt = $pdo->prepare("
            SELECT TOP 1 s.*, uc.price
            FROM solutions s
            LEFT JOIN user_corrections uc ON s.id = uc.solution_id
            WHERE s.problem_id = :problem_id AND s.user_id = :user_id
            ORDER BY s.created_at DESC
        ");
        
        $stmt->bindValue(':problem_id', $problem_id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $user_solution = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Récupérer le nombre de solutions soumises et approuvées
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS total_solutions
        FROM solutions
        WHERE problem_id = :problem_id
    ");
    $stmt->bindValue(':problem_id', $problem_id, PDO::PARAM_INT);
    $stmt->execute();
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Récupérer le nombre de solutions approuvées
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS approved_solutions
        FROM solutions
        WHERE problem_id = :problem_id AND status = 'accepted'
    ");
    $stmt->bindValue(':problem_id', $problem_id, PDO::PARAM_INT);
    $stmt->execute();
    $approved_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Récupérer le nombre de vues
    $stmt = $pdo->prepare("
        SELECT views
        FROM problems
        WHERE problem_id = :problem_id
    ");
    $stmt->bindValue(':problem_id', $problem_id, PDO::PARAM_INT);
    $stmt->execute();
    $views = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Afficher les détails du problème
    // (Code pour afficher les détails ici)

} catch (Exception $e) {
    $_SESSION['error_message'] = $e->getMessage();
    header('Location: exacueil.php');
    exit;
}
?>
