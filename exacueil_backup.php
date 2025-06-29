<?php
header("Access-Control-Allow-Origin: https://elleuchbrahimkhalil.github.io");
header("X-Frame-Options: ALLOW-FROM https://elleuchbrahimkhalil.github.io");

session_start();
require_once 'verification.php';
require_once 'db_connect.php';

// Configuration de la page
$page_title = "Problèmes récents";
$additional_css = "
    .publications-grid{
        display: flex;
        flex-direction: column;
        gap: 15px;
        margin-top: 15px;
        max-width: 800px;
        margin-left: auto;
        margin-right: auto;
    }
    .publication-card{
        border: 1px solid #e0e6ed;
        border-radius: 8px;
        padding: 15px;
        background: #fff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        position: relative;
        width: 100%;
    }
    .publication-header{display:flex;align-items:center;margin-bottom:10px}
    .author-avatar{width:40px;height:40px;border-radius:50%;margin-right:10px;object-fit:cover;border:2px solid #f0f4f8}
    .username{font-weight:600;color:#2c3e50;font-size:1em}
    .date{color:#7f8c8d;font-size:0.8em}
    .publication-title{font-weight:600;font-size:1.2em;margin:10px 0;color:#2c3e50}
    .publication-description{color:#4a5568;line-height:1.5;margin-bottom:15px;font-size:0.9em;max-height:60px;overflow:hidden;text-overflow:ellipsis}
    .read-more-btn{color:#4299e1;font-size:0.85em;cursor:pointer;text-decoration:underline;background:none;border:none;padding:0}
    .publication-code{background:#f8fafc;padding:10px;border-radius:6px;font-family:monospace;overflow-x:auto;border:1px solid #e2e8f0;max-height:150px;font-size:0.85em;margin-bottom:10px}
    .tags-container{display:flex;flex-wrap:wrap;gap:5px;margin:10px 0}
    .tag{padding:3px 8px;border-radius:15px;font-size:0.75em;background:#edf2f7}
    .difficulty-easy{background:#f0fff4;color:#38a169}
    .difficulty-medium{background:#fffaf0;color:#dd6b20}
    .difficulty-hard{background:#fff5f5;color:#e53e3e}
    .publication-footer{display:flex;justify-content:space-between;align-items:center;margin-top:10px;padding-top:10px;border-top:1px solid #f0f4f8}
    .publication-stats{display:flex;gap:10px;color:#718096;font-size:0.8em}
    .favorite-btn{position:absolute;top:10px;right:10px;background:none;border:none;font-size:1.2em;color:#cbd5e0;cursor:pointer;transition:all .2s}
    .favorite-btn:hover{transform:scale(1.1)}
    .favorite-btn.active{color:#EC4899}
    .favorite-btn.loading{color:#ffc107;transform:scale(1.1)}
    .status-indicator {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        margin-right: 5px;
    }

    .status-indicator.not-submitted {
        background-color: red;
    }

    .status-indicator.submitted {
        background-color: green;
    }
    .status-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 8px;
    }
    .unsolved {
        background-color: red;
    }
    .solved {
        background-color: green;
    }
    .action-buttons { display: flex; gap: 10px; }
    .btn { padding: 8px 12px; border-radius: 4px; text-decoration: none; font-size: 0.9em; }
    .btn-primary { background: #4299e1; color: white; }
    .btn-success { background: #48bb78; color: white; }
    .btn:hover { opacity: 0.9; }
";

$conn = connect();
if ($conn === null) die("Database connection failed.");
if (!isLoggedIn()) header('Location: login.php');

// Récupérer les informations de l'utilisateur
$user = getCurrentUser();

// Préparer les messages pour header.php
$_SESSION['success_message'] = $_SESSION['success_message'] ?? '';
$_SESSION['error_message'] = $_SESSION['error_message'] ?? '';

// Récupérer les publications EXACTEMENT comme dans manage_favorites.php
try {
    $stmt = $conn->prepare("
        SELECT p.*, u.username, u.avatar_url,
               (SELECT COUNT(*) FROM favorites WHERE problem_id = p.problem_id) AS favorite_count,
               (SELECT COUNT(*) FROM favorites WHERE problem_id = p.problem_id AND user_id = ?) AS is_favorite,
               (SELECT COUNT(*) FROM solutions WHERE problem_id = p.problem_id AND user_id = ?) AS has_solution
        FROM problems p 
        JOIN users u ON p.user_id = u.id 
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$user['id'], $user['id']]);
    $publications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Database error: ".$e->getMessage());
    $publications = [];
}

// Inclure l'en-tête qui contient la structure de base et la sidebar
include 'header.php';
?>

<h2>Problèmes récents</h2>

<div class="publications-grid">
    <?php foreach($publications as $pub): ?>
    <div class="publication-card">
        <button class="favorite-btn <?= $pub['is_favorite'] > 0 ? 'active' : '' ?>" 
                data-problem-id="<?= htmlspecialchars($pub['problem_id']) ?>"
                title="<?= $pub['is_favorite'] > 0 ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>">
            <i class="fas fa-heart"></i>
        </button>
        
        <div class="publication-header">
            <img src="<?= htmlspecialchars($pub['avatar_url']??'default.png') ?>" 
                 class="author-avatar" alt="Avatar">
            <div>
                <div class="username"><?= htmlspecialchars($pub['username']) ?></div>
                <div class="date"><?= date('d/m/Y H:i', strtotime($pub['created_at'])) ?></div>
            </div>
        </div>
        
        <div>
            <div class="status-dot <?= $pub['has_solution'] ? 'solved' : 'unsolved'; ?>"></div>
            <h3 class="publication-title"><?= htmlspecialchars($pub['title']) ?></h3>
        </div>
        
        <div class="publication-description">
            <?= nl2br(htmlspecialchars(substr($pub['description'], 0, 100).(strlen($pub['description']) > 100 ? '...' : ''))) ?>
        </div>
        
        <?php if(!empty($pub['code'])): ?>
            <div class="publication-code">
                <pre><?= htmlspecialchars($pub['code']) ?></pre>
            </div>
        <?php endif; ?>
        
        <div class="tags-container">
            <span class="difficulty-<?= htmlspecialchars($pub['difficulty']) ?> tag">
                <?= ucfirst(htmlspecialchars($pub['difficulty'])) ?>
            </span>
            <?php if(!empty($pub['tags'])): ?>
                <?php foreach(explode(',', $pub['tags']) as $tag): ?>
                    <span class="tag"><?= htmlspecialchars(trim($tag)) ?></span>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="publication-footer">
            <div class="publication-stats">
                <span><i class="fas fa-code"></i> <?= htmlspecialchars(ucfirst($pub['language'])) ?></span>
                <span><i class="fas fa-check-circle"></i> <?= htmlspecialchars($pub['solution_count']??0) ?> résolutions</span>
                <span><i class="fas fa-heart"></i> <?= htmlspecialchars($pub['favorite_count']) ?> favoris</span>
            </div>
            
            <div class="action-buttons">
                <a href="problem.php?id=<?= htmlspecialchars($pub['problem_id']) ?>" 
                   class="btn btn-primary">
                    <i class="fas fa-eye"></i> Voir
                </a>
                <a href="submit_solution.php?problem_id=<?= htmlspecialchars($pub['problem_id']) ?>" 
                   class="btn btn-success">
                    <i class="fas fa-paper-plane"></i> Soumettre
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php
// Ajout du script spécifique pour cette page
$additional_scripts = "
    // Gestion des favoris - VERSION CORRIGÉE
    document.querySelectorAll('.favorite-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const problemId = this.dataset.problemId;
            const isCurrentlyActive = this.classList.contains('active');
            const heartIcon = this.querySelector('i');
            
            // Désactiver le bouton pendant la requête
            this.disabled = true;
            this.classList.add('loading');
            heartIcon.className = 'fas fa-spinner fa-spin';
            
            // Préparer les données pour la requête
            const formData = new FormData();
            formData.append('problem_id', problemId);
            formData.append('favorite_action', isCurrentlyActive ? 'remove' : 'add');
            
            // Envoyer la requête vers manage_favorites.php
            fetch('manage_favorites.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Succès - changer l'état du bouton
                    if (data.action === 'removed') {
                        // Retirer des favoris
                        this.classList.remove('active');
                        this.title = 'Ajouter aux favoris';
                        
                        // Décrémenter le compteur de favoris
                        const statsSpan = this.closest('.publication-card').querySelector('.publication-stats span:last-child');
                        if (statsSpan) {
                            const currentCount = parseInt(statsSpan.textContent.match(/\\d+/)[0]);
                            statsSpan.innerHTML = '<i class="fas fa-heart"></i> ' + Math.max(0, currentCount - 1) + ' favoris';
                        }
                        
                        showMessage('Retiré des favoris!', 'info');
                    } else if (data.action === 'added') {
                        // Ajouter aux favoris
                        this.classList.add('active');
                        this.title = 'Retirer des favoris';
                        
                        // Incrémenter le compteur de favoris
                        const statsSpan = this.closest('.publication-card').querySelector('.publication-stats span:last-child');
                        if (statsSpan) {
                            const currentCount = parseInt(statsSpan.textContent.match(/\\d+/)[0]);
                            statsSpan.innerHTML = '<i class=\"fas fa-heart\"></i> ' + (currentCount + 1) + ' favoris';
                        }
                        
                        showMessage('Ajouté aux favoris!', 'success');
                    }
                    
                    // Animation
                    heartIcon.style.transform = 'scale(1.3)';
                    setTimeout(() => {
                        heartIcon.style.transform = '';
                    }, 200);
                } else {
                    throw new Error(data.message || 'Erreur serveur');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showMessage('Erreur: ' + error.message, 'error');
            })
            .finally(() => {
                // Réactiver le bouton
                this.disabled = false;
                this.classList.remove('loading');
                heartIcon.className = 'fas fa-heart';
            });
        });
    });
    
    // Fonction pour afficher des messages
    function showMessage(message, type = 'info') {
        const messageDiv = document.createElement('div');
        messageDiv.style.position = 'fixed';
        messageDiv.style.top = '20px';
        messageDiv.style.right = '20px';
        messageDiv.style.padding = '15px 20px';
        messageDiv.style.borderRadius = '5px';
        messageDiv.style.zIndex = '9999';
        messageDiv.style.maxWidth = '300px';
        messageDiv.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
        messageDiv.style.transition = 'opacity 0.3s ease';
        
        switch(type) {
            case 'success':
                messageDiv.style.backgroundColor = '#d4edda';
                messageDiv.style.color = '#155724';
                messageDiv.style.border = '1px solid #c3e6cb';
                messageDiv.innerHTML = '✅ ' + message;
                break;
            case 'error':
                messageDiv.style.backgroundColor = '#f8d7da';
                messageDiv.style.color = '#721c24';
                messageDiv.style.border = '1px solid #f5c6cb';
                messageDiv.innerHTML = '❌ ' + message;
                break;
            case 'info':
                messageDiv.style.backgroundColor = '#d1ecf1';
                messageDiv.style.color = '#0c5460';
                messageDiv.style.border = '1px solid #bee5eb';
                messageDiv.innerHTML = 'ℹ️ ' + message;
                break;
        }
        
        document.body.appendChild(messageDiv);
        
        // Supprimer le message après 3 secondes
        setTimeout(() => {            messageDiv.style.opacity = '0';
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.parentNode.removeChild(messageDiv);
                }
            }, 300);
        }, 3000);
    }
    
    // Animation des cartes
    document.querySelectorAll('.publication-card').forEach(card => {
        card.addEventListener('mouseenter', () => 
            card.style.boxShadow = '0 3px 10px rgba(0,0,0,0.1)');
        card.addEventListener('mouseleave', () => 
            card.style.boxShadow = '0 2px 8px rgba(0,0,0,0.05)');
    });
    
    // Animation d'apparition des cartes
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.publication-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => {
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });
    
    // Gestion des erreurs de chargement d'images
    document.addEventListener('DOMContentLoaded', function() {
        const avatars = document.querySelectorAll('.author-avatar');
        avatars.forEach(avatar => {
            avatar.addEventListener('error', function() {
                this.src = 'default.png';
            });
        });
    });
";

// Inclure le pied de page
include 'footer.php';
?>

<?php if (isset($_SESSION['solution_submitted']) && $_SESSION['solution_submitted']): ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Sélectionner l'indicateur correspondant au problème
        var indicators = document.querySelectorAll('.status-indicator[data-problem-id="<?php echo $_SESSION['submitted_problem_id']; ?>"]');
        indicators.forEach(function(indicator) {
            indicator.classList.remove('not-submitted');
            indicator.classList.add('submitted');
        });
    });
</script>
<?php 
    // Nettoyer les variables de session
    unset($_SESSION['solution_submitted']);
    unset($_SESSION['submitted_problem_id']);
endif; 
?>
