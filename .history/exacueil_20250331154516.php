<?php
// Configuration des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

ob_start(); // Start output buffering

// Démarrage de la session
session_start();


// Inclusion des fichiers nécessaires
require_once 'include_avatar.php';
require_once 'verification.php';
require_once 'db_connect.php';
require_once 'exmenu.php';

// Vérification de la connexion utilisateur
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Vérification de l'ID du problème
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: exacueil.php');
    exit;
}

// Initialisation des variables
$problem_id = (int)$_GET['id'];
$user_id = $_SESSION['user_id'];
$problem = null;
$existing_solution = null;
$errors = [];
$success = '';

// Génération du token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Connexion à la base de données et récupération des données
try {
    $pdo = connect();
    // Test simple
    $stmt = $pdo->query("SELECT TOP 1 * FROM problems");

    $test = $stmt->fetch(PDO::FETCH_ASSOC);
    // var_dump($test); // Afficher le résultat
    // exit; // Arrêter l'exécution pour voir le résultat

    
    // Suite du code...
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupération du problème
    $stmt = $pdo->prepare("
        SELECT p.*, u.username, u.name as author_name
        FROM problems p
        JOIN users u ON p.user_id = u.id
        WHERE p.problem_id = ?
    ");
    $stmt->execute([$problem_id]);
    $problem = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$problem) {
        header('Location: exacueil.php');
        exit;
    }
// Dans submit_solution.php et autres fichiers
$stmt = $pdo->prepare("
    INSERT INTO solutions (problem_id, user_id, solution_code, explanation, status, created_at)
    VALUES (?, ?, ?, ?, 'pending', CURRENT_TIMESTAMP)
");
    // Récupération des solutions existantes
    $stmt = $pdo->prepare("
        SELECT TOP 1 *
        FROM solutions
        WHERE problem_id = ? AND user_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$problem_id, $user_id]);
    $existing_solution = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Erreur PDO: " . $e->getMessage());
    $errors[] = "Une erreur de base de données est survenue: " . $e->getMessage();
} catch (Exception $e) {
    error_log("Erreur: " . $e->getMessage());
    $errors[] = $e->getMessage();
}

// Récupération des erreurs de formulaire
if (isset($_SESSION['form_errors'])) {
    $errors = array_merge($errors, $_SESSION['form_errors']);
    unset($_SESSION['form_errors']);
}

// Récupération du message de succès
if (isset($_SESSION['success'])) {
    $success = $_SESSION['success'];
    unset($_SESSION['success']);
}
function safe_echo($var) {
    if (is_array($var)) {
        echo htmlspecialchars(implode(', ', $var));
    } else {
        echo htmlspecialchars((string)$var);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Soumettre une solution - <?php safe_echo($problem['title'] ?? 'Problème'); ?></title>


    
    <!-- CodeMirror pour l'éditeur de code -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/dracula.min.css">
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
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .sidebar ul li a:hover {
            background-color: #e0e0e0;
            transform: translateX(5px);
        }

        .form-container {
            max-width: 900px;
            margin: 0 auto;
            background-color: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }

        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .form-header h2 {
            margin: 0;
            color: #2c3e50;
            font-size: 1.8rem;
            border-bottom: none;
        }

        .back-button {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            background-color: #f8f9fa;
            color: #2c3e50;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .back-button:hover {
            background-color: #e9ecef;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        textarea, input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            transition: border 0.3s;
        }

        textarea:focus, input[type="text"]:focus {
            border-color: #3498db;
            outline: none;
        }

        textarea {
            min-height: 300px;
            resize: vertical;
            font-family: 'Consolas', 'Monaco', monospace;
        }

        button {
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
            background-color: #4CAF50;
            color: white;
        }

        button:hover {
            background-color: #45a049;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .error-message {
            color: #e74c3c;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #fadbd8;
            border-radius: 6px;
            border-left: 4px solid #e74c3c;
        }

        .success-message {
            color: #27ae60;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #d5f5e3;
            border-radius: 6px;
            border-left: 4px solid #27ae60;
        }

        .problem-details {
            margin-bottom: 25px;
            padding: 20px;
            background-color: #f9f9f9;
            border-left: 4px solid #4CAF50;
            border-radius: 6px;
        }

        .problem-details h3 {
            margin-top: 0;
            color: #2c3e50;
            font-size: 1.4em;
        }

        .problem-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 15px;
            font-size: 0.9rem;
            color: #7f8c8d;
        }

        .problem-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 5px 10px;
            background-color: #f8f9fa;
            border-radius: 20px;
            font-size: 0.9em;
        }

        .existing-solution {
            margin-bottom: 25px;
            padding: 20px;
            background-color: #f0f7fb;
            border-left: 4px solid #3498db;
            border-radius: 6px;
        }

        .status-pending {
            color: #f39c12;
        }

        .status-approved {
            color: #27ae60;
        }

        .status-rejected {
            color: #e74c3c;
        }

        .CodeMirror {
            border: 1px solid #ddd;
            border-radius: 6px;
            height: auto;
            min-height: 300px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .form-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
        }

        .form-actions button {
            min-width: 200px;
        }

        .form-actions .cancel-btn {
            background-color: transparent;
            color: #7f8c8d;
            border: 1px solid #ddd;
        }

        .form-actions .cancel-btn:hover {
            background-color: #f8f9fa;
            color: #2c3e50;
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

        .difficulty-easy {
            color: #28a745;
        }

        .difficulty-medium {
            color: #ffc107;
        }

        .difficulty-hard {
            color: #dc3545;
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
            
            .form-actions {
                flex-direction: column;
                gap: 15px;
            }
            
            .form-actions button {
                width: 100%;
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