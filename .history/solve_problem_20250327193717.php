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
    header('Location: exacueil.php');
    exit;
}

$problem_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

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
    
    // Vérifier si l'utilisateur est l'auteur du problème (ne peut pas résoudre son propre problème)
    if ($problem['user_id'] == $user_id) {
        header('Location: problem.php?id=' . $problem_id);
        exit;
    }
    
    // Vérifier si l'utilisateur a déjà soumis une solution
    $stmt = $pdo->prepare("
        SELECT *
        FROM solutions
        WHERE problem_id = ? AND user_id = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    
    $stmt->execute([$problem_id, $user_id]);
    $existing_solution = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Erreur lors de la récupération du problème: " . $e->getMessage());
    header('Location: problems.php');
    exit;
}

// Traiter la soumission de solution
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $solution_code = $_POST['solution_code'] ?? '';
    $explanation = $_POST['explanation'] ?? '';
    
    if (empty($solution_code)) {
        $error = "Le code de solution est obligatoire.";
    } else {
        try {
            // Insérer la solution dans la base de données
            $stmt = $pdo->prepare("
                INSERT INTO solutions (
                    problem_id, user_id, solution_code, explanation, status, created_at
                ) VALUES (
                    ?, ?, ?, ?, 'pending', CURRENT_TIMESTAMP
                )
            ");
            
            $result = $stmt->execute([$problem_id, $user_id, $solution_code, $explanation]);
            
            if ($result) {
                $success = "Votre solution a été soumise avec succès et est en attente d'évaluation.";
                
                // Rediriger vers la page du problème après 2 secondes
                header("refresh:2;url=problem.php?id=$problem_id");
            } else {
                $error = "Une erreur est survenue lors de la soumission de votre solution.";
            }
        } catch (Exception $e) {
            error_log("Erreur lors de la soumission de la solution: " . $e->getMessage());
            $error = "Une erreur est survenue lors de la soumission de votre solution.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résoudre: <?php echo htmlspecialchars($problem['title']); ?></title>
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

        .problem-summary {
            background: white;
            padding: 25px;
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

        .problem-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #7f8c8d;
        }

        .difficulty.easy { background: #d5f5e3; color: #27ae60; }
        .difficulty.medium { background: #fef9e7; color: #f39c12; }
        .difficulty.hard { background: #fdedec; color: #e74c3c; }

        .code-block {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            font-family: 'Courier New', Courier, monospace;
            line-height: 1.5;
            border: 1px solid #e0e0e0;
            margin-bottom: 20px;
        }

        .solution-form {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 14px;
            resize: vertical;
            min-height: 200px;
        }

        .explanation-textarea {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100px;
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

        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .form-actions {
            display: flex;
            gap: 15px;
        }

        @media (max-width: 768px) {
            .container {
                margin: 20px;
            }
            
            .form-actions {
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
        <div class="problem-summary">
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
            </div>
            
            <h2>Description</h2>
            <div><?php echo nl2br(htmlspecialchars($problem['description'])); ?></div>
            
            <h2>Code du problème</h2>
            <pre class="code-block"><?php echo htmlspecialchars($problem['code']); ?></pre>
        </div>
        
        <div class="solution-form">
            <h2>Proposer une solution</h2>
            
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <?php if (empty($success)): ?>
                <form action="process_solution.php" method="POST">
                    <input type="hidden" name="problem_id" value="<?php echo $problem_id; ?>">
                    <div class="form-group">
                        <label for="solution_code">Votre solution (<?php echo htmlspecialchars(ucfirst($problem['language'])); ?>)</label>
                        <textarea id="solution_code" name="solution_code" required placeholder="Écrivez votre code ici..."><?php echo htmlspecialchars($existing_solution['solution_code'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="explanation">Explication (optionnelle)</label>
                        <textarea id="explanation" name="explanation" class="explanation-textarea" placeholder="Expliquez votre approche et votre solution..."><?php echo htmlspecialchars($existing_solution['explanation'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Soumettre ma solution</button>
                        <a href="problem.php?id=<?php echo $problem_id; ?>" class="btn btn-outline">Annuler</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
