<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

$conn = connect();
if ($conn === null) die("Database connection failed.");
if (!isLoggedIn()) { header('Location: login.php'); exit; }

// Messages
$success = $_SESSION['success_message'] ?? '';
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

try {
    $stmt = $conn->prepare("
        SELECT p.*, u.username, u.avatar_url,
               (SELECT COUNT(*) FROM favorites WHERE problem_id = p.problem_id AND user_id = ?) AS is_favorite,
               (SELECT COUNT(*) FROM solutions WHERE problem_id = p.problem_id AND user_id = ? AND status IN ('approved','accepted')) AS is_solved
        FROM problems p JOIN users u ON p.user_id = u.id 
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $publications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("DB Error: ".$e->getMessage());
    $publications = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body{font-family:'Segoe UI',sans-serif;margin:0;padding:0;min-height:100vh;background:#f5f7fa;color:#333}
        .main-container{display:flex;flex:1}
        .sidebar{width:220px;padding:15px;background:#2c3e50;color:#ecf0f1;position:sticky;top:0;height:100vh;overflow-y:auto}
        .content{flex:1;padding:15px;background:#fff;overflow-x:auto}
        .publications-grid{display:flex;gap:15px;padding:10px;overflow-x:auto}
        .publication-card{min-width:300px;border:1px solid #e0e6ed;border-radius:8px;padding:15px;background:#fff;box-shadow:0 2px 8px rgba(0,0,0,0.1);position:relative}
        .publication-header{display:flex;align-items:center;margin-bottom:10px}
        .author-avatar{width:40px;height:40px;border-radius:50%;margin-right:10px;object-fit:cover;border:2px solid #f0f4f8}
        .publication-title{font-weight:600;font-size:1.2em;margin:10px 0;color:#2c3e50}
        .publication-description{color:#4a5568;line-height:1.5;margin-bottom:15px;font-size:0.9em}
        .tags-container{display:flex;flex-wrap:wrap;gap:5px;margin:10px 0}
        .tag{padding:3px 8px;border-radius:12px;font-size:0.75em}
        .difficulty-easy{background:#f0fff4;color:#38a169}
        .difficulty-medium{background:#fffaf0;color:#dd6b20}
        .difficulty-hard{background:#fff5f5;color:#e53e3e}
        .publication-footer{display:flex;justify-content:space-between;margin-top:10px}
        .btn{padding:8px 12px;border-radius:4px;font-size:0.8em;display:inline-flex;align-items:center;gap:5px}
        .btn-primary{background:#4299e1;color:#fff}
        .btn-success{background:#48bb78;color:#fff}
        .favorite-btn{position:absolute;top:10px;right:10px;background:none;border:none;font-size:1.2em;color:#cbd5e0}
        .favorite-btn.active{color:#e53e3e}
        .problem-status-indicator{width:10px;height:10px;border-radius:50%;display:inline-block;margin-right:5px}
        .problem-status-solved{background:#48bb78}
        .problem-status-unsolved{background:#f56565}
        .alert{padding:10px;margin-bottom:15px;border-radius:4px;display:flex;align-items:center;font-size:0.9em}
        .alert-success{background:#f0fff4;color:#2f855a}
        .alert-danger{background:#fff5f5;color:#c53030}
        @media (max-width:768px){
            .main-container{flex-direction:column}
            .sidebar{width:100%;height:auto;position:relative}
            .publications-grid{flex-direction:column}
            .publication-card{min-width:auto}
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="sidebar">
            <h2>Menu</h2>
            <ul>
                <li><a href="expublier.php"><i class="fas fa-plus"></i> Publier</a></li>
                <li><a href="favorites.php"><i class="fas fa-heart"></i> Favoris</a></li>
            </ul>
        </div>
        
        <div class="content">
            <?php if($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation"></i> <?= htmlspecialchars($error) ?>
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
                            <img src="<?= htmlspecialchars($pub['avatar_url']?:'default.png') ?>" 
                                 class="author-avatar" alt="Avatar">
                            <div>
                                <div><?= htmlspecialchars($pub['username']) ?></div>
                                <small><?= date('d/m/Y', strtotime($pub['created_at'])) ?></small>
                            </div>
                        </div>
                        
                        <div>
                            <span class="problem-status-indicator <?= $pub['is_solved']?'problem-status-solved':'problem-status-unsolved' ?>"></span>
                            <h3 class="publication-title"><?= htmlspecialchars($pub['title']) ?></h3>
                        </div>
                        
                        <div class="publication-description">
                            <?= nl2br(htmlspecialchars(substr($pub['description'],0,100).(strlen($pub['description'])>100?'...':''))) ?>
                        </div>
                        
                        <div class="tags-container">
                            <span class="tag difficulty-<?= $pub['difficulty'] ?>">
                                <?= ucfirst($pub['difficulty']) ?>
                            </span>
                        </div>
                        
                        <div class="publication-footer">
                            <div>
                                <a href="problem.php?id=<?= $pub['problem_id'] ?>" class="btn btn-primary">
                                    <i class="fas fa-eye"></i> Voir
                                </a>
                            </div>
                            <div>
                                <a href="submit_solution.php?problem_id=<?= $pub['problem_id'] ?>" class="btn btn-success">
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
    document.addEventListener('DOMContentLoaded',()=>{
        document.querySelectorAll('.favorite-btn').forEach(btn=>{
            btn.addEventListener('click',function(e){
                e.preventDefault();
                const icon = this.querySelector('i');
                this.classList.toggle('active');
                
                fetch('api/toggle_favorite.php',{
                    method:'POST',
                    headers:{'Content-Type':'application/x-www-form-urlencoded'},
                    body:`problem_id=${this.dataset.problemId}&set_favorite=${this.classList.contains('active')?1:0}`
                }).catch(e=>{
                    this.classList.toggle('active');
                    alert('Erreur réseau');
                });
            });
        });
    });
    </script>
</body>
</html>