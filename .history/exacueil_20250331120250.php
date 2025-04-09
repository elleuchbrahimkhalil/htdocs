<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

// Rediriger si l'utilisateur n'est pas connecté
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Récupérer les messages de session (succès / erreur)
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Connexion à la base
$conn = connect();
if ($conn === null) {
    die("Database connection failed.");
}

// Récupérer les publications depuis la base de données
try {
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

$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <?php 
    // Inclusion du header commun qui charge les CSS et métadonnées
    require_once 'header.php'; 
    ?>
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Accueil - CodeChallenge'; ?></title>
    <style>
        /* Layout principal : Sidebar et Contenu */
        .main-container {
            display: flex;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            box-sizing: border-box;
        }
        .sidebar-container {
            flex: 0 0 250px;
        }
        .content {
            flex: 1;
            padding: 20px;
        }
        /* Grille responsive pour les publications */
        .publications-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        /* Carte de publication */
        .publication-card {
            border: 1px solid #ddd;
            border-radius: 10px;
            background-color: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .publication-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        /* En-tête de carte */
        .publication-header {
            display: flex;
            align-items: center;
            padding: 10px 15px;
            background-color: #fafafa;
            border-bottom: 1px solid #f0f0f0;
        }
        .author-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
            border: 2px solid #f0f0f0;
        }
        .publication-meta {
            font-size: 0.9em;
        }
        .publication-meta .username { font-weight: bold; }
        .publication-meta .date { color: #7f8c8d; font-size: 0.85em; }
        /* Contenu de carte */
        .publication-content {
            padding: 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        .problem-title-with-status {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        .problem-status-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 8px;
        }
        .problem-status-solved {
            background-color: #4CAF50;
            box-shadow: 0 0 4px rgba(76,175,80,0.5);
        }
        .problem-status-unsolved {
            background-color: #f44336;
            box-shadow: 0 0 4px rgba(244,67,54,0.5);
        }
        .publication-title {
            font-weight: bold;
            font-size: 1.2em;
            color: #2c3e50;
            margin: 0;
        }
        .publication-description {
            color: #555;
            font-size: 0.95em;
            margin-bottom: 12px;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.5;
        }
        .tags-container {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .tag {
            background-color: #f1f1f1;
            color: #555;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.75em;
        }
        /* Pied de carte */
        .publication-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            padding: 10px 15px;
            background-color: #fafafa;
            border-top: 1px solid #f0f0f0;
        }
        .publication-stats {
            display: flex;
            gap: 10px;
            font-size: 0.85em;
            color: #666;
        }
        .publication-stats span {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        .btn {
            padding: 6px 12px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.85em;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: background-color 0.3s;
        }
        .btn-primary { background-color: #3498db; color: white; }
        .btn-primary:hover { background-color: #2980b9; }
        .favorite-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            color: #ccc;
            font-size: 1.2em;
            cursor: pointer;
            transition: transform 0.3s;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            z-index: 10;
        }
        .favorite-btn:hover {
            background-color: rgba(255,215,0,0.1);
            transform: scale(1.1);
        }
        .favorite-btn.active { color: #FFD700; }
        @media (max-width:768px) {
            .main-container { flex-direction: column; }
            .sidebar-container { flex: 0 0 auto; }
            .publications-grid { grid-template-columns: 1fr; }
            .action-buttons { flex-direction: column; width: 100%; }
            .btn { width: 100%; text-align: center; }
        }
    </style>
</head>
<body>
    <?php 
    // Inclusion du menu global (header avec la navigation)
    require_once 'exmenu.php'; 
    ?>
    <div class="main-container">
        <div class="sidebar-container">
            <?php
            // Inclusion unique de la sidebar
            include 'sidebar.php';
            ?>
        </div>
        <div class="content">
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            <h2><i class="fas fa-list-alt"></i> Publications Récentes</h2>
            <div class="publications-grid">
                <?php if (empty($publications)): ?>
                    <div class="empty-state">
                        <p>Aucune publication pour le moment. Soyez le premier à <a href="expublier.php">publier un problème</a>!</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($publications as $pub): ?>
                        <div class="publication-card">
                            <!-- Bouton favoris -->
                            <button class="favorite-btn <?php echo $pub['is_favorite'] ? 'active' : ''; ?>" onclick="toggleFavorite(this, <?php echo $pub['problem_id']; ?>)">
                                <i class="fas fa-bookmark"></i>
                            </button>
                            <div class="publication-header">
                                <?php 
                                    $avatar = !empty($pub['avatar_url']) ? $pub['avatar_url'] : 'https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png';
                                ?>
                                <img src="<?php echo htmlspecialchars($avatar); ?>" class="author-avatar" alt="Avatar de <?php echo htmlspecialchars($pub['username']); ?>">
                                <div class="publication-meta">
                                    <div class="username"><?php echo htmlspecialchars($pub['username']); ?></div>
                                    <div class="date">
                                        <?php 
                                            $date = new DateTime($pub['created_at']);
                                            $now = new DateTime();
                                            $interval = $date->diff($now);
                                            if ($interval->d > 0) {
                                                echo "Il y a " . $interval->d . " jour" . ($interval->d > 1 ? "s" : "");
                                            } elseif ($interval->h > 0) {
                                                echo "Il y a " . $interval->h . " heure" . ($interval->h > 1 ? "s" : "");
                                            } else {
                                                echo "Il y a " . $interval->i . " minute" . ($interval->i > 1 ? "s" : "");
                                            }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            <div class="publication-content">
                                <div class="problem-title-with-status">
                                    <span class="problem-status-indicator <?php echo $pub['is_solved_by_me'] > 0 ? 'problem-status-solved' : 'problem-status-unsolved'; ?>" title="<?php echo $pub['is_solved_by_me'] > 0 ? 'Vous avez résolu ce problème' : 'Vous n\'avez pas encore résolu ce problème'; ?>"></span>
                                    <div class="publication-title"><?php echo htmlspecialchars($pub['title']); ?></div>
                                </div>
                                <div class="publication-description">
                                    <?php 
                                        $description = $pub['description'];
                                        if (strlen($description) > 150) {
                                            $description = substr($description, 0, 150) . '...';
                                        }
                                        echo nl2br(htmlspecialchars($description));
                                    ?>
                                </div>
                                <?php if (!empty($pub['tags'])): ?>
                                    <div class="tags-container">
                                        <?php
                                            $tags = explode(',', $pub['tags']);
                                            $tags = array_slice($tags, 0, 3);
                                            foreach ($tags as $tag):
                                                if (!empty(trim($tag))):
                                        ?>
                                            <span class="tag"><?php echo htmlspecialchars(trim($tag)); ?></span>
                                        <?php 
                                                endif;
                                            endforeach;
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="publication-footer">
                                <div class="publication-stats">
                                    <span class="difficulty-<?php echo htmlspecialchars($pub['difficulty']); ?>">
                                        <i class="fas fa-signal"></i> 
                                        <?php 
                                            $difficulty_labels = [
                                                'easy' => 'Facile',
                                                'medium' => 'Moyen',
                                                'hard' => 'Difficile'
                                            ];
                                            echo $difficulty_labels[$pub['difficulty']] ?? $pub['difficulty'];
                                        ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-award"></i> <?php echo htmlspecialchars($pub['points']); ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-code"></i> <?php echo htmlspecialchars(ucfirst($pub['language'])); ?>
                                    </span>
                                </div>
                                <div class="action-buttons">
                                    <a href="problem.php?id=<?php echo $pub['problem_id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-eye"></i> Voir
                                    </a>
                                    <a href="submit_solution.php?id=<?php echo $pub['problem_id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i> Soumettre une solution
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php displayUserAvatar(); ?>
        </div>
    </div>
    <script>
        function toggleFavorite(button, problemId) {
            button.classList.toggle('active');
            button.classList.add('favorite-animation');
            setTimeout(() => { button.classList.remove('favorite-animation'); }, 500);
            fetch('toggle_favorite.php?problem_id=' + problemId + '&ajax=1', {
                method: 'GET',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log(data.message);
                    const message = document.createElement('div');
                    message.className = 'favorite-message';
                    message.textContent = data.action === 'added' ? 'Ajouté aux favoris!' : 'Retiré des favoris';
                    message.style.position = 'absolute';
                    message.style.top = '50px';
                    message.style.right = '15px';
                    message.style.backgroundColor = data.action === 'added' ? 'rgba(255,215,0,0.2)' : '#f8f9fa';
                    message.style.color = data.action === 'added' ? '#856404' : '#666';
                    message.style.padding = '5px 10px';
                    message.style.borderRadius = '4px';
                    message.style.fontSize = '12px';
                    message.style.opacity = '0';
                    message.style.transition = 'opacity 0.3s';
                    button.parentNode.appendChild(message);
                    setTimeout(() => { message.style.opacity = '1'; }, 10);
                    setTimeout(() => {
                        message.style.opacity = '0';
                        setTimeout(() => { message.remove(); }, 300);
                    }, 2000);
                } else {
                    button.classList.toggle('active');
                    console.error(data.message);
                }
            })
            .catch(error => {
                button.classList.toggle('active');
                console.error('Erreur lors de la mise à jour des favoris:', error);
            });
        }
    </script>
</body>
</html>