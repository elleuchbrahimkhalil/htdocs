<?php
// Ajouter au début du fichier
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Démarrer la session
session_start();

// Inclure les fichiers nécessaires
require_once 'verification.php';
require_once 'db_connect.php';
require_once 'exmenu.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}


// Vérifier si l'ID du problème est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: exacueil.php');  // Assurez-vous que c'est bien exacueil.php et non problems.php
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
        header('Location: exacueil.php');
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
    header('Location: exacueil.php');
    exit;
}

// Après avoir récupéré les détails du problème
$problem_id = $_GET['id'];  // Cette variable doit être définie et utilisée dans le formulaire

// Récupérer les erreurs du formulaire s'il y en a
$form_errors = $_SESSION['form_errors'] ?? [];
unset($_SESSION['form_errors']);

// Afficher le formulaire de soumission de solution
// ... reste du code ...
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soumettre une solution</title>
    <style>
        body {
            display: flex;
            flex-direction: column;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .main-container {
            display: flex;
            flex: 1;
        }

        .sidebar {
            width: 25%;
            padding: 20px;
            background-color: #f4f4f4;
        }

        .content {
            width: 75%;
            padding: 20px;
        }

        .form-container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        input[type="text"],
        input[type="number"],
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }

        textarea {
            min-height: 200px;
            resize: vertical;
        }

        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }

        button:hover {
            background-color: #45a049;
        }

        .error-message {
            color: red;
            margin-bottom: 15px;
        }

        .success-message {
            color: green;
            margin-bottom: 15px;
        }

        .problem-details {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-left: 4px solid #4CAF50;
        }
    </style>
</head>
<body>
    <?php include 'exmenu.php'; ?>

    <div class="main-container">
        <div class="sidebar">
            <h2>Formation Générale</h2>
            <ul>
                <li><a href="https://www.hackerrank.com/">HackerRank</a></li>
                <li><a href="https://www.codewars.com/">Codewars</a></li>
                <li><a href="https://www.leetcode.com/">LeetCode</a></li>
                <li><a href="https://www.topcoder.com/">TopCoder</a></li>
            </ul>
            <h2>Compétitions Mondiales</h2>
            <ul>
                <li><a href="https://icpc.global/">ICPC</a></li>
                <li><a href="https://www.kaggle.com/">Kaggle</a></li>
            </ul>
            <h2>Compétitions Régionales</h2>
            <ul>
                <li><a href="https://www.codechef.com/">CodeChef</a></li>
                <li><a href="https://www.codingame.com/">CodinGame</a></li>
            </ul>
            <h2>Contact</h2>
            <ul>
                <li><a href="mailto:contact@example.com">contact@example.com</a></li>
            </ul>
        </div>
        <div class="content">
            <div class="form-container">
                <h2>Soumettre une solution</h2>
                
                <?php if (!empty($errors)): ?>
                    <div class="error-message">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($success)): ?>
                    <div class="success-message">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($problemDetails)): ?>
                    <div class="problem-details">
                        <h3><?php echo htmlspecialchars($problemDetails['title']); ?></h3>
                        <p><?php echo htmlspecialchars($problemDetails['description']); ?></p>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="process_solution.php">
                    <input type="hidden" name="problem_id" value="<?php echo htmlspecialchars($problem_id); ?>">
    
                    <div class="form-group">
                        <label for="solution_code">Votre solution</label>
                        <textarea id="solution_code" name="solution_code" placeholder="Entrez votre code ou solution ici..."></textarea>
                    </div>
    
                    <div class="form-group">
                        <label for="explanation">Explication (optionnelle)</label>
                        <textarea id="explanation" name="explanation" placeholder="Expliquez votre approche..."></textarea>
                    </div>
    
                    <button type="submit">Soumettre la solution</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>