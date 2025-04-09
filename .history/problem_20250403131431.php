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
            SELECT p.*, u.username, u.avatar_url, u.name as author_name,
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

    // Récupérer les statistiques du problème - Version compatible SQL Server
    // Récupérer le nombre total de solutions
    $total_stmt = $conn->prepare("
        SELECT COUNT(1) as total_solutions
        FROM solutions
        WHERE problem_id = ?
    ");
    $total_stmt->execute([$problem_id]);
    $total = $total_stmt->fetch();

    // Récupérer le nombre de solutions approuvées
    $approved_stmt = $conn->prepare("
        SELECT COUNT(1) as approved_solutions
        FROM solutions
        WHERE problem_id = ? AND (status = 'approved' OR status = 'accepted')
    ");
    $approved_stmt->execute([$problem_id]);
    $approved = $approved_stmt->fetch();

    // Combiner les résultats
    $stats = [
        'total_solutions' => $total['total_solutions'] ?? 0,
        'approved_solutions' => $approved['approved_solutions'] ?? 0
    ];

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

    // Incrémenter le compteur de vues
    $view_stmt = $conn->prepare("
        UPDATE problems 
        SET views = ISNULL(views, 0) + 1 
        WHERE problem_id = ?
    ");
    $view_stmt->execute([$problem_id]);

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
            position: relative;
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
            object-fit: cover;
        }
        .problem-meta {
            display: flex;
            flex-wrap: wrap;
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
            font-family: 'Courier New', monospace;
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
            border-left: 4px solid #3498db;
        }
        .solution-header {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .solution-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            margin-right: 10px;
        }
        .actions {
            padding: 20px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }
        .btn {
            padding: 10px 15px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }
        .btn-primary {
            background: #3498db;
            color: white;
            border: none;
        }
        .btn-success {
            background: #2ecc71;
            color: white;
            border: none;
        }
        .btn-outline {
            background: transparent;
            color: #3498db;
            border: 1px solid #3498db;
        }
        .solution-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 5px 10px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 12px;
        }
        .solution-status.pending {
            background: #fef9e7;
            color: #f39c12;
        }
        .solution-status.approved, .solution-status.accepted {
            background: #d5f5e3;
            color: #27ae60;
        }
        .solution-status.rejected {
            background: #fdedec;
            color: #e74c3c;
        }
        .favorite-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            font-size: 20px;
            cursor: pointer;
            color: #ddd;
        }
        .favorite-btn.active {
            color: #f1c40f;
        }
        .problem-stats {
            display: flex;
            gap: 15px;
            margin-top: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #3498db;
        }
        .stat-label {
            font-size: 12px;
            color: #7f8c8d;
        }
        @media (max-width: 768px) {
            .problem-container {
                margin: 10px;
                border-radius: 0;
            }
            .actions {
                flex-direction: column;
            }
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'exmenu.php'; ?>
    
    <div class="problem-container">
        <div class="problem-header">
            <h1 class="problem-title"><?= htmlspecialchars($problem['title']) ?></h1>
            
            <?php if (isLoggedIn()): ?>
            <button class="favorite-btn <?= $problem['is_favorite'] ? 'active' : '' ?>" 
                    data-pid="<?= $problem['problem_id'] ?>" title="<?= $problem['is_favorite'] ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>">
                <i class="fas fa-star"></i>
            </button>
            <?php endif; ?>
            
            <div class="author-info">
                <img src="<?= !empty($problem['avatar_url']) ? htmlspecialchars($problem['avatar_url']) : 'assets/default-avatar.png' ?>" 
                     alt="Avatar" class="author-avatar">
                <div>
                    <div><?= htmlspecialchars($problem['author_name'] ?? $problem['username']) ?></div>
                    <div>Publié le <?= date('d/m/Y', strtotime($problem['created_at'])) ?></div>
                </div>
            </div>
            
            <div class="problem-meta">
                <span><i class="fas fa-code"></i> <?= htmlspecialchars(ucfirst($problem['language'])) ?></span>
                <span class="difficulty <?= htmlspecialchars($problem['difficulty']) ?>">
                    <?php 
                    $difficulty_text = '';
                    switch($problem['difficulty']) {
                        case 'easy': $difficulty_text = 'Facile'; break;
                        case 'medium': $difficulty_text = 'Moyen'; break;
                        case 'hard': $difficulty_text = 'Difficile'; break;
                        default: $difficulty_text = ucfirst($problem['difficulty']);
                    }
                    echo $difficulty_text;
                    ?>
                </span>
                <span><i class="fas fa-award"></i> <?= htmlspecialchars($problem['points'] ?? 0) ?> points</span>
                <?php if ($problem['is_solved']): ?>
                    <span class="solution-status approved"><i class="fas fa-check-circle"></i> Résolu</span>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($problem['tags'])): ?>
                <div class="problem-tags">
                    <?php 
                    $tags = explode(',', $problem['tags']);
                    foreach ($tags as $tag): 
                        if (!empty(trim($tag))):
                    ?>
                        <span class="tag"><?= htmlspecialchars(trim($tag)) ?></span>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="problem-stats">
                <div class="stat-item">
                    <div class="stat-value"><?= $stats['total_solutions'] ?></div>
                    <div class="stat-label">Solutions soumises</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= $stats['approved_solutions'] ?></div>
                    <div class="stat-label">Solutions approuvées</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= $problem['views'] ?? 0 ?></div>
                    <div class="stat-label">Vues</div>
                </div>
            </div>
        </div>
        
        <div class="section">
            <h2><i class="fas fa-info-circle"></i> Description</h2>
            <p><?= nl2br(htmlspecialchars($problem['description'])) ?></p>
            </div>
        
        <?php if (!empty($problem['code'])): ?>
        <div class="section">
            <h2><i class="fas fa-code"></i> Code du problème</h2>
            <div style="position: relative;">
                <pre class="code-block"><code><?= htmlspecialchars($problem['code']) ?></code></pre>
                <button class="copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i></button>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($problem['solution'])): ?>
        <div class="section">
            <h2><i class="fas fa-lightbulb"></i> Solution attendue</h2>
            <p><?= nl2br(htmlspecialchars($problem['solution'])) ?></p>
        </div>
        <?php endif; ?>
        
        <div class="section">
            <h2><i class="fas fa-check-circle"></i> Solutions (<?= count($solutions) ?>)</h2>
            
            <?php if (count($solutions) > 0): ?>
                <?php foreach ($solutions as $solution): ?>
                    <div class="solution-card">
                        <div class="solution-header">
                            <img src="<?= !empty($solution['avatar_url']) ? htmlspecialchars($solution['avatar_url']) : 'assets/default-avatar.png' ?>" 
                                 alt="Avatar" class="solution-avatar">
                            <div>
                                <div><?= htmlspecialchars($solution['username']) ?></div>
                                <div style="font-size: 12px; color: #7f8c8d;">
                                    Le <?= date('d/m/Y à H:i', strtotime($solution['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                        
                        <?php if (!empty($solution['explanation'])): ?>
                            <div style="margin: 10px 0; padding: 10px; background: #f0f7fb; border-radius: 5px;">
                                <p><?= nl2br(htmlspecialchars($solution['explanation'])) ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div style="position: relative;">
                            <pre class="code-block"><code><?= htmlspecialchars($solution['solution_code']) ?></code></pre>
                            <button class="copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i></button>
                        </div>
                        
                        <?php if (!empty($solution['evaluated_at'])): ?>
                            <p style="font-size: 12px; color: #7f8c8d; margin-top: 10px;">
                                Évalué le <?= date('d/m/Y à H:i', strtotime($solution['evaluated_at'])) ?>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Aucune solution approuvée pour le moment.</p>
            <?php endif; ?>
        </div>
        
        <div class="actions">
            <div>
                <?php if (isLoggedIn()): ?>
                    <?php if ($problem['user_id'] == $_SESSION['user_id']): ?>
                        <!-- L'utilisateur est l'auteur du problème -->
                        <a href="user_feedback.php" class="btn btn-primary">
                            <i class="fas fa-check-double"></i> Évaluer les solutions
                        </a>
                    <?php else: ?>
                        <!-- L'utilisateur n'est pas l'auteur -->
                        <?php if ($user_solution): ?>
                            <!-- L'utilisateur a déjà soumis une solution -->
                            <?php if ($user_solution['status'] === 'pending'): ?>
                                <div class="solution-status pending">
                                    <i class="fas fa-clock"></i> Votre solution est en attente d'évaluation
                                </div>
                                <a href="submit_solution.php?problem_id=<?= $problem['problem_id'] ?>" class="btn btn-outline">
                                    <i class="fas fa-edit"></i> Modifier ma solution
                                </a>
                            <?php elseif ($user_solution['status'] === 'approved' || $user_solution['status'] === 'accepted'): ?>
                                <div class="solution-status approved">
                                    <i class="fas fa-check-circle"></i> Votre solution a été approuvée
                                </div>
                            <?php elseif ($user_solution['status'] === 'rejected'): ?>
                                <div class="solution-status rejected">
                                    <i class="fas fa-times-circle"></i> Votre solution a été rejetée
                                </div>
                                <a href="submit_solution.php?problem_id=<?= $problem['problem_id'] ?>" class="btn btn-primary">
                                    <i class="fas fa-redo"></i> Soumettre une nouvelle solution
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <!-- L'utilisateur n'a pas encore soumis de solution -->
                            <a href="submit_solution.php?problem_id=<?= $problem['problem_id'] ?>" class="btn btn-success">
                                <i class="fas fa-paper-plane"></i> Proposer une solution
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- L'utilisateur n'est pas connecté -->
                    <a href="exlogin.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Se connecter pour proposer une solution
                    </a>
                <?php endif; ?>
            </div>
            
            <a href="exacueil.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Retour
            </a>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
    <script>
        // Coloration syntaxique
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('pre code').forEach((block) => {
                hljs.highlightElement(block);
            });
        });
        
        // Fonction pour copier le code
        function copyCode(button) {
            const codeBlock = button.previousElementSibling;
            const code = codeBlock.textContent;
            
            navigator.clipboard.writeText(code).then(() => {
                // Changer temporairement le texte du bouton
                const originalHTML = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i>';
                
                setTimeout(() => {
                    button.innerHTML = originalHTML;
                }, 2000);
            });
        }
        
        // Gestion des favoris
        <?php if (isLoggedIn()): ?>
        document.querySelector('.favorite-btn').addEventListener('click', function() {
            const pid = this.dataset.pid;
            const active = this.classList.contains('active');
            
            this.classList.toggle('active');
            
            fetch('toggle_favorite.php?problem_id=' + pid + '&redirect=0', {
                method: 'GET'
            })
            .catch(e => {
                // En cas d'erreur, rétablir l'état précédent
                this.classList.toggle('active');
                console.error('Erreur lors de la mise à jour des favoris:', e);
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>

