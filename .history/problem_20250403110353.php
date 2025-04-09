<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

// Vérifier si l'ID est fourni
if (!isset($_GET['id'])) {
    header('Location: exacueil.php');
    exit;
}

$conn = connect();
$problem_id = (int)$_GET['id'];
$user_id = isLoggedIn() ? $_SESSION['user_id'] : 0;

try {
    // Récupérer le problème complet avec gestion des utilisateurs non connectés
    if (isLoggedIn()) {
        // Version compatible SQL Server
        $stmt = $conn->prepare("
            SELECT p.*, u.username, u.avatar_url,
                   CASE WHEN EXISTS(SELECT 1 FROM favorites WHERE problem_id = p.problem_id AND user_id = ?) THEN 1 ELSE 0 END AS is_favorite,
                   CASE WHEN EXISTS(SELECT 1 FROM solutions WHERE problem_id = p.problem_id AND user_id = ? AND status IN ('approved','accepted')) THEN 1 ELSE 0 END AS is_solved
            FROM problems p 
            JOIN users u ON p.user_id = u.id
            WHERE p.problem_id = ?
        ");
        $stmt->execute([$user_id, $user_id, $problem_id]);
    } else {
        // Version pour utilisateurs non connectés
        $stmt = $conn->prepare("
            SELECT p.*, u.username, u.avatar_url,
                   0 AS is_favorite,
                   0 AS is_solved
            FROM problems p 
            JOIN users u ON p.user_id = u.id
            WHERE p.problem_id = ?
        ");
        $stmt->execute([$problem_id]);
    }
    
    $problem = $stmt->fetch();
    
    if (!$problem) {
        throw new Exception("Problème non trouvé");
    }

    // Récupérer les solutions
    $solutions_stmt = $conn->prepare("
        SELECT s.*, u.username 
        FROM solutions s
        JOIN users u ON s.user_id = u.id
        WHERE s.problem_id = ? AND s.status = 'approved'
        ORDER BY s.created_at DESC
    ");
    $solutions_stmt->execute([$problem_id]);
    $solutions = $solutions_stmt->fetchAll();

} catch (Exception $e) {
    $_SESSION['error_message'] = "Erreur: " . $e->getMessage();
    header('Location: exacueil.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($problem['title']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Styles similaires à votre page d'accueil */
        body { font-family: 'Segoe UI', sans-serif; margin: 0; padding: 20px; }
        .problem-container { max-width: 800px; margin: 0 auto; }
        .problem-header { display: flex; align-items: center; margin-bottom: 20px; }
        .problem-title { font-size: 1.5em; font-weight: bold; }
        .problem-meta { color: #666; margin: 10px 0; }
        .problem-content { margin: 20px 0; }
        .solution { border: 1px solid #ddd; padding: 15px; margin: 15px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="problem-container">
        <div class="problem-header">
            <h1 class="problem-title"><?= htmlspecialchars($problem['title']) ?></h1>
            <?php if (isLoggedIn()): ?>
            <button class="fav-btn <?= $problem['is_favorite']?'active':'' ?>" 
                    data-pid="<?= $problem['problem_id'] ?>">
                <i class="fas fa-heart"></i>
            </button>
            <?php endif; ?>
        </div>
        
        <div class="problem-meta">
            Posté par <?= htmlspecialchars($problem['username']) ?> 
            le <?= date('d/m/Y à H:i', strtotime($problem['created_at'])) ?>
        </div>
        
        <div class="problem-content">
            <h3>Description</h3>
            <p><?= nl2br(htmlspecialchars($problem['description'])) ?></p>
            
            <?php if(!empty($problem['code'])): ?>
                <h3>Code</h3>
                <pre><?= htmlspecialchars($problem['code']) ?></pre>
            <?php endif; ?>
            
            <div class="tags">
                <span class="tag diff-<?= $problem['difficulty'] ?>">
                    <?= ucfirst($problem['difficulty']) ?>
                </span>
            </div>
        </div>
        
        <div class="solutions">
            <h2>Solutions proposées</h2>
            <?php if(count($solutions) > 0): ?>
                <?php foreach($solutions as $solution): ?>
                    <div class="solution">
                        <h3>Solution par <?= htmlspecialchars($solution['username']) ?></h3>
                        <p><?= nl2br(htmlspecialchars($solution['explanation'])) ?></p>
                        <pre><?= htmlspecialchars($solution['solution_code']) ?></pre>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Aucune solution approuvée pour le moment.</p>
            <?php endif; ?>
        </div>
        
        <?php if (isLoggedIn()): ?>
        <a href="submit_solution.php?problem_id=<?= $problem['problem_id'] ?>" class="btn btn-success">
            <i class="fas fa-paper-plane"></i> Proposer une solution
        </a>
        <?php else: ?>
        <a href="exlogin.php" class="btn btn-success">
            <i class="fas fa-sign-in-alt"></i> Connectez-vous pour proposer une solution
        </a>
        <?php endif; ?>
    </div>

    <script>
    // Script pour gérer les favoris
    <?php if (isLoggedIn()): ?>
    document.querySelector('.fav-btn').addEventListener('click', function() {
        const pid = this.dataset.pid;
        const active = this.classList.contains('active');
        
        this.classList.toggle('active');
        fetch('toggle_favorite.php?problem_id=' + pid + '&redirect=0', {
            method: 'GET'
        }).catch(e => this.classList.toggle('active'));
    });
    <?php endif; ?>
    </script>
</body>
</html>