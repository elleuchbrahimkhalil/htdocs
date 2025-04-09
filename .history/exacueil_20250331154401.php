<?php
session_start();
// Vérifier la connexion de l'utilisateur
require_once 'verification.php';

if (!empty($error_message)): ?>
    <div class="alert alert-danger">
        <?php echo htmlspecialchars($error_message); ?>
    </div>
<?php endif; ?>
<?php
require_once 'db_connect.php';

// Initialiser la connexion à la base de données
$conn = connect();

if ($conn === null) {
    die("Database connection failed.");
}

// Rediriger si l'utilisateur n'est pas connecté
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Afficher les messages de succès/erreur
$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['error_message']);

// Inclure le fichier contenant la fonction pour afficher l'avatar
require 'include_avatar.php';

// Récupérer les informations de l'utilisateur
$user = getCurrentUser();

// Récupérer les publications depuis la base de données
try {
    // Requête modifiée pour inclure l'information si l'utilisateur a résolu le problème
    $stmt = $conn->prepare("
        SELECT p.*, u.username, u.avatar_url,
               (SELECT COUNT(*) FROM favorites WHERE favorites.problem_id = p.problem_id AND favorites.user_id = ?) AS is_favorite,
               (SELECT COUNT(*) FROM solutions WHERE solutions.problem_id = p.problem_id AND solutions.user_id = ? AND (solutions.status = 'approved' OR solutions.status = 'accepted')) AS is_solved_by_me
        FROM problems p 
        JOIN users u ON p.user_id = u.id 
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $publications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des publications: " . $e->getMessage());
    $publications = [];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page d'accueil</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            display: flex;
            flex-direction: column;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background-color: #f9f9f9;
        }

        .main-container {
            display: flex;
            flex: 1;
        }

        .sidebar {
            width: 250px;
            padding: 20px;
            background-color: #f4f4f4;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }

        .content {
            flex: 1;
            padding: 20px;
        }

        .sidebar h2, .content h2 {
            font-size: 1.5em;
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #eaeaea;
            padding-bottom: 10px;
        }

        .sidebar ul {
            list-style-type: none;
            padding: 0;
            margin: 0 0 20px 0;
        }

        .sidebar ul li {
            margin: 10px 0;
        }

        .sidebar ul li a {
            text-decoration: none;
            color: #333;
            display: block;
            padding: 8px;
            border-radius: 4px;
            transition: all 0.3s;
        }

        .sidebar ul li a:hover {
            background-color: #e0e0e0;
            transform: translateX(5px);
        }

        .publications-grid {
            display: flex;
            flex-direction: column;
            gap: 25px;
            margin-top: 20px;
        }

        .publication-card {
            border: 1px solid #ddd;
            border-radius: 12px;
            padding: 20px;
            background-color: white;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
        }

        .publication-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.12);
        }

        .publication-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 15px;
        }

        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 15px;
            object-fit: cover;
            border: 2px solid #f0f0f0;
        }

        .publication-meta {
            font-size: 0.95em;
        }

        .publication-meta .username {
            font-weight: bold;
            color: #2c3e50;
            font-size: 1.1em;
        }

        .publication-meta .date {
            color: #7f8c8d;
            font-size: 0.9em;
        }

        .publication-title {
            font-weight: bold;
            font-size: 1.4em;
            margin-bottom: 15px;
            color: #2c3e50;
        }

        .publication-content {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 20px;
        }

        .publication-description {
            color: #333;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .publication-code {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
            border: 1px solid #e0e0e0;
            color: #333;
            line-height: 1.5;
            max-height: 200px;
            overflow-y: auto;
        }

        .publication-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }

        .publication-stats {
            display: flex;
            gap: 15px;
            color: #666;
            flex-wrap: wrap;
        }

        .publication-stats span {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            background-color: #f8f9fa;
            border-radius: 20px;
            font-size: 0.9em;
        }

        .action-buttons {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .btn {
            padding: 10px 18px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background-color: #4CAF50;
            color: white;
        }

        .btn-primary:hover {
            background-color: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .favorite-btn {
            cursor: pointer;
            font-size: 1.5em;
            background: none;
            border: none;
            padding: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            color: #ccc;
            position: absolute;
            top: 15px;
            right: 15px;
            z-index: 10;
            width: 40px;
            height: 40px;
            border-radius: 50%;
        }

        .favorite-btn:hover {
            background-color: rgba(255,215,0,0.1);
            transform: scale(1.1);
        }

        .favorite-btn i {
            transition: all 0.3s;
        }

        .favorite-btn.active {
            color: #FFD700;
            background-color: rgba(255,215,0,0.15);
            box-shadow: 0 0 8px rgba(255,215,0,0.5);
        }

        .favorite-btn.active i {
            transform: scale(1.2);
        }

        @keyframes favoriteAdded {
            0% { transform: scale(1); }
            50% { transform: scale(1.3); }
            100% { transform: scale(1); }
        }
        
        .favorite-animation {
            animation: favoriteAdded 0.5s ease;
        }

        .difficulty-easy {
            color: #28a745;
        }

        .difficulty-medium {
            color: #ffc107;
        }

        .difficulty-hard {
            color: #dc3545;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 8px;
            font-weight: 500;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }

        .alert-danger {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .tags-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .tag {
            background-color: #e9ecef;
            color: #495057;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
        }

        .problem-status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 8px;
            position: relative;
            top: 1px;
        }

        .problem-status-solved {
            background-color: #4CAF50;
            box-shadow: 0 0 5px rgba(76, 175, 80, 0.5);
        }

        .problem-status-unsolved {
            background-color: #f44336;
            box-shadow: 0 0 5px rgba(244, 67, 54, 0.5);
        }

        .problem-title-with-status {
            display: flex;
            align-items: center;
        }

        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .publication-content {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>

    <div class="main-container">
        <div class="sidebar">
            <h2>Formation Générale</h2>
            <ul>
                <li><a href="https://www.hackerrank.com/" target="_blank"><i class="fas fa-laptop-code"></i> HackerRank</a></li>
                <li><a href="https://www.codewars.com/" target="_blank"><i class="fas fa-code"></i> Codewars</a></li>
                <li><a href="https://www.leetcode.com/" target="_blank"><i class="fas fa-file-code"></i> LeetCode</a></li>
                <li><a href="https://www.topcoder.com/" target="_blank"><i class="fas fa-trophy"></i> TopCoder</a></li>
            </ul>
            
            <h2>Compétitions Mondiales</h2>
            <ul>
                <li><a href="https://icpc.global/" target="_blank"><i class="fas fa-globe"></i> ICPC</a></li>
                <li><a href="https://www.kaggle.com/" target="_blank"><i class="fas fa-chart-line"></i> Kaggle</a></li>
            </ul>
            
            <h2>Compétitions Régionales</h2>
            <ul>
                <li><a href="https://www.codechef.com/" target="_blank"><i class="fas fa-utensils"></i> CodeChef</a></li>
                <li><a href="https://www.codingame.com/" target="_blank"><i class="fas fa-gamepad"></i> CodinGame</a></li>
            </ul>
            
            <h2>Mes Actions</h2>
            <ul>
                <li><a href="expublier.php"><i class="fas fa-plus-circle"></i> Publier un problème</a></li>
                <li><a href="favorites.php"><i class="fas fa-star"></i> Mes problèmes favoris</a></li>
            </ul>
            
            <h2>Contact</h2>
            <ul>
                <li><a href="mailto:contact@example.com"><i class="fas fa-envelope"></i> contact@example.com</a></li>
            </ul>
        </div>
        
        <div class="content">
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> 
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <div class="form-container">
                <div class="form-header">
                    <h2><i class="fas fa-paper-plane"></i> Soumettre une solution</h2>
                    <a href="problem.php?id=<?= htmlspecialchars($problem_id) ?>" class="back-button">
                        <i class="fas fa-arrow-left"></i> Retour au problème
                    </a>
                </div>
                
                <?php if (!empty($problem)): ?>
                    <div class="problem-details">
                        <h3><?= htmlspecialchars($problem['title']) ?></h3>
                        <p><?= nl2br(htmlspecialchars($problem['description'])) ?></p>
                        <div class="problem-meta">
                            <span>
                                <i class="fas fa-user"></i> 
                                <?= htmlspecialchars($problem['author_name'] ?? $problem['username']) ?>
                            </span>
                            <span>
                                <i class="fas fa-code"></i> 
                                <?= htmlspecialchars(ucfirst($problem['language'] ?? 'inconnu')) ?>
                            </span>
                            <span class="difficulty-<?= htmlspecialchars($problem['difficulty']) ?>">
                                <i class="fas fa-signal"></i> 
                                <?php 
                                $difficulty_labels = [
                                    'easy' => 'Facile',
                                    'medium' => 'Moyen',
                                    'hard' => 'Difficile'
                                ];
                                echo $difficulty_labels[$problem['difficulty']] ?? $problem['difficulty'];
                                ?>
                            </span>
                            <span>
                                <i class="fas fa-award"></i> 
                                <?= htmlspecialchars($problem['points'] ?? '0') ?> points
                            </span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($existing_solution): ?>
                    <div class="existing-solution">
                        <h3><i class="fas fa-history"></i> Votre dernière soumission</h3>
                        <p><strong>Statut :</strong> 
                            <span class="status-<?= htmlspecialchars($existing_solution['status']) ?>">
                                <?php 
                                $status_labels = [
                                    'pending' => 'En attente',
                                    'approved' => 'Approuvée',
                                    'rejected' => 'Rejetée'
                                ];
                                echo $status_labels[$existing_solution['status']] ?? $existing_solution['status'];
                                ?>
                            </span>
                        </p>
                        <?php if (!empty($existing_solution['feedback'])): ?>
                            <p><strong>Feedback :</strong> <?= nl2br(htmlspecialchars($existing_solution['feedback'])) ?></p>
                        <?php endif; ?>
                        <p><small>Soumis le <?= date('d/m/Y à H:i', strtotime($existing_solution['created_at'])) ?></small></p>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="process_solution.php" onsubmit="return validateForm()">
                    <input type="hidden" name="problem_id" value="<?= htmlspecialchars($problem_id) ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    
                    <div class="form-group">
                        <label for="solution_code">
                            <i class="fas fa-code"></i> Votre solution 
                            <?php if (!empty($problem['language'])): ?>
                                (<?= htmlspecialchars(ucfirst($problem['language'])) ?>)
                            <?php endif; ?>
                        </label>
                        <textarea id="solution_code" name="solution_code" placeholder="Entrez votre code ici..."><?= 
                            isset($_SESSION['old_solution_code']) ? 
                            htmlspecialchars($_SESSION['old_solution_code']) : 
                            ($existing_solution['solution_code'] ?? '') 
                        ?></textarea>
                    </div>
    
                    <div class="form-group">
                        <label for="explanation">
                            <i class="fas fa-comment-alt"></i> Explication de votre approche (optionnelle)
                        </label>
                        <textarea id="explanation" name="explanation" placeholder="Expliquez votre raisonnement, algorithmes utilisés..."><?= 
                            isset($_SESSION['old_explanation']) ? 
                            htmlspecialchars($_SESSION['old_explanation']) : 
                            ($existing_solution['explanation'] ?? '') 
                        ?></textarea>
                    </div>
    
                    <div class="form-actions">
                        <a href="problem.php?id=<?= htmlspecialchars($problem_id) ?>" class="cancel-btn">
                            <i class="fas fa-times"></i> Annuler
                        </a>
                        <button type="submit">
                            <i class="fas fa-paper-plane"></i> Soumettre la solution
                        </button>
                    </div>
                </form>
            </div>
            
            <?php 
            // Afficher l'avatar utilisateur si la fonction existe
            if (function_exists('displayUserAvatar')) {
                displayUserAvatar();
            }
            ?>
        </div>
    </div>

    <!-- Scripts CodeMirror -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/clike/clike.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/javascript/javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/python/python.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/mode/php/php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/addon/edit/matchbrackets.min.js"></script>
    
    <script>
        // Déterminer le mode en fonction du langage du problème
        function getCodeMirrorMode(language) {
            const modeMap = {
                'python': 'text/x-python',
                'java': 'text/x-java',
                'javascript': 'text/javascript',
                'c': 'text/x-csrc',
                'cpp': 'text/x-c++src',
                'csharp': 'text/x-csharp',
                'php': 'application/x-httpd-php',
                'ruby': 'text/x-ruby',
                'swift': 'text/x-swift',
                'go': 'text/x-go',
                'rust': 'text/x-rustsrc',
                'kotlin': 'text/x-kotlin',
                'typescript': 'text/typescript',
                'sql': 'text/x-sql',
                'html': 'text/html'
            };
            
            return modeMap[language] || 'text/x-csrc';
        }
        
        // Initialisation de l'éditeur de code
        var editor = CodeMirror.fromTextArea(document.getElementById('solution_code'), {
            lineNumbers: true,
            mode: getCodeMirrorMode('<?= htmlspecialchars($problem['language'] ?? 'c') ?>'),
            theme: 'dracula',
            indentUnit: 4,
            matchBrackets: true,
                'java': 'text/x-java',
                'javascript': 'text/javascript',
                'c': 'text/x-csrc',
                'cpp': 'text/x-c++src',
                'csharp': 'text/x-csharp',
                'php': 'application/x-httpd-php',
                'ruby': 'text/x-ruby',
                'swift': 'text/x-swift',
                'go': 'text/x-go',
                'rust': 'text/x-rustsrc',
                'kotlin': 'text/x-kotlin',
                'typescript': 'text/typescript',
                'sql': 'text/x-sql',
                'html': 'text/html'
            };
            
            return modeMap[language] || 'text/x-csrc';
        }
        
        // Initialisation de l'éditeur de code
        var editor = CodeMirror.fromTextArea(document.getElementById('solution_code'), {
            lineNumbers: true,
            mode: getCodeMirrorMode('<?= htmlspecialchars($problem['language'] ?? 'c') ?>'),
            theme: 'dracula',
            indentUnit: 4,
            matchBrackets: true,
            lineWrapping: true,
            extraKeys: {"Ctrl-Space": "autocomplete"}
        });
        
        // Initialisation de l'éditeur d'explication
        var explanationEditor = CodeMirror.fromTextArea(document.getElementById('explanation'), {
            lineNumbers: false,
            mode: 'text/plain',
            theme: 'default',
            lineWrapping: true
        });

        // Validation du formulaire
        function validateForm() {
            const code = editor.getValue();
            if (code.trim() === '') {
                alert('Veuillez entrer votre solution avant de soumettre');
                return false;
            }
            return true;
        }
        
        // Animation pour les boutons
        document.querySelectorAll('button, .back-button, .cancel-btn').forEach(button => {
            button.addEventListener('mouseenter', () => {
                button.style.transform = 'translateY(-2px)';
                button.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
            });
            
            button.addEventListener('mouseleave', () => {
                button.style.transform = '';
                button.style.boxShadow = '';
            });
        });
    </script>
</body>
</html>