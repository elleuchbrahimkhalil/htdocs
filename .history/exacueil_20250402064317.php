<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

$conn = connect();
if ($conn === null) die("Database connection failed.");
if (!isLoggedIn()) header('Location: login.php'); exit;

// Messages
$success = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);
$error = $_SESSION['error_message'] ?? null;
unset($_SESSION['error_message']);

// Récupérer les publications
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
    error_log("Erreur publications: ".$e->getMessage());
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
        body{font-family:'Segoe UI',sans-serif;margin:0;padding:0;background:#f5f7fa}
        .main-container{display:flex;min-height:100vh}
        .sidebar{width:70px;background:#2c3e50;color:#ecf0f1;position:sticky;top:0;height:100vh;overflow-y:auto}
        .sidebar ul{list-style:none;padding:0;margin:20px 0}
        .sidebar li{margin:15px 0;text-align:center}
        .sidebar a{color:#bdc3c7;display:block;padding:10px;transition:all 0.3s}
        .sidebar a:hover{color:#fff;background:#34495e}
        .content{flex:1;padding:15px}
        .pub-grid{display:flex;flex-direction:column;gap:15px}
        .pub-card{border:1px solid #e0e6ed;border-radius:8px;padding:15px;background:#fff;box-shadow:0 2px 10px rgba(0,0,0,0.05);position:relative}
        .pub-header{display:flex;align-items:center;margin-bottom:10px}
        .avatar{width:40px;height:40px;border-radius:50%;margin-right:10px;border:2px solid #f0f4f8}
        .username{font-weight:600;color:#2c3e50}
        .pub-date{color:#7f8c8d;font-size:0.8em}
        .pub-title{font-weight:600;margin:10px 0;color:#2c3e50}
        .pub-desc{color:#4a5568;line-height:1.5;margin-bottom:15px;font-size:0.9em}
        .tags{display:flex;flex-wrap:wrap;gap:5px;margin:10px 0}
        .tag{padding:3px 8px;border-radius:15px;font-size:0.8em}
        .easy{background:#f0fff4;color:#38a169}
        .medium{background:#fffaf0;color:#dd6b20}
        .hard{background:#fff5f5;color:#e53e3e}
        .pub-footer{display:flex;justify-content:space-between;margin-top:10px}
        .btn{padding:8px 15px;border-radius:4px;text-decoration:none;font-size:0.9em}
        .btn-primary{background:#4299e1;color:#fff}
        .btn-success{background:#48bb78;color:#fff}
        .favorite-btn{position:absolute;top:10px;right:10px;background:none;border:none;color:#cbd5e0;font-size:1.2em}
        .favorite-btn.active{color:#EC4899}
        .status{width:10px;height:10px;border-radius:50%;margin-right:5px}
        .solved{background:#48bb78}
        .unsolved{background:#f56565}
        @media (max-width:768px){
            .main-container{flex-direction:column}
            .sidebar{width:100%;height:auto}
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="sidebar">
            <ul>
                <li><a href="expublier.php" title="Publier"><i class="fas fa-plus"></i></a></li>
                <li><a href="favorites.php" title="Favoris"><i class="fas fa-heart"></i></a></li>
                <li><a href="https://www.leetcode.com/" target="_blank" title="LeetCode"><i class="fas fa-code"></i></a></li>
            </ul>
        </div>
        
        <div class="content">
            <?php if($success): ?>
                <div style="background:#f0fff4;color:#2f855a;padding:10px;margin-bottom:15px;border-radius:4px">
                    <i class="fas fa-check-circle"></i> <?=htmlspecialchars($success)?>
                </div>
            <?php endif; ?>
            
            <?php if($error): ?>
                <div style="background:#fff5f5;color:#c53030;padding:10px;margin-bottom:15px;border-radius:4px">
                    <i class="fas fa-exclamation-circle"></i> <?=htmlspecialchars($error)?>
                </div>
            <?php endif; ?>
            
            <h2>Problèmes récents</h2>
            
            <div class="pub-grid">
                <?php foreach($publications as $p): ?>
                    <div class="pub-card">
                        <button class="favorite-btn <?=$p['is_favorite']?'active':''?>" 
                                data-problem-id="<?=$p['problem_id']?>">
                            <i class="fas fa-heart"></i>
                        </button>
                        
                        <div class="pub-header">
                            <img src="<?=htmlspecialchars($p['avatar_url']?:'default.png')?>" class="avatar">
                            <div>
                                <div class="username"><?=htmlspecialchars($p['username'])?></div>
                                <div class="pub-date"><?=date('d/m H:i',strtotime($p['created_at']))?></div>
                            </div>
                        </div>
                        
                        <div style="display:flex;align-items:center">
                            <span class="status <?=$p['is_solved']?'solved':'unsolved'?>"></span>
                            <h3 class="pub-title"><?=htmlspecialchars($p['title'])?></h3>
                        </div>
                        
                        <div class="pub-desc"><?=nl2br(htmlspecialchars($p['description']))?></div>
                        
                        <div class="tags">
                            <span class="tag <?=$p['difficulty']?>"><?=ucfirst($p['difficulty'])?></span>
                            <?php if(!empty($p['tags'])): ?>
                                <?php foreach(explode(',',$p['tags']) as $t): ?>
                                    <span class="tag"><?=htmlspecialchars(trim($t))?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="pub-footer">
                            <div>
                                <span><i class="fas fa-comment"></i> <?=$p['comment_count']??0?></span>
                                <span style="margin-left:10px"><i class="fas fa-check"></i> <?=$p['solution_count']??0?></span>
                            </div>
                            <div>
                                <a href="problem.php?id=<?=$p['problem_id']?>" class="btn btn-primary">
                                    <i class="fas fa-eye"></i> Voir
                                </a>
                                <a href="submit_solution.php?problem_id=<?=$p['problem_id']?>" class="btn btn-success">
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
            btn.onclick=function(e){
                e.preventDefault();
                const pid=this.dataset.problemId;
                const active=this.classList.contains('active');
                
                this.classList.toggle('active');
                this.querySelector('i').style.transform='scale(1.2)';
                setTimeout(()=>this.querySelector('i').style.transform='',300);
                
                fetch('api/toggle_favorite.php',{
                    method:'POST',
                    body:new FormData().append('problem_id',pid)
                          .append('set_favorite',active?'0':'1')
                          .append('csrf_token','<?=$_SESSION['csrf_token']?>')
                }).catch(e=>{
                    this.classList.toggle('active');
                    alert('Erreur réseau');
                });
            };
        });
    });
    </script>
</body>
</html>