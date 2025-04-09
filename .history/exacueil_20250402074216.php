<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

$conn = connect();
if (!$conn) die("Erreur connexion DB");
if (!isLoggedIn()) header('Location: login.php');

// Gestion messages
$success = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);
$error = $_SESSION['error_message'] ?? '';
unset($_SESSION['error_message']);

// Récupération données
try {
    $stmt = $conn->prepare("SELECT p.*, u.username, u.avatar_url,
        EXISTS(SELECT 1 FROM favorites WHERE problem_id = p.problem_id AND user_id = ?) AS is_favorite,
        EXISTS(SELECT 1 FROM solutions WHERE problem_id = p.problem_id AND user_id = ? AND status IN ('approved','accepted')) AS is_solved
        FROM problems p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $pubs = $stmt->fetchAll();
} catch (Exception $e) {
    error_log("DB Error: ".$e->getMessage());
    $pubs = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Problèmes</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body{font-family:'Segoe UI',sans-serif;margin:0;background:#f5f7fa}
        .main-container{display:flex;min-height:100vh}
        .sidebar{width:180px;background:#2c3e50;color:#ecf0f1;padding:12px}
        .content{flex:1;padding:15px;background:#fff}
        .pub-grid{display:flex;flex-direction:column;gap:12px;margin-top:12px}
        .pub-card{border:1px solid #e0e6ed;border-radius:6px;padding:12px;background:#fff;position:relative}
        .pub-header{display:flex;align-items:center;margin-bottom:8px}
        .avatar{width:36px;height:36px;border-radius:50%;margin-right:8px;object-fit:cover}
        .username{font-weight:600;font-size:0.95em}
        .date{color:#7f8c8d;font-size:0.75em}
        .pub-title{font-weight:600;margin:8px 0;font-size:1.1em}
        .pub-desc{color:#4a5568;line-height:1.4;font-size:0.85em;margin-bottom:10px}
        .pub-code{background:#f8f9fa;padding:8px;border-radius:4px;font-family:monospace;font-size:0.8em;max-height:120px;overflow:auto;margin-bottom:8px}
        .tags{display:flex;flex-wrap:wrap;gap:4px;margin:8px 0}
        .tag{padding:2px 6px;border-radius:12px;font-size:0.7em;background:#edf2f7}
        .diff-easy{background:#f0fff4;color:#38a169}
        .diff-medium{background:#fffaf0;color:#dd6b20}
        .diff-hard{background:#fff5f5;color:#e53e3e}
        .pub-footer{display:flex;justify-content:space-between;margin-top:8px;padding-top:8px;border-top:1px solid #f0f4f8}
        .stats{display:flex;gap:8px;color:#718096;font-size:0.75em}
        .btn{padding:6px 10px;border-radius:3px;font-size:0.8em;text-decoration:none}
        .btn-primary{background:#4299e1;color:#fff}
        .btn-success{background:#48bb78;color:#fff}
        .fav-btn{position:absolute;top:8px;right:8px;background:none;border:none;color:#cbd5e0;cursor:pointer}
        .fav-btn.active{color:#EC4899}
        .status{width:8px;height:8px;border-radius:50%;margin-right:6px;display:inline-block}
        .solved{background:#48bb78}
        .unsolved{background:#f56565}
        .alert{padding:8px 10px;margin-bottom:10px;border-radius:4px;font-size:0.85em}
        .alert-success{background:#f0fff4;color:#2f855a}
        .alert-danger{background:#fff5f5;color:#c53030}
        .actions{display:flex;gap:6px}
        @media (max-width:768px){
            .main-container{flex-direction:column}
            .sidebar{width:100%}
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
            
            <div class="pub-grid">
                <?php foreach($pubs as $p): ?>
                <div class="pub-card">
                    <button class="fav-btn <?= $p['is_favorite']?'active':'' ?>" 
                            data-pid="<?= $p['problem_id'] ?>">
                        <i class="fas fa-heart"></i>
                    </button>
                    
                    <div class="pub-header">
                        <img src="<?= htmlspecialchars($p['avatar_url']??'default.png') ?>" 
                             class="avatar" alt="">
                        <div>
                            <div class="username"><?= htmlspecialchars($p['username']) ?></div>
                            <div class="date"><?= date('d/m H:i', strtotime($p['created_at'])) ?></div>
                        </div>
                    </div>
                    
                    <div>
                        <span class="status <?= $p['is_solved']?'solved':'unsolved' ?>"></span>
                        <h3 class="pub-title"><?= htmlspecialchars($p['title']) ?></h3>
                    </div>
                    
                    <div class="pub-desc">
                        <?= nl2br(htmlspecialchars(mb_strimwidth($p['description'], 0, 150, '...'))) ?>
                    </div>
                    
                    <?php if(!empty($p['code'])): ?>
                        <div class="pub-code">
                            <pre><?= htmlspecialchars(mb_strimwidth($p['code'], 0, 100, '...')) ?></pre>
                        </div>
                    <?php endif; ?>
                    
                    <div class="tags">
                        <span class="tag diff-<?= $p['difficulty'] ?>">
                            <?= ucfirst($p['difficulty']) ?>
                        </span>
                        <?php if(!empty($p['tags'])): ?>
                            <?php foreach(explode(',', $p['tags']) as $t): ?>
                                <span class="tag"><?= htmlspecialchars(trim($t)) ?></span>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <div class="pub-footer">
                        <div class="stats">
                            <span><i class="fas fa-comment"></i> <?= $p['comment_count']??0 ?></span>
                            <span><i class="fas fa-check"></i> <?= $p['solution_count']??0 ?></span>
                        </div>
                        
                        <div class="actions">
                            <a href="problem.php?id=<?= $p['problem_id'] ?>" 
                               class="btn btn-primary">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="submit.php?id=<?= $p['problem_id'] ?>" 
                               class="btn btn-success">
                                <i class="fas fa-paper-plane"></i>
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
        // Gestion favoris
        document.querySelectorAll('.fav-btn').forEach(b => {
            b.addEventListener('click', function() {
                const pid = this.dataset.pid;
                const active = this.classList.contains('active');
                
                this.classList.toggle('active');
                fetch('api/fav.php', {
                    method: 'POST',
                    body: `pid=${pid}&fav=${active?0:1}&token=<?= $_SESSION['csrf_token'] ?>`,
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'}
                }).catch(e => this.classList.toggle('active'));
            });
        });
    });
    </script>
</body>
</html>