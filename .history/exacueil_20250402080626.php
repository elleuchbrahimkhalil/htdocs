<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

$conn = connect();
if ($conn === null) die("Database connection failed.");
if (!isLoggedIn()) header('Location: login.php');

// Messages
$success = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['error_message']);

// Récupérer les publications
try {
    $stmt = $conn->prepare("
        SELECT p.title, p.description, p.created_at, u.username, u.avatar_url,
               (SELECT COUNT(*) FROM favorites WHERE problem_id = p.problem_id AND user_id = ?) AS is_favorite,
               (SELECT COUNT(*) FROM solutions WHERE problem_id = p.problem_id AND user_id = ? AND status IN ('approved','accepted')) AS is_solved_by_me
        FROM problems p 
        JOIN users u ON p.user_id = u.id 
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $publications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Database error: ".$e->getMessage());
    $publications = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Problèmes récents</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body{font-family:'Segoe UI',sans-serif;margin:0;padding:0;background:#f5f7fa}
        .main-container{display:flex;min-height:100vh}
        .sidebar{width:200px;background:#2c3e50;color:#ecf0f1;padding:15px;position:sticky;top:0;height:100vh}
        .sidebar h2{font-size:1.2em;margin:20px 0 10px;padding-bottom:8px;border-bottom:1px solid #34495e}
        .sidebar ul{list-style:none;padding:0;margin:0 0 20px}
        .sidebar li{margin:8px 0}
        .sidebar a{color:#bdc3c7;text-decoration:none;display:flex;align-items:center;padding:8px 10px;border-radius:4px;transition:all .3s}
        .sidebar a:hover{background:#34495e;color:#fff;transform:translateX(3px)}
        .content{flex:1;padding:20px;background:#fff}
        .publications-grid{display:flex;flex-direction:column;gap:15px;margin-top:15px}
        .publication-card{border:1px solid #e0e6ed;border-radius:8px;padding:15px;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,0.05);position:relative}
        .publication-header{display:flex;align-items:center;margin-bottom:10px}
        .author-avatar{width:40px;height:40px;border-radius:50%;margin-right:10px;object-fit:cover;border:2px solid #f0f4f8}
        .username{font-weight:600;color:#2c3e50;font-size:1em}
        .date{color:#7f8c8d;font-size:0.8em}
        .publication-title{font-weight:600;font-size:1.2em;margin:10px 0;color:#2c3e50}
        .publication-description{color:#4a5568;line-height:1.5;margin-bottom:15px;font-size:0.9em}
        .tags-container{display:flex;flex-wrap:wrap;gap:5px;margin:10px 0}
        .tag{padding:3px 8px;border-radius:15px;font-size:0.75em;background:#edf2f7}
        .publication-footer{display:flex;justify-content:space-between;align-items:center;margin-top:10px;padding-top:10px;border-top:1px solid #f0f4f8}
        .publication-stats{display:flex;gap:10px;color:#718096;font-size:0.8em}
        .btn{padding:8px 15px;border-radius:4px;text-decoration:none;font-weight:600;font-size:0.85em;transition:all .2s}
        .btn-primary{background:#4299e1;color:#fff}
        .btn-primary:hover{background:#3182ce}
        .btn-success{background:#48bb78;color:#fff}
        .btn-success:hover{background:#38a169}
        .favorite-btn{position:absolute;top:10px;right:10px;background:none;border:none;font-size:1.2em;color:#cbd5e0;cursor:pointer;transition:all .2s}
        .favorite-btn:hover{transform:scale(1.1)}
        .favorite-btn.active{color:#EC4899}
        .alert{padding:10px 15px;margin-bottom:15px;border-radius:6px;font-size:0.9em;display:flex;align-items:center}
        .alert-success{background:#f0fff4;color:#2f855a}
        .alert-danger{background:#fff5f5;color:#c53030}
        @media (max-width:768px){
            .main-container{flex-direction:column}
            .sidebar{width:100%;height:auto;position:relative}
            .content{padding:15px}
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="sidebar">
            <h2>Formation</h2>
            <ul>
                <li><a href="https://www.hackerrank.com/" target="_blank"><i class="fas fa-laptop-code"></i> HackerRank</a></li>
                <li><a href="https://www.leetcode.com/" target="_blank"><i class="fas fa-file-code"></i> LeetCode</a></li>
            </ul>
            
            <h2>Compétitions</h2>
            <ul>
                <li><a href="https://icpc.global/" target="_blank"><i class="fas fa-globe"></i> ICPC</a></li>
                <li><a href="https://www.kaggle.com/" target="_blank"><i class="fas fa-chart-line"></i> Kaggle</a></li>
            </ul>
            
            <h2>Actions</h2>
            <ul>
                <li><a href="expublier.php"><i class="fas fa-plus"></i> Publier</a></li>
                <li><a href="favorites.php"><i class="fas fa-heart"></i> Favoris</a></li>
            </ul>
        </div>
        
        <div class="content">
            <?php if($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <h2>Problèmes récents</h2>
            
            <div class="publications-grid">
                <?php foreach($publications as $pub): ?>
                <div class="publication-card">
                    <button class="favorite-btn <?= $pub['is_favorite']?'active':'' ?>" 
                            data-problem-id="<?= $pub['problem_id'] ?>">
                        <i class="fas fa-heart"></i>
                    </button>
                    
                    <div class="publication-header">
                        <img src="<?= htmlspecialchars($pub['avatar_url']??'default.png') ?>" 
                             class="author-avatar" alt="Avatar">
                        <div>
                            <div class="username"><?= htmlspecialchars($pub['username']) ?></div>
                            <div class="date"><?= date('d/m/Y H:i', strtotime($pub['created_at'])) ?></div>
                        </div>
                    </div>
                    
                    <div>
                        <?php if($pub['is_solved_by_me']): ?>
                            <span class="problem-status-indicator problem-status-solved" title="Résolu"></span>
                        <?php else: ?>
                            <span class="problem-status-indicator problem-status-unsolved" title="Non résolu"></span>
                        <?php endif; ?>
                        <h3 class="publication-title"><?= htmlspecialchars($pub['title']) ?></h3>
                    </div>
                    
                    <div class="publication-description">
                        <?= nl2br(htmlspecialchars($pub['description'])) ?>
                    </div>
                    
                    <div class="tags-container">
                        <span class="difficulty-<?= $pub['difficulty'] ?> tag">
                            <?= ucfirst($pub['difficulty']) ?>
                        </span>
                        <?php if(!empty($pub['tags'])): ?>
                            <?php foreach(explode(',', $pub['tags']) as $tag): ?>
                                <span class="tag"><?= htmlspecialchars(trim($tag)) ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="publication-footer">
                        <div class="publication-stats">
                            <span><i class="fas fa-comment"></i> <?= $pub['comment_count']??0 ?></span>
                            <span><i class="fas fa-check"></i> <?= $pub['solution_count']??0 ?></span>
                        </div>
                        
                        <div class="action-buttons">
    <a href="problem.php?id=<?= htmlspecialchars($pub['problem_id']) ?>" 
       class="btn btn-primary">
        <i class="fas fa-eye"></i> Voir
    </a>
    <a href="submit_solution.php?problem_id=<?= htmlspecialchars($pub['problem_id']) ?>" 
       class="btn btn-success">
        <i class="fas fa-paper-plane"></i> Soumettre
    </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gestion des favoris
        document.querySelectorAll('.favorite-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const problemId = this.dataset.problemId;
                const isActive = this.classList.contains('active');
                
                this.classList.toggle('active');
                this.querySelector('i').style.transform = 'scale(1.2)';
                
                fetch('api/toggle_favorite.php', {
                    method: 'POST',
                    body: new URLSearchParams({
                        problem_id: problemId,
                        set_favorite: isActive ? '0' : '1',
                        csrf_token: '<?= $_SESSION['csrf_token'] ?>'
                    })
                }).catch(() => this.classList.toggle('active'));
                
                setTimeout(() => {
                    this.querySelector('i').style.transform = '';
                }, 300);
            });
        });
        
        // Animation des cartes
        document.querySelectorAll('.publication-card').forEach(card => {
            card.addEventListener('mouseenter', () => 
                card.style.boxShadow = '0 3px 10px rgba(0,0,0,0.1)');
            card.addEventListener('mouseleave', () => 
                card.style.boxShadow = '');
        });
    });
    </script>
</body>
</html>
