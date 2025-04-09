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

// Récupérer les informations de l'utilisateur
$user = getCurrentUser();

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
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page d'accueil</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Début des styles CSS */
        body {
            display: flex;
            flex-direction: column;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background-color: #f5f7fa;
            color: #333;
        }

        .main-container {
            display: flex;
            flex: 1;
        }

        .sidebar {
            width: 280px;
            padding: 25px 20px;
            background-color: #2c3e50;
            color: #ecf0f1;
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }

        .sidebar h2 {
            font-size: 1.3em;
            margin: 25px 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #34495e;
            color: #ecf0f1;
        }

        .sidebar ul {
            list-style-type: none;
            padding: 0;
            margin: 0 0 25px 0;
        }

        .sidebar ul li {
            margin: 12px 0;
        }

        .sidebar ul li a {
            text-decoration: none;
            color: #bdc3c7;
            display: flex;
            align-items: center;
            padding: 10px 15px;
            border-radius: 6px;
            transition: all 0.3s;
        }

        .sidebar ul li a:hover {
            background-color: #34495e;
            color: #fff;
            transform: translateX(5px);
        }

        .sidebar ul li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .content {
            flex: 1;
            padding: 30px;
            background-color: #fff;
        }

        .publications-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .publication-card {
            border: 1px solid #e0e6ed;
            border-radius: 12px;
            padding: 25px;
            background-color: white;
            box-shadow: 0 3px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
            overflow: hidden;
        }

        .publication-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .publication-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f4f8;
        }

        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            margin-right: 15px;
            object-fit: cover;
            border: 3px solid #f0f4f8;
        }

        .publication-meta .username {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.1em;
            display: block;
        }

        .publication-meta .date {
            color: #7f8c8d;
            font-size: 0.85em;
        }

        .publication-title {
            font-weight: 600;
            font-size: 1.4em;
            margin: 15px 0;
            color: #2c3e50;
        }

        .publication-description {
            color: #4a5568;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .publication-code {
            background-color: #f8fafc;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Fira Code', 'Courier New', monospace;
            overflow-x: auto;
            border: 1px solid #e2e8f0;
            color: #2d3748;
            line-height: 1.5;
            max-height: 200px;
            overflow-y: auto;
            font-size: 0.9em;
            margin-bottom: 15px;
        }

        .tags-container {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 15px 0;
        }

        .tag {
            background-color: #edf2f7;
            color: #4a5568;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 500;
        }

        .difficulty-easy {
            background-color: #f0fff4;
            color: #38a169;
        }

        .difficulty-medium {
            background-color: #fffaf0;
            color: #dd6b20;
        }

        .difficulty-hard {
            background-color: #fff5f5;
            color: #e53e3e;
        }

        .publication-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #f0f4f8;
        }

        .publication-stats {
            display: flex;
            gap: 15px;
            color: #718096;
            flex-wrap: wrap;
            font-size: 0.9em;
        }

        .publication-stats span {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9em;
        }

        .btn-primary {
            background-color: #4299e1;
            color: white;
        }

        .btn-primary:hover {
            background-color: #3182ce;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(66, 153, 225, 0.2);
        }

        .favorite-btn {
            cursor: pointer;
            font-size: 1.4em;
            background: none;
            border: none;
            padding: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            color: #cbd5e0;
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
        }

        .favorite-btn.active i {
            transform: scale(1.2);
        }

        .problem-status-indicator {
            display: inline-block;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin-right: 10px;
        }

        .problem-status-solved {
            background-color: #48bb78;
            box-shadow: 0 0 8px rgba(72, 187, 120, 0.4);
        }

        .problem-status-unsolved {
            background-color: #f56565;
            box-shadow: 0 0 8px rgba(245, 101, 101, 0.4);
        }

        .alert {
            padding: 15px 20px;
            margin-bottom: 25px;
            border-radius: 8px;
            font-weight: 500;
            display: flex;
            align-items: center;
        }

        .alert-success {
            background-color: #f0fff4;
            color: #2f855a;
            border: 1px solid #c6f6d5;
        }

        .alert-danger {
            background-color: #fff5f5;
            color: #c53030;
            border: 1px solid #fed7d7;
        }

        .alert i {
            margin-right: 10px;
            font-size: 1.2em;
        }

        @media (max-width: 1024px) {
            .publications-grid {
                grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            }
        }

        @media (max-width: 768px) {
            .main-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
                padding: 15px;
            }
            
            .content {
                padding: 20px;
            }
            
            .publication-card {
                padding: 20px;
            }
        }

        @media (max-width: 480px) {
            .publications-grid {
                grid-template-columns: 1fr;
            }
            
            .publication-footer {
                flex-direction: column;
                align-items: flex-start;
            }
        }
        /* Fin des styles CSS */
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
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error_message) ?>
                </div>
            <?php endif; ?>
            
            <h2>Problèmes récents</h2>
            
            <div class="publications-grid">
                <?php foreach ($publications as $pub): ?>
                    <div class="publication-card">
                        <button class="favorite-btn <?= $pub['is_favorite'] ? 'active' : '' ?>" 
                                data-problem-id="<?= $pub['problem_id'] ?>">
                            <i class="fas fa-star"></i>
                        </button>
                        
                        <div class="publication-header">
                            <img src="<?= htmlspecialchars($pub['avatar_url'] ?? 'default_avatar.png') ?>" 
                                 alt="User avatar" class="author-avatar">
                            <div class="publication-meta">
                                <span class="username"><?= htmlspecialchars($pub['username']) ?></span>
                                <span class="date"><?= date('d/m/Y H:i', strtotime($pub['created_at'])) ?></span>
                            </div>
                        </div>
                        
                        <div class="problem-title-with-status">
                            <?php if ($pub['is_solved_by_me']): ?>
                                <span class="problem-status-indicator problem-status-solved" 
                                      title="Problème résolu"></span>
                            <?php else: ?>
                                <span class="problem-status-indicator problem-status-unsolved" 
                                      title="Non résolu"></span>
                            <?php endif; ?>
                            <h3 class="publication-title"><?= htmlspecialchars($pub['title']) ?></h3>
                        </div>
                        
                        <div class="publication-description">
                            <?= nl2br(htmlspecialchars($pub['description'])) ?>
                        </div>
                        
                        <?php if (!empty($pub['code'])): ?>
                            <div class="publication-code">
                                <pre><?= htmlspecialchars($pub['code']) ?></pre>
                            </div>
                        <?php endif; ?>
                        
                        <div class="tags-container">
                            <span class="difficulty-<?= $pub['difficulty'] ?> tag">
                                <?= ucfirst($pub['difficulty']) ?>
                            </span>
                            <?php if (!empty($pub['tags'])): ?>
                                <?php $tags = explode(',', $pub['tags']); ?>
                                <?php foreach ($tags as $tag): ?>
                                    <span class="tag"><?= htmlspecialchars(trim($tag)) ?></span>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="publication-footer">
                            <div class="publication-stats">
                                <span>
                                    <i class="fas fa-comments"></i> 
                                    <?= $pub['comment_count'] ?? 0 ?>
                                </span>
                                <span>
                                    <i class="fas fa-check-circle"></i> 
                                    <?= $pub['solution_count'] ?? 0 ?>
                                </span>
                            </div>
                            
                            <div class="action-buttons">
                                <a href="problem.php?id=<?= $pub['problem_id'] ?>" 
                                   class="btn btn-primary">
                                    <i class="fas fa-eye"></i> Voir le problème
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        // Début du JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            // Gestion des boutons favoris
            const favoriteButtons = document.querySelectorAll('.favorite-btn');
            
            favoriteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    const problemId = this.dataset.problemId;
                    const isActive = this.classList.contains('active');
                    
                    // Animation visuelle immédiate
                    this.classList.toggle('active');
                    const icon = this.querySelector('i');
                    icon.style.transform = 'scale(1.3)';
                    
                    // Envoyer la requête AJAX
                    toggleFavorite(problemId, !isActive, this);
                    
                    // Réinitialiser l'animation après un délai
                    setTimeout(() => {
                        icon.style.transform = '';
                    }, 300);
                });
            });
            
            // Fonction pour basculer l'état favori
            function toggleFavorite(problemId, setFavorite, buttonElement) {
                const formData = new FormData();
                formData.append('problem_id', problemId);
                formData.append('set_favorite', setFavorite ? '1' : '0');
                formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');
                
                fetch('api/toggle_favorite.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        // Annuler le changement si l'appel API a échoué
                        buttonElement.classList.toggle('active');
                        showAlert('error', data.message || 'Échec de la mise à jour des favoris');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    buttonElement.classList.toggle('active');
                    showAlert('error', 'Erreur réseau. Veuillez réessayer.');
                });
            }
            
            // Fonction pour afficher des alertes
            function showAlert(type, message) {
                const alertDiv = document.createElement('div');
                alertDiv.className = `alert alert-${type}`;
                alertDiv.innerHTML = `
                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'}"></i>
                    ${message}
                `;
                
                const content = document.querySelector('.content');
                content.insertBefore(alertDiv, content.firstChild);
                
                setTimeout(() => {
                    alertDiv.style.opacity = '0';
                    setTimeout(() => alertDiv.remove(), 300);
                }, 5000);
            }
            
            // Animation au survol des cartes
            const publicationCards = document.querySelectorAll('.publication-card');
            
            publicationCards.forEach(card => {
                card.addEventListener('mouseenter', () => {
                    card.style.boxShadow = '0 10px 25px rgba(0,0,0,0.1)';
                });
                
                card.addEventListener('mouseleave', () => {
                    card.style.boxShadow = '0 3px 15px rgba(0,0,0,0.05)';
                });
            });
        });
        // Fin du JavaScript
    </script>
</body>
</html><!-- ... existing code ... -->

<div class="problem-actions">
  <button class="btn btn-primary" onclick="viewProblem()">Voir problème</button>
  <button class="btn btn-success" onclick="submitProblem()">Soumettre problème</button>
  <button class="btn btn-outline-secondary favorite-btn" onclick="toggleFavorite()">
    <i class="fas fa-heart"></i> <!-- Changed from fa-star to fa-heart -->
  </button>
</div>

<!-- ... existing code ... -->

<script>
  function viewProblem() {
    // Add logic to view the problem
    console.log("Viewing problem");
  }

  function submitProblem() {
    // Add logic to submit the problem
    console.log("Submitting problem");
  }

  function toggleFavorite() {
    // Existing favorite toggle logic
    const favoriteBtn = document.querySelector('.favorite-btn i');
    favoriteBtn.classList.toggle('text-danger');
  }
</script>
