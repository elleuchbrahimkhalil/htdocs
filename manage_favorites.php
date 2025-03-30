<?php
// Vérifier la connexion de l'utilisateur
require_once 'verification.php';

// Rediriger si l'utilisateur n'est pas connecté
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Inclure le fichier contenant la fonction pour afficher l'avatar
require 'include_avatar.php';

// Récupérer les informations de l'utilisateur
$user = getCurrentUser();

// Traitement de l'ajout aux favoris
if (isset($_POST['favorite_action']) && isset($_POST['problem_id'])) {
    require_once 'db_connect.php';
    
    $problemId = $_POST['problem_id'];
    $action = $_POST['favorite_action'];
    
    if ($action === 'add') {
        // Ajouter aux favoris
        $stmt = $pdo->prepare("INSERT INTO favorites (user_id, problem_id, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$user['id'], $problemId]);
    } else if ($action === 'remove') {
        // Retirer des favoris
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND problem_id = ?");
        $stmt->execute([$user['id'], $problemId]);
    }
    
    // Rediriger pour éviter la soumission multiple du formulaire
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Récupérer les publications avec leurs statuts de favoris
require_once 'db_connect.php';
$stmt = $pdo->prepare("
    SELECT p.*, u.username,
           (SELECT COUNT(*) FROM favorites WHERE problem_id = p.problem_id) AS favorite_count,
           (SELECT COUNT(*) FROM favorites WHERE problem_id = p.problem_id AND user_id = ?) AS is_favorite
    FROM problems p
    JOIN users u ON p.user_id = u.id
    ORDER BY p.created_at DESC
");
$stmt->execute([$user['id']]);
$publications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page d'accueil</title>
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

        .publications-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .publication-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
        }

        .favorite-star {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            cursor: pointer;
            color: #ccc;
        }

        .favorite-star.active {
            color: gold;
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
        </div>
        <div class="content">
            <h2>Publications</h2>
            <div class="publications-grid">
                <?php foreach ($publications as $pub): ?>
                <div class="publication-card">
                    <form method="POST" class="favorite-form">
                        <input type="hidden" name="problem_id" value="<?php echo htmlspecialchars($pub['problem_id']); ?>">
                        <input type="hidden" name="favorite_action" value="<?php echo $pub['is_favorite'] ? 'remove' : 'add'; ?>">
                        <button type="submit" class="favorite-star <?php echo $pub['is_favorite'] ? 'active' : ''; ?>">
                            ★
                        </button>
                    </form>
                    <div class="publication-title"><?php echo htmlspecialchars($pub['title']); ?></div>
                    <div class="publication-preview">
                        <?php echo htmlspecialchars(substr($pub['description'], 0, 150)) . (strlen($pub['description']) > 150 ? '...' : ''); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
