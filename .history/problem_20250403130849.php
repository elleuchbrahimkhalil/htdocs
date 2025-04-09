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
        $stmt = $conn->prepare("
            SELECT p.*, u.username, u.avatar_url, u.name as author_name,
                   CASE WHEN EXISTS(SELECT 1 FROM favorites WHERE problem_id = p.problem_id AND user_id = ?) THEN 1 ELSE 0 END AS is_favorite,
                   CASE WHEN EXISTS(SELECT 1 FROM solutions WHERE problem_id = p.problem_id AND user_id = ? AND status IN ('approved','accepted')) THEN 1 ELSE 0 END AS is_solved
            FROM problems p 
            JOIN users u ON p.user_id = u.id
            WHERE p.problem_id = ?
        ");
        $stmt->execute([$user_id, $user_id, $problem_id]);
    } else {
        $stmt = $conn->prepare("
            SELECT p.*, u.username, u.avatar_url, u.name as author_name,
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
        SELECT s.*, u.username, u.avatar_url
        FROM solutions s
        JOIN users u ON s.user_id = u.id
        WHERE s.problem_id = ? AND s.status = 'approved'
        ORDER BY s.created_at DESC
    ");
    $solutions_stmt->execute([$problem_id]);
    $solutions = $solutions_stmt->fetchAll();

    // Récupérer les statistiques du problème
    $stats_stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_solutions,
            SUM(CASE WHEN status IN ('approved','accepted') THEN 1 ELSE 0 END) as approved_solutions
        FROM solutions
        WHERE problem_id = ?
    ");
    $stats_stmt->execute([$problem_id]);
    $stats = $stats_stmt->fetch();

    // Récupérer la solution de l'utilisateur si connecté
    $user_solution = null;
    if (isLoggedIn()) {
        $user_solution_stmt = $conn->prepare("
            SELECT TOP 1 id, status, created_at, evaluated_at
            FROM solutions
            WHERE problem_id = ? AND user_id = ?
            ORDER BY created_at DESC
        ");
        $user_solution_stmt->execute([$problem_id, $user_id]);
        $user_solution = $user_solution_stmt->fetch();
    }

} catch (Exception $e) {
    $_SESSION['error_message'] = "Erreur: " . $e->getMessage();
    header('Location: exacueil.php');
    exit;
}

// Définir le titre de la page
$page_title = htmlspecialchars($problem['title']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/styles/atom-one-dark.min.css">
    <link rel="stylesheet" href="common.css">
    <style>
        /* Styles spécifiques à la page problème */
        body {
            font-family: 'Segoe UI', sans-serif;
            margin: 0;
            background: #f5f7fa;
            color: #333;
        }
        .problem-container {
            max-width: 900px;
            margin: 20px auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .problem-header {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        .problem-title {
            margin: 0;
            font-size: 24px;
            color: #2c3e50;
        }
        .author-info {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }
        .author-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .problem-meta {
            display: flex;
            gap: 15px;
            margin: 15px 0;
            font-size: 14px;
            color: #7f8c8d;
        }
        .difficulty {
            padding: 3px 10px;
            border-radius: 20px;
            font-weight: 600;
        }
        .easy { background: #d5f5e3; color: #27ae60; }
        .medium { background: #fef9e7; color: #f39c12; }
        .hard { background: #fdedec; color: #e74c3c; }
        .section {
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        .code-block {
            background: #282c34;
            color: #abb2bf;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            position: relative;
        }
        .copy-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: rgba(255,255,255,0.1);
            border: none;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
            cursor: pointer;
        }
        .solution-card {
            background: #f8f9fa;
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .actions {
            padding: 20px;
            display: flex;
            justify-content: space-between;
        }
        .btn {
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-success {
            background: #2ecc71;
            color: white;
        }
        @media (max-width: 768px) {
            .problem-container {
                margin: 10px;
                border-radius: 0;
            }
        }
    </style>
</head>
<body>
    <?php include 'exmenu.php'; ?>
    
    <div class="problem-container">
        <div class="problem-header">
            <h1 class="problem-title"><?= htmlspecialchars($problem['title']) ?></h1>
            
            <div class="author-info">
                <img src="<?= htmlspecialchars($problem['avatar_url'] ?? 'default.png') ?>" class="author-avatar" alt="Avatar">
                <div>
                    <div><?= htmlspecialchars($problem['author_name'] ?? $problem['username']) ?></div>
                    <div>Publié le <?= date('d/m/Y', strtotime($problem['created_at'])) ?></div>
                </div>
            </div>
            
            <div class="problem-meta">
                <span><i class="fas fa-code"></i> <?= htmlspecialchars($problem['language']) ?></span>
                <span class="difficulty <?= htmlspecialchars($problem['difficulty']) ?>">
                    <?= ucfirst($problem['difficulty']) ?>
                </span>
                <?php if ($problem['is_solved']): ?>
                    <span><i class="fas fa-check-circle"></i> Résolu</span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="section">
            <h2><i class="fas fa-info-circle"></i> Description</h2>
            <p><?= nl2br(htmlspecialchars($problem['description'])) ?></p>
        </div>
        
        <?php if (!empty($problem['code'])): ?>
        <div class="section">
            <h2><i class="fas fa-code"></i> Code</h2>
            <div class="code-block">
                <pre><code><?= htmlspecialchars($problem['code']) ?></code></pre>
                <button class="copy-btn"><i class="fas fa-copy"></i></button>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="section">
            <h2><i class="fas fa-check-circle"></i> Solutions (<?= count($solutions) ?>)</h2>
            
            <?php if (count($solutions) > 0): ?>
                <?php foreach ($solutions as $solution): ?>
                    <div class="solution-card">
                        <div>
                            <img src="<?= htmlspecialchars($solution['avatar_url'] ?? 'default.png') ?>" width="30" height="30" style="border-radius:50%">
                            <?= htmlspecialchars($solution['username']) ?>
                        </div>
                        <?php if (!empty($solution['explanation'])): ?>
                            <p><?= nl2br(htmlspecialchars($solution['explanation'])) ?></p>
                        <?php endif; ?>
                        <div class="code-block">
                            <pre><code><?= htmlspecialchars($solution['solution_code']) ?></code></pre>
                            <button class="copy-btn"><i class="fas fa-copy"></i></button>
                        </div>
                        <p>Évalué le <?= date('d/m/Y à H:i', strtotime($solution['evaluated_at'])) ?></p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Aucune solution approuvée.</p>
            <?php endif; ?>
        </div>
        
        <div class="actions">
            <?php if (isLoggedIn()): ?>
                <a href="submit_solution.php?problem_id=<?= $problem_id ?>" class="btn btn-success">
                    <i class="fas fa-paper-plane"></i> Proposer une solution
                </a>
            <?php else: ?>
                <a href="exlogin.php" class="btn btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Se connecter pour proposer une solution
                </a>
            <?php endif; ?>
            
            <a href="exacueil.php" class="btn">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
    <script>
        // Coloration syntaxique
        document.addEventListener('DOMContentLoaded', () => {
            hljs.highlightAll();
            
            // Boutons de copie
            document.querySelectorAll('.copy-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const code = this.parentNode.querySelector('code').textContent;
                    navigator.clipboard.writeText(code);
                    
                    const originalHTML = this.innerHTML;
                    this.innerHTML = '<i class="fas fa-check"></i>';
                    
                    setTimeout(() => {
                        this.innerHTML = originalHTML;
                    }, 2000);
                });
            });
        });
    </script>
</body>
</html>
