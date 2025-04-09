<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

$conn = connect();
if ($conn === null) die("DB connection failed");
if (!isLoggedIn()) header('Location: login.php');

$success = $_SESSION['success_message'] ?? '';
unset($_SESSION['success_message']);
$error = $_SESSION['error_message'] ?? null;
unset($_SESSION['error_message']);

try {
    $stmt = $conn->prepare("
        SELECT p.*, u.username, u.avatar_url,
               (SELECT COUNT(*) FROM favorites WHERE favorites.problem_id = p.problem_id AND favorites.user_id = ?) AS is_favorite,
               (SELECT COUNT(*) FROM solutions WHERE solutions.problem_id = p.problem_id AND solutions.user_id = ? AND (solutions.status = 'approved' OR solutions.status = 'accepted')) AS is_solved_by_me
        FROM problems p 
        JOIN users u ON p.user_id = u.id 
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $pubs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("DB error: ".$e->getMessage());
    $pubs = [];
}
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Accueil</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
body{font-family:'Segoe UI',sans-serif;margin:0;padding:0;min-height:100vh;background:#f5f7fa}
.main-container{display:flex;min-height:100vh}
.sidebar{width:200px;background:#2c3e50;color:#ecf0f1;padding:15px;position:sticky;top:0;height:100vh;overflow-y:auto}
.sidebar h2{font-size:1.2em;margin:20px 0 10px;padding-bottom:8px;border-bottom:1px solid #34495e}
.sidebar ul{padding:0;margin:0 0 20px;list-style:none}
.sidebar li{margin:8px 0}
.sidebar a{color:#bdc3c7;text-decoration:none;display:flex;align-items:center;padding:8px 10px;border-radius:4px;transition:all .3s}
.sidebar a:hover{background:#34495e;color:#fff;transform:translateX(3px)}
.sidebar i{margin-right:8px;width:18px;text-align:center}
.content{flex:1;padding:20px;background:#fff;overflow-x:auto}
.pubs-container{display:flex;gap:15px;padding-bottom:20px;overflow-x:auto}
.pub-card{min-width:300px;border:1px solid #e0e6ed;border-radius:8px;padding:15px;background:#fff;box-shadow:0 2px 10px rgba(0,0,0,.05);position:relative}
.pub-header{display:flex;align-items:center;margin-bottom:10px}
.avatar{width:40px;height:40px;border-radius:50%;margin-right:10px;object-fit:cover;border:2px solid #f0f4f8}
.pub-title{font-weight:600;font-size:1.2em;margin:10px 0;display:flex;align-items:center}
.pub-desc{color:#4a5568;line-height:1.5;margin-bottom:15px;font-size:.9em}
.pub-code{background:#f8fafc;padding:10px;border-radius:6px;font-family:'Courier New',monospace;font-size:.8em;max-height:150px;overflow:auto;margin-bottom:10px}
.tags{display:flex;flex-wrap:wrap;gap:5px;margin:10px 0}
.tag{background:#edf2f7;color:#4a5568;padding:3px 8px;border-radius:15px;font-size:.7em}
.difficulty-easy{background:#f0fff4;color:#38a169}
.difficulty-medium{background:#fffaf0;color:#dd6b20}
.difficulty-hard{background:#fff5f5;color:#e53e3e}
.pub-footer{display:flex;justify-content:space-between;margin-top:10px;font-size:.8em}
.fav-btn{position:absolute;top:10px;right:10px;background:none;border:none;color:#ccc;cursor:pointer;font-size:1.2em}
.fav-btn.active{color:#FFD700}
.status{display:inline-block;width:10px;height:10px;border-radius:50%;margin-right:5px}
.solved{background:#48bb78}
.unsolved{background:#f56565}
.alert{padding:10px 15px;margin-bottom:15px;border-radius:6px;display:flex;align-items:center}
.alert-success{background:#f0fff4;color:#2f855a}
.alert-danger{background:#fff5f5;color:#c53030}
.btn{padding:8px 15px;border-radius:4px;text-decoration:none;font-size:.8em;display:inline-flex;align-items:center;gap:5px}
.btn-primary{background:#4299e1;color:#fff}
@media (max-width:768px){
.main-container{flex-direction:column}
.sidebar{width:100%;height:auto;position:relative}
.pubs-container{flex-direction:column}
.pub-card{min-width:auto}
}
</style>
</head>
<body>
<div class="main-container">
<div class="sidebar">
<h2>Formation</h2>
<ul>
<li><a href="https://www.hackerrank.com/" target="_blank"><i class="fas fa-laptop-code"></i> HackerRank</a></li>
<li><a href="https://www.codewars.com/" target="_blank"><i class="fas fa-code"></i> Codewars</a></li>
<li><a href="https://www.leetcode.com/" target="_blank"><i class="fas fa-file-code"></i> LeetCode</a></li>
</ul>
<h2>Compétitions</h2>
<ul>
<li><a href="https://icpc.global/" target="_blank"><i class="fas fa-globe"></i> ICPC</a></li>
<li><a href="https://www.kaggle.com/" target="_blank"><i class="fas fa-chart-line"></i> Kaggle</a></li>
</ul>
<h2>Actions</h2>
<ul>
<li><a href="expublier.php"><i class="fas fa-plus-circle"></i> Publier</a></li>
<li><a href="favorites.php"><i class="fas fa-star"></i> Favoris</a></li>
</ul>
</div>
<div class="content">
<?php if($success):?><div class="alert alert-success"><i class="fas fa-check-circle"></i><?=htmlspecialchars($success)?></div><?php endif?>
<?php if($error):?><div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i><?=htmlspecialchars($error)?></div><?php endif?>
<h2>Problèmes récents</h2>
<div class="pubs-container">
<?php foreach($pubs as $p):?>
<div class="pub-card">
<button class="fav-btn <?=$p['is_favorite']?'active':''?>" data-id="<?=$p['problem_id']?>"><i class="fas fa-star"></i></button>
<div class="pub-header">
<img src="<?=htmlspecialchars($p['avatar_url']??'default.png')?>" class="avatar">
<div>
<div><?=htmlspecialchars($p['username'])?></div>
<small><?=date('d/m/Y H:i',strtotime($p['created_at']))?></small>
</div>
</div>
<div class="pub-title">
<span class="status <?=$p['is_solved_by_me']?'solved':'unsolved'?>"></span>
<?=htmlspecialchars($p['title'])?>
</div>
<div class="pub-desc"><?=nl2br(htmlspecialchars($p['description']))?></div>
<?php if(!empty($p['code'])):?><div class="pub-code"><pre><?=htmlspecialchars($p['code'])?></pre></div><?php endif?>
<div class="tags">
<span class="difficulty-<?=$p['difficulty']?> tag"><?=ucfirst($p['difficulty'])?></span>
<?php if(!empty($p['tags'])):foreach(explode(',',$p['tags']) as $t):?><span class="tag"><?=htmlspecialchars(trim($t))?></span><?php endforeach;endif?>
</div>
<div class="pub-footer">
<div>
<span><i class="fas fa-comments"></i> <?=$p['comment_count']??0?></span>
<span><i class="fas fa-check-circle"></i> <?=$p['solution_count']??0?></span>
</div>
<a href="problem.php?id=<?=$p['problem_id']?>" class="btn btn-primary"><i class="fas fa-eye"></i> Voir</a>
</div>
</div>
<?php endforeach?>
</div>
</div>
</div>
<script>
document.addEventListener('DOMContentLoaded',()=>{
document.querySelectorAll('.fav-btn').forEach(btn=>{
btn.addEventListener('click',e=>{
e.preventDefault();
const id=btn.dataset.id,isActive=btn.classList.contains('active');
btn.classList.toggle('active');
fetch('api/toggle_favorite.php',{
method:'POST',
headers:{'Content-Type':'application/x-www-form-urlencoded'},
body:`problem_id=${id}&set_favorite=${isActive?0:1}&csrf_token=<?=$_SESSION['csrf_token']?>`
}).then(r=>r.json()).then(d=>{
if(!d.success) btn.classList.toggle('active');
});
});
});
});
</script>
</body>
</html>