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
        transition: all 0.3s ease;
    }
    .publication-card:hover{
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .publication-header{display:flex;align-items:center;margin-bottom:10px}
    .author-avatar{width:40px;height:40px;border-radius:50%;margin-right:10px;object-fit:cover;border:2px solid #f0f4f8}
    .username{font-weight:600;color:#2c3e50;font-size:1em}
    .date{color:#7f8c8d;font-size:0.8em}
    .publication-title{font-weight:600;font-size:1.2em;margin:10px 0;color:#2c3e50}
    .publication-description{color:#4a5568;line-height:1.5;margin-bottom:15px;font-size:0.9em;max-height:60px;overflow:hidden;text-overflow:ellipsis}
    .publication-code{background:#f8fafc;padding:10px;border-radius:6px;font-family:monospace;overflow-x:auto;border:1px solid #e2e8f0;max-height:150px;font-size:0.85em;margin-bottom:10px}
    .tags-container{display:flex;flex-wrap:wrap;gap:5px;margin:10px 0}
    .tag{padding:3px 8px;border-radius:15px;font-size:0.75em;background:#edf2f7}
    .difficulty-easy{background:#f0fff4;color:#38a169}
    .difficulty-medium{background:#fffaf0;color:#dd6b20}
    .difficulty-hard{background:#fff5f5;color:#e53e3e}
    .publication-footer{display:flex;justify-content:space-between;align-items:center;margin-top:10px;padding-top:10px;border-top:1px solid #f0f4f8}
    .publication-stats{display:flex;gap:10px;color:#718096;font-size:0.8em}
    .favorite-btn{
        position:absolute;
        top:10px;
        right:10px;
        background:none;
        border:none;
        font-size:1.2em;
        color:#cbd5e0;
        cursor:pointer;
        transition:all .2s;
        padding:5px;
        border-radius:50%;
    }
    .favorite-btn:hover{transform:scale(1.1)}
    .favorite-btn.active{color:#EC4899}
    .favorite-btn.loading{color:#ffc107;transform:scale(1.1)}
    .favorite-btn:disabled{opacity:0.6;cursor:not-allowed}
    .status-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 8px;
    }
    .unsolved {background-color: #f56565;}
    .solved {background-color: #48bb78;}
    .action-buttons { display: flex; gap: 10px; flex-wrap: wrap; }
    .btn { 
        padding: 8px 12px; 
        border-radius: 4px; 
        text-decoration: none; 
        font-size: 0.9em; 
        transition: all 0.2s;
        border: none;
        cursor: pointer;
    }
    .btn-primary { background: #4299e1; color: white; }
    .btn-success { background: #48bb78; color: white; }
    .btn-favorites { background: #6f42c1; color: white; }
    .btn:hover { opacity: 0.9; transform: translateY(-1px); }
    
    /* Messages de notification */
    .notification {
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        z-index: 9999;
        max-width: 300px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        transition: opacity 0.3s ease;
        font-weight: bold;
    }
    .notification.success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .notification.error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .notification.info {
        background-color: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }
";

$conn = connect();
if ($conn === null) die("Database connection failed.");
if (!isLoggedIn()) header('Location: login.php');

// Récupérer les informations de l'utilisateur
$user = getCurrentUser();

// Récupérer les publications
try {
    $stmt = $conn->prepare("
        SELECT p.*, u.username, u.avatar_url,
               (SELECT COUNT(*) FROM favorites WHERE problem_id = p.problem_id) AS favorite_count,
               (SELECT COUNT(*) FROM favorites WHERE problem_id = p.problem_id AND user_id = ?) AS is_favorite,
               (SELECT COUNT(*) FROM solutions WHERE problem_id = p.problem_id AND user_id = ?) AS has_solution
        FROM problems p 
        JOIN users u ON p.user_id = u.id 
        ORDER BY p.created_at DESC
        LIMIT 20
    ");
    $stmt->execute([$user['id'], $user['id']]);
    $publications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    error_log("Database error: ".$e->getMessage());
    $publications = [];
}

// Inclure l'en-tête
include 'header.php';
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2><i class="fas fa-code"></i> Problèmes récents</h2>
    <a href="favorites.php" class="btn btn-favorites">
        <i class="fas fa-star"></i> Mes Favoris
    </a>
</div>

<div class="publications-grid">
    <?php if (empty($publications)): ?>
        <div style="text-align: center; padding: 40px; background: white; border-radius: 8px;">
            <h3>Aucun problème trouvé</h3>
            <p>Il n'y a pas encore de problèmes publiés.</p>
        </div>
    <?php else: ?>
        <?php foreach($publications as $pub): ?>
        <div class="publication-card">
            <button class="favorite-btn <?= $pub['is_favorite'] > 0 ? 'active' : '' ?>" 
                    data-problem-id="<?= htmlspecialchars($pub['problem_id']) ?>"
                    title="<?= $pub['is_favorite'] > 0 ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>">
                <i class="fas fa-heart"></i>
            </button>
            
            <div class="publication-header">
                <img src="<?= htmlspecialchars($pub['avatar_url'] ?? 'default.png') ?>" 
                     class="author-avatar" alt="Avatar" onerror="this.src='default.png'">
                <div>
                    <div class="username"><?= htmlspecialchars($pub['username']) ?></div>
                    <div class="date"><?= date('d/m/Y H:i', strtotime($pub['created_at'])) ?></div>
                </div>
            </div>
            
            <div>
                <span class="status-dot <?= $pub['has_solution'] > 0 ? 'solved' : 'unsolved'; ?>" 
                      title="<?= $pub['has_solution'] > 0 ? 'Résolu' : 'Non résolu' ?>"></span>
                <h3 class="publication-title"><?= htmlspecialchars($pub['title']) ?></h3>
            </div>
            
            <div class="publication-description">
                <?= nl2br(htmlspecialchars(substr($pub['description'], 0, 150) . (strlen($pub['description']) > 150 ? '...' : ''))) ?>
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
                    <span><i class="fas fa-check-circle"></i> <?= htmlspecialchars($pub['solution_count'] ?? 0) ?> solutions</span>
                    <span class="favorite-count"><i class="fas fa-heart"></i> <?= htmlspecialchars($pub['favorite_count']) ?> favoris</span>
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
    <?php endif; ?>
</div>

<?php
$additional_scripts = "
    // Gestion des favoris
    document.querySelectorAll('.favorite-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const problemId = this.dataset.problemId;
            const isCurrentlyActive = this.classList.contains('active');
            const heartIcon = this.querySelector('i');
            const card = this.closest('.publication-card');
            const favoriteCountSpan = card.querySelector('.favorite-count');
            
            // Désactiver le bouton pendant la requête
            this.disabled = true;
            this.classList.add('loading');
            heartIcon.className = 'fas fa-spinner fa-spin';
            
            // Préparer les données
            const formData = new FormData();
            formData.append('problem_id', problemId);
            formData.append('favorite_action', isCurrentlyActive ? 'remove' : 'add');
            
            // Envoyer la requête
            fetch('toggle_favorite.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur HTTP: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Mettre à jour l'état du bouton
                    if (data.is_favorite) {
                        this.classList.add('active');
                        this.title = 'Retirer des favoris';
                        showNotification('Ajouté aux favoris!', 'success');
                    } else {
                        this.classList.remove('active');
                        this.title = 'Ajouter aux favoris';
                        showNotification('Retiré des favoris!', 'info');
                    }
                    
                    // Mettre à jour le compteur
                    if (favoriteCountSpan) {
                        favoriteCountSpan.innerHTML = '<i class=\"fas fa-heart\"></i> ' + data.favorite_count + ' favoris';
                    }
                    
                    // Animation de succès
                    heartIcon.style.transform = 'scale(1.3)';
                    setTimeout(() => {
                        heartIcon.style.transform = '';
                    }, 300);
                    
                } else {
                    throw new Error(data.message || 'Erreur inconnue');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur: ' + error.message, 'error');
            })
            .finally(() => {
                // Réactiver le bouton
                this.disabled = false;
                this.classList.remove('loading');
                heartIcon.className = 'fas fa-heart';
            });
        });
    });
    
    // Fonction pour afficher les notifications
    function showNotification(message, type = 'info') {
        // Supprimer les notifications existantes
        const existingNotifications = document.querySelectorAll('.notification');
        existingNotifications.forEach(notif => notif.remove());
        
        const notification = document.createElement('div');
        notification.className = 'notification ' + type;
        
        const icons = {
            success: '✅',
            error: '❌',
            info: 'ℹ️'
        };
        
        notification.innerHTML = icons[type] + ' ' + message;
        document.body.appendChild(notification);
        
        // Animation d'apparition
        setTimeout(() => {
            notification.style.opacity = '1';
        }, 10);
        
        // Suppression automatique
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 3000);
    }
    
    // Animation des cartes au chargement
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
";

include 'footer.php';
?>
