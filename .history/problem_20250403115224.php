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

    // Requête pour récupérer le problème - version débogage
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
    
    if ($user_id) {
        $stmt->bindValue(':problem_id', $problem_id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    } else {
        $stmt->bindValue(':problem_id', $problem_id, PDO::PARAM_INT);
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
        SELECT 
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
        .problem-container {
            max-width: 900px;
            margin: 40px auto;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .problem-header {
            padding: 30px;
            border-bottom: 1px solid #f0f0f0;
            position: relative;
        }

        .problem-title {
            margin: 0 0 15px 0;
            color: #2c3e50;
            font-size: 28px;
            padding-right: 40px; /* Espace pour le bouton favori */
        }

        .problem-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #7f8c8d;
        }

        .author-info {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 15px;
        }

        .author-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #f0f4f8;
        }

        .difficulty {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
        }

        .difficulty.easy { background: #d5f5e3; color: #27ae60; }
        .difficulty.medium { background: #fef9e7; color: #f39c12; }
        .difficulty.hard { background: #fdedec; color: #e74c3c; }

        .problem-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 15px;
        }

        .tag {
            background: #ecf0f1;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            color: #2c3e50;
        }

        .section {
            padding: 30px;
            border-bottom: 1px solid #f0f0f0;
        }

        .section-title {
            color: #2c3e50;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 22px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #3498db;
        }

        .code-block {
            background: #282c34;
            color: #abb2bf;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            font-family: 'Courier New', Courier, monospace;
            line-height: 1.5;
            margin: 15px 0;
        }

        .solutions-section {
            padding: 30px;
        }

        .solution-card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .solution-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .solution-author {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .solution-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            object-fit: cover;
        }

        .solution-date {
            color: #7f8c8d;
            font-size: 12px;
        }

        .solution-content {
            margin-top: 15px;
        }

        .solution-explanation {
            background: #f0f7fb;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #3498db;
        }

        .actions {
            padding: 30px;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 15px;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #3498db;
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .btn-success {
            background: #2ecc71;
            color: white;
            border: none;
        }

        .btn-success:hover {
            background: #27ae60;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .btn-outline {
            background: transparent;
            color: #3498db;
            border: 2px solid #3498db;
        }

        .btn-outline:hover {
            background: #ebf5fb;
        }

        .solution-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
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

        .solutions-count {
            margin-top: 15px;
            font-size: 14px;
            color: #7f8c8d;
            display: flex;
            gap: 15px;
        }

        .favorite-btn {
            position: absolute;
            top: 30px;
            right: 30px;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            transition: all 0.3s;
            color: #ddd;
        }

        .favorite-btn.active {
            color: #f1c40f;
        }

        .favorite-btn:hover {
            transform: scale(1.2);
        }

        .problem-stats {
            display: flex;
            gap: 20px;
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .stat-value {
            font-size: 24px;
            font-weight: bold;
            color: #3498db;
        }

        .stat-label {
            font-size: 12px;
            color: #7f8c8d;
        }

        .copy-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(52, 152, 219, 0.1);
            color: #3498db;
            border: none;
            border-radius: 4px;
            padding: 5px 10px;
            font-size: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .copy-btn:hover {
            background: rgba(52, 152, 219, 0.2);
        }

        .code-container {
            position: relative;
        }

        /* Responsive design */
        @media (max-width: 768px) {
            .problem-container {
                margin: 20px 10px;
                border-radius: 8px;
            }
            
            .problem-header, .section, .solutions-section, .actions {
                padding: 15px;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            
            .problem-meta {
                flex-direction: column;
                gap: 8px;
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
                    <div class="solution-date">Publié le <?= date('d/m/Y à H:i', strtotime($problem['created_at'])) ?></div>
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
                <span><i class="fas fa-award"></i> <?= htmlspecialchars($problem['points']) ?> points</span>
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
                    <div class="stat-value"><?= $stats['total_solutions'] ?? 0 ?></div>
                    <div class="stat-label">Solutions soumises</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= $stats['approved_solutions'] ?? 0 ?></div>
                    <div class="stat-label">Solutions approuvées</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= $problem['views'] ?? 0 ?></div>
                    <div class="stat-label">Vues</div>
                </div>
            </div>
        </div>
        
        <div class="section">
            <h2 class="section-title"><i class="fas fa-info-circle"></i> Description</h2>
            <div><?= nl2br(htmlspecialchars($problem['description'])) ?></div>
        </div>
        
        <?php if (!empty($problem['code'])): ?>
        <div class="section">
            <h2 class="section-title"><i class="fas fa-code"></i> Code du problème</h2>
            <div class="code-container">
                <pre class="code-block"><code class="language-<?= strtolower($problem['language']) ?>"><?= htmlspecialchars($problem['code']) ?></code></pre>
                <button class="copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copier</button>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="section">
            <h2 class="section-title"><i class="fas fa-lightbulb"></i> Solution attendue</h2>
            <div><?= nl2br(htmlspecialchars($problem['solution'])) ?></div>
        </div>
        
        <!-- Section des solutions approuvées -->
        <div class="solutions-section">
            <h2 class="section-title"><i class="fas fa-check-circle"></i> Solutions approuvées (<?= count($solutions) ?>)</h2>
            
            <?php if (count($solutions) > 0): ?>
                <?php foreach($solutions as $solution): ?>
                    <div class="solution-card">
                        <div class="solution-header">
                            <div class="solution-author">
                                <img src="<?= !empty($solution['avatar_url']) ? htmlspecialchars($solution['avatar_url']) : 'assets/default-avatar.png' ?>" 
                                     alt="Avatar" class="solution-avatar">
                                <div>
                                    <div><?= htmlspecialchars($solution['username']) ?></div>
                                    <div class="solution-date">Le <?= date('d/m/Y à H:i', strtotime($solution['created_at'])) ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <?php if(!empty($solution['explanation'])): ?>
                            <div class="solution-explanation">
                                <h4><i class="fas fa-comment-alt"></i> Explication</h4>
                                <?= nl2br(htmlspecialchars($solution['explanation'])) ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="solution-content code-container">
                            <pre class="code-block"><code class="language-<?= strtolower($problem['language']) ?>"><?= htmlspecialchars($solution['solution_code']) ?></code></pre>
                            <button class="copy-btn" onclick="copyCode(this)"><i class="fas fa-copy"></i> Copier</button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <p>Aucune solution approuvée pour le moment. Soyez le premier à proposer une solution!</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Section des actions -->
        <div class="actions">
            <div>
                <?php if (isLoggedIn()): ?>
                    <?php if ($problem['user_id'] == $_SESSION['user_id']): ?>
                        <!-- L'utilisateur est l'auteur du problème -->
                        <a href="user_feedback.php" class="btn btn-primary">
                            <i class="fas fa-check-double"></i> Évaluer les solutions
                        </a>
                        <a href="edit_problem.php?id=<?= $problem['problem_id'] ?>" class="btn btn-outline">
                            <i class="fas fa-edit"></i> Modifier le problème
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
                            <?php elseif ($user_solution['status'] === 'accepted' || $user_solution['status'] === 'approved'): ?>
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
                                <i class="fas fa-code"></i> Proposer une solution
                            </a>
                        <?php endif; ?>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- L'utilisateur n'est pas connecté -->
                    <a href="exlogin.php" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt"></i> Connectez-vous pour proposer une solution
                    </a>
                <?php endif; ?>
            </div>
            
            <div>
                <a href="exacueil.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Retour à la liste des problèmes
                </a>
                
                <?php if (isLoggedIn() && $problem['is_favorite']): ?>
                    <button onclick="removeFavorite(<?= $problem['problem_id'] ?>)" class="btn btn-outline">
                        <i class="fas fa-star"></i> Retirer des favoris
                    </button>
                <?php elseif (isLoggedIn() && !$problem['is_favorite']): ?>
                    <button onclick="addFavorite(<?= $problem['problem_id'] ?>)" class="btn btn-outline">
                        <i class="far fa-star"></i> Ajouter aux favoris
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.7.0/highlight.min.js"></script>
    <script>
        // Initialiser la coloration syntaxique
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('pre code').forEach((block) => {
                hljs.highlightElement(block);
            });
            
            // Mettre à jour le compteur de vues
            updateViewCount();
        });
        
        // Fonction pour copier le code
        function copyCode(button) {
            const codeBlock = button.previousElementSibling;
            const code = codeBlock.textContent;
            
            navigator.clipboard.writeText(code).then(() => {
                // Changer temporairement le texte du bouton
                const originalText = button.innerHTML;
                button.innerHTML = '<i class="fas fa-check"></i> Copié!';
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                }, 2000);
            });
        }
        
        // Gestion des favoris
        function toggleFavorite(problemId) {
            const favoriteBtn = document.querySelector('.favorite-btn');
            const isActive = favoriteBtn.classList.contains('active');
            
            favoriteBtn.classList.toggle('active');
            
            fetch('toggle_favorite.php?problem_id=' + problemId + '&redirect=0', {
                method: 'GET'
            })
            .catch(() => {
                // En cas d'erreur, rétablir l'état précédent
                favoriteBtn.classList.toggle('active');
            });
        }
        
        // Ajouter aux favoris
        function addFavorite(problemId) {
            fetch('toggle_favorite.php?problem_id=' + problemId + '&redirect=0', {
                method: 'GET'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Rafraîchir la page pour mettre à jour l'interface
                    location.reload();
                }
            });
        }
        
        // Retirer des favoris
        function removeFavorite(problemId) {
            fetch('toggle_favorite.php?problem_id=' + problemId + '&redirect=0', {
                method: 'GET'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Rafraîchir la page pour mettre à jour l'interface
                    location.reload();
                }
            });
        }
        
        // Mettre à jour le compteur de vues
        function updateViewCount() {
            fetch('update_view.php?problem_id=<?= $problem_id ?>', {
                method: 'GET'
            });
        }
        
        // Gestion du bouton favori
        const favoriteBtn = document.querySelector('.favorite-btn');
        if (favoriteBtn) {
            favoriteBtn.addEventListener('click', function() {
                toggleFavorite(this.dataset.pid);
            });
        }
    </script>
</body>
</html>
