<?php
// Démarrer la session
session_start();

// Inclure les fichiers nécessaires
require_once 'verification.php';
require_once 'db_connect.php';
require_once 'exmenu.php';

// Vérifier si l'ID du problème est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: problems.php');
    exit;
}

$problem_id = $_GET['id'];

// Récupérer les détails du problème
try {
    $pdo = connect();
    
    if (!$pdo) {
        throw new Exception("Erreur de connexion à la base de données");
    }
    
    $stmt = $pdo->prepare("
        SELECT p.*, u.username, u.name as author_name
        FROM problems p
        JOIN users u ON p.user_id = u.id
        WHERE p.id = ?
    ");
    
    $stmt->execute([$problem_id]);
    $problem = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$problem) {
        // Problème non trouvé
        header('Location: problems.php');
        exit;
    }
    
    // Si l'utilisateur est connecté, vérifier s'il a déjà soumis une solution
    $user_solution = null;
    if (isLoggedIn()) {
        $stmt = $pdo->prepare("
            SELECT id, status
            FROM solutions
            WHERE problem_id = ? AND user_id = ?
            ORDER BY created_at DESC
            LIMIT 1
        ");
        
        $stmt->execute([$problem_id, $_SESSION['user_id']]);
        $user_solution = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Récupérer le nombre de solutions soumises et approuvées
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_solutions,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_solutions
        FROM solutions
        WHERE problem_id = ?
    ");
    
    $stmt->execute([$problem_id]);
    $solutions_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Erreur lors de la récupération du problème: " . $e->getMessage());
    header('Location: problems.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($problem['title']); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #f5f7fa, #e4e8f0);
            color: #333;
            min-height: 100vh;
        }

        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .problem-container {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .problem-header {
            padding: 25px;
            border-bottom: 1px solid #f0f0f0;
        }

        .problem-title {
            margin: 0 0 15px 0;
            color: #2c3e50;
            font-size: 28px;
        }

        .problem-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 14px;
            color: #7f8c8d;
        }

        .difficulty {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 15px;
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
            gap: 5px;
            margin-top: 15px;
        }

        .tag {
            background: #ecf0f1;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 12px;
            color: #2c3e50;
        }

        .section {
            padding: 25px;
            border-bottom: 1px solid #f0f0f0;
        }

        .section-title {
            color: #2c3e50;
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 20px;
            font-weight: 600;
        }

        .code-block {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            font-family: 'Courier New', Courier, monospace;
            line-height: 1.5;
            border: 1px solid #e0e0e0;
        }

        .actions {
            padding: 25px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
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

        .solution-status.approved {
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
        }

        @media (max-width: 768px) {
            .container {
                margin: 20px;
            }
            
            .actions {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <?php 
    // Afficher l'avatar utilisateur
    require_once('exavatar.php');
    displayUserAvatar();
    ?>
    
    <div class="container">
        <div class="problem-container">
            <div class="problem-header">
                <h1 class="problem-title"><?php echo htmlspecialchars($problem['title']); ?></h1>
                
                <div class="problem-meta">
                    <span>Publié par <?php echo htmlspecialchars($problem['author_name']); ?></span>
                    <span>Le <?php echo date('d/m/Y', strtotime($problem['created_at'])); ?></span>
                    <span>Langage: <?php echo htmlspecialchars(ucfirst($problem['language'])); ?></span>
                    <span class="difficulty <?php echo htmlspecialchars($problem['difficulty']); ?>">
                        <?php 
                        $difficulty_text = '';
                        switch($problem['difficulty']) {
                            case 'easy': $difficulty_text = 'Facile'; break;
                            case 'medium': $difficulty_text = 'Moyen'; break;
                            case 'hard': $difficulty_text = 'Difficile'; break;
                            default: $difficulty_text = $problem['difficulty'];
                        }
                        echo htmlspecialchars($difficulty_text);
                        ?>
                    </span>
                    <span><?php echo htmlspecialchars($problem['points']); ?> points</span>
                    
                    <?php if (isLoggedIn()): ?>
                        <?php
                        // Vérifier si le problème est déjà en favori
                        $stmt_fav = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND problem_id = ?");
                        $stmt_fav->execute([$_SESSION['user_id'], $problem['id']]);
                        $is_favorite = $stmt_fav->fetch();
                        ?>
                        <a href="toggle_favorite.php?problem_id=<?php echo $problem['id']; ?>&redirect=problem.php?id=<?php echo $problem['id']; ?>" class="favorite-btn">
                            <i class="fas fa-star <?php echo $is_favorite ? 'favorite-active' : ''; ?>"></i>
                            <?php echo $is_favorite ? 'Retirer des favoris' : 'Ajouter aux favoris'; ?>
                        </a>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($problem['tags'])): ?>
                    <div class="problem-tags">
                        <?php 
                        $tags = explode(',', $problem['tags']);
                        foreach ($tags as $tag): 
                        ?>
                            <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="section">
                <h2 class="section-title">Description</h2>
                <div><?php echo nl2br(htmlspecialchars($problem['description'])); ?></div>
            </div>
            
            <div class="section">
                <h2 class="section-title">Code du problème</h2>
                <pre class="code-block"><?php echo htmlspecialchars($problem['code']); ?></pre>
            </div>
            
            <div class="section">
                <h2 class="section-title">Solution attendue</h2>
                <div><?php echo nl2br(htmlspecialchars($problem['solution'])); ?></div>
                
                <div class="solutions-count">
                    <p>
                        <i class="fas fa-users"></i> <?php echo $solutions_stats['total_solutions']; ?> solution(s) soumise(s)
                        <i class="fas fa-check-circle"></i> <?php echo $solutions_stats['approved_solutions']; ?> solution(s) approuvée(s)
                    </p>
                </div>
            </div>
            
            <div class="actions">
                <?php if (isLoggedIn()): ?>
                    <?php if ($problem['user_id'] == $_SESSION['user_id']): ?>
                        <!-- L'utilisateur est l'auteur du problème -->
                        <a href="review_solutions.php?id=<?php echo $problem_id; ?>" class="btn btn-primary">
                            <i class="fas fa-check-double"></i> Évaluer les solutions
                        </a>
                        <a href="edit_problem.php?id=<?php echo $problem_id; ?>" class="btn btn-outline">
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
                                <a href="solve_problem.php?id=<?php echo $problem_id; ?>" class="btn btn-outline">
                                    <i class="fas fa-edit"></i> Modifier ma solution
                                </a>
                            <?php elseif ($user_solution['status'] === 'approved'): ?>
                                <div class="solution-status approved">
                                    <i class="fas fa-check-circle"></i> Votre solution a été approuvée
                                </div>
                            <?php elseif ($user_solution['status'] === 'rejected'): ?>
                                <div class="solution-status rejected">
                                    <i class="fas fa-times-circle"></i> Votre solution a été rejetée
                                </div>
                                <a href="solve_problem.php?id=<?php echo $problem_id; ?>" class="btn btn-primary">
                                    <i class="fas fa-redo"></i> Soumettre une nouvelle solution
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <!-- L'utilisateur n'a pas encore soumis de solution -->
                            <a href="solve_problem.php?id=<?php echo $problem_id; ?>" class="btn btn-primary">
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
                
                <a href="problems.php" class="btn btn-outline">
                    <i class="fas fa-arrow-left"></i> Retour à la liste des problèmes
                </a>
            </div>
        </div>
    </div>

    <script>
        // Vous pouvez ajouter ici du code JavaScript pour améliorer l'expérience utilisateur
        // Par exemple, pour la mise en évidence de la syntaxe du code
    </script>
</body>
</html>
