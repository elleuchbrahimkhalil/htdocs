<?php
// Démarrer la session
session_start();

// Inclure les fichiers nécessaires
require_once 'verification.php';
require_once 'db_connect.php';
require_once 'exmenu.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: exlogin.php');
    exit;
}

// Vérifier si l'ID du problème est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: problems.php');
    exit;
}

$problem_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Récupérer les détails du problème et vérifier si l'utilisateur en est l'auteur
try {
    $pdo = connect();
    
    if (!$pdo) {
        throw new Exception("Erreur de connexion à la base de données");
    }
    
    $stmt = $pdo->prepare("
        SELECT *
        FROM problems
        WHERE id = ?
    ");
    
    $stmt->execute([$problem_id]);
    $problem = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$problem) {
        // Problème non trouvé
        header('Location: problems.php');
        exit;
    }
    
    // Vérifier si l'utilisateur est l'auteur du problème
    if ($problem['user_id'] != $user_id) {
        header('Location: problem.php?id=' . $problem_id);
        exit;
    }
    
    // Récupérer toutes les solutions soumises pour ce problème
    $stmt = $pdo->prepare("
        SELECT s.*, u.username, u.name as solver_name
        FROM solutions s
        JOIN users u ON s.user_id = u.id
        WHERE s.problem_id = ?
        ORDER BY s.created_at DESC
    ");
    
    $stmt->execute([$problem_id]);
    $solutions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des solutions: " . $e->getMessage());
    header('Location: problem.php?id=' . $problem_id);
    exit;
}

// Traiter l'évaluation d'une solution
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $solution_id = $_POST['solution_id'] ?? 0;
    $action = $_POST['action'] ?? '';
    $feedback = $_POST['feedback'] ?? '';
    
    if (!in_array($action, ['approve', 'reject']) || !is_numeric($solution_id)) {
        $message = "Action invalide.";
    } else {
        try {
            // Récupérer les informations de la solution
            $stmt = $pdo->prepare("
                SELECT s.*, p.points
                FROM solutions s
                JOIN problems p ON s.problem_id = p.id
                WHERE s.id = ? AND s.problem_id = ?
            ");
            
            $stmt->execute([$solution_id, $problem_id]);
            $solution = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$solution) {
                $message = "Solution introuvable.";
            } else if ($solution['status'] !== 'pending') {
                $message = "Cette solution a déjà été évaluée.";
            } else {
                // Mettre à jour le statut de la solution
                $status = ($action === 'approve') ? 'approved' : 'rejected';
                
                $stmt = $pdo->prepare("
                    UPDATE solutions
                    SET status = ?, feedback = ?, evaluated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                
                $result = $stmt->execute([$status, $feedback, $solution_id]);
                
                if ($result) {
                    // Si la solution est approuvée, mettre à jour les statistiques de l'utilisateur
                    if ($status === 'approved') {
                        // Mettre à jour le score et le nombre de problèmes résolus
                        $stmt = $pdo->prepare("
                            UPDATE users
                            SET score = score + ?, problems_solved = problems_solved + 1
                            WHERE id = ?
                        ");
                        
                        $stmt->execute([$solution['points'], $solution['user_id']]);
                    }
                    
                    $message = "La solution a été " . ($status === 'approved' ? "approuvée" : "rejetée") . " avec succès.";
                    
                    // Rafraîchir la liste des solutions
                    $stmt = $pdo->prepare("
                        SELECT s.*, u.username, u.name as solver_name
                        FROM solutions s
                        JOIN users u ON s.user_id = u.id
                        WHERE s.problem_id = ?
                        ORDER BY s.created_at DESC
                    ");
                    
                    $stmt->execute([$problem_id]);
                    $solutions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $message = "Une erreur est survenue lors de l'évaluation de la solution.";
                }
            }
        } catch (Exception $e) {
            error_log("Erreur lors de l'évaluation de la solution: " . $e->getMessage());
            $message = "Une erreur est survenue lors de l'évaluation de la solution.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Évaluer les Solutions</title>
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

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        h1 {
            color: #2c3e50;
            margin: 0;
        }

        .btn {
            padding: 10px 20px;
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

        .message {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
        }

        .problem-summary {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .problem-title {
            color: #2c3e50;
            margin-top: 0;
            margin-bottom: 15px;
            font-size: 24px;
        }

        .solutions-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .solution-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .solution-header {
            padding: 15px;
            background: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .solution-meta {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .solution-author {
            font-weight: 600;
            color: #2c3e50;
        }

        .solution-date {
            font-size: 14px;
            color: #7f8c8d;
        }

        .solution-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 5px 10px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
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

        .solution-body {
            padding: 20px;
        }

        .solution-code {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            overflow-x: auto;
            font-family: 'Courier New', Courier, monospace;
            line-height: 1.5;
            border: 1px solid #e0e0e0;
            margin-bottom: 20px;
        }

        .solution-explanation {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }

        .solution-explanation h4 {
            margin-top: 0;
            color: #2c3e50;
        }

        .solution-feedback {
            margin-top: 20px;
            padding: 15px;
            background: #f0f7fb;
            border-radius: 8px;
            border: 1px solid #d1e7f5;
        }

        .solution-feedback h4 {
            margin-top: 0;
            color: #2980b9;
        }

        .solution-actions {
            padding: 20px;
            border-top: 1px solid #e0e0e0;
            display: flex;
            gap: 15px;
        }

        .btn-approve {
            background: #27ae60;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-approve:hover {
            background: #219653;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .btn-reject {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-reject:hover {
            background: #c0392b;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .feedback-textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            resize: vertical;
            min-height: 80px;
            margin-bottom: 15px;
        }

        .no-solutions {
            background: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .no-solutions h3 {
            color: #2c3e50;
            margin-top: 0;
        }

        .no-solutions p {
            color: #7f8c8d;
        }

        @media (max-width: 768px) {
            .container {
                margin: 20px;
            }
            
            .page-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .solution-actions {
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
        <div class="page-header">
            <h1>Évaluer les Solutions</h1>
            <a href="problem.php?id=<?php echo $problem_id; ?>" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Retour au Problème
            </a>
        </div>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo strpos($message, 'erreur') !== false ? 'error' : 'success'; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="problem-summary">
            <h2 class="problem-title"><?php echo htmlspecialchars($problem['title']); ?></h2>
            <p>Vous évaluez les solutions soumises pour ce problème.</p>
        </div>
        
        <?php if (empty($solutions)): ?>
            <div class="no-solutions">
                <h3>Aucune solution soumise</h3>
                <p>Personne n'a encore soumis de solution pour ce problème.</p>
            </div>
        <?php else: ?>
            <div class="solutions-list">
                <?php foreach ($solutions as $solution): ?>
                    <div class="solution-card">
                        <div class="solution-header">
                            <div class="solution-meta">
                                <span class="solution-author"><?php echo htmlspecialchars($solution['solver_name']); ?> (@<?php echo htmlspecialchars($solution['username']); ?>)</span>
                                <span class="solution-date">Soumis le <?php echo date('d/m/Y à H:i', strtotime($solution['created_at'])); ?></span>
                            </div>
                            <span class="solution-status <?php echo htmlspecialchars($solution['status']); ?>">
                                <?php 
                                switch($solution['status']) {
                                    case 'pending': echo 'En attente'; break;
                                    case 'approved': echo 'Approuvée'; break;
                                    case 'rejected': echo 'Rejetée'; break;
                                    default: echo $solution['status'];
                                }
                                ?>
                            </span>
                        </div>
                        
                        <div class="solution-body">
                            <h3>Solution proposée</h3>
                            <pre class="solution-code"><?php echo htmlspecialchars($solution['solution_code']); ?></pre>
                            
                            <?php if (!empty($solution['explanation'])): ?>
                                <div class="solution-explanation">
                                    <h4>Explication du développeur</h4>
                                    <?php echo nl2br(htmlspecialchars($solution['explanation'])); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($solution['feedback']) && $solution['status'] !== 'pending'): ?>
                                <div class="solution-feedback">
                                    <h4>Votre feedback</h4>
                                    <?php echo nl2br(htmlspecialchars($solution['feedback'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($solution['status'] === 'pending'): ?>
                            <div class="solution-actions">
                                <form action="" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir approuver cette solution?');">
                                    <input type="hidden" name="solution_id" value="<?php echo $solution['id']; ?>">
                                    <input type="hidden" name="action" value="approve">
                                    <textarea name="feedback" class="feedback-textarea" placeholder="Donnez un feedback sur cette solution (optionnel)..."></textarea>
                                    <button type="submit" class="btn-approve">
                                        <i class="fas fa-check"></i> Approuver cette solution
                                    </button>
                                </form>
                                
                                <form action="" method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir rejeter cette solution?');">
                                    <input type="hidden" name="solution_id" value="<?php echo $solution['id']; ?>">
                                    <input type="hidden" name="action" value="reject">
                                    <textarea name="feedback" class="feedback-textarea" placeholder="Expliquez pourquoi vous rejetez cette solution..."></textarea>
                                    <button type="submit" class="btn-reject">
                                        <i class="fas fa-times"></i> Rejeter cette solution
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
