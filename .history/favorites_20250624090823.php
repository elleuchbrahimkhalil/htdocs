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

// Set page title
$page_title = "Mes Favoris";

// Variables de débogage - DÉFINIR EN PREMIER
$debug_mode = isset($_GET['debug']) && $_GET['debug'] == '1';

// Additional CSS specific to this page
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
        transition: transform 0.3s, box-shadow 0.3s;
    }
    .publication-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
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
    .favorite-btn{position:absolute;top:10px;right:10px;background:none;border:none;font-size:1.2em;color:#EC4899;cursor:pointer;transition:all .2s}
    .favorite-btn:hover{transform:scale(1.1)}
    .favorite-btn.active{color:#EC4899}
    .favorite-btn.loading{color:#ffc107;transform:scale(1.1)}
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

    .no-favorites {
        background: white;
        padding: 30px;
        text-align: center;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        max-width: 600px;
        margin: 20px auto;
    }

    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 30px;
        gap: 5px;
    }

    .pagination a, .pagination span {
        display: inline-block;
        padding: 8px 16px;
        text-decoration: none;
        border-radius: 4px;
        transition: background-color 0.3s;
    }

    .pagination a {
        background-color: white;
        color: #3498db;
        border: 1px solid #3498db;
    }

    .pagination a:hover {
        background-color: #ebf5fb;
    }

    .pagination span {
        background-color: #3498db;
        color: white;
        border: 1px solid #3498db;
    }

    .debug-info {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        padding: 15px;
        margin: 20px 0;
        font-family: monospace;
        font-size: 12px;
        max-width: 800px;
        margin-left: auto;
        margin-right: auto;
    }

    .debug-info pre {
        background: white;
        padding: 10px;
        border-radius: 3px;
        overflow-x: auto;
        max-height: 300px;
        overflow-y: auto;
    }
";

// Traitement de la suppression des favoris (même logique que manage_favorites.php)
if (isset($_POST['favorite_action']) && isset($_POST['problem_id'])) {
    require_once 'db_connect.php';
    
    $problemId = $_POST['problem_id'];
    $action = $_POST['favorite_action'];
    
    if ($action === 'remove') {
        // Retirer des favoris
        $conn = connect();
        $stmt = $conn->prepare("DELETE FROM favorites WHERE user_id = ? AND problem_id = ?");
        $stmt->execute([$user['id'], $problemId]);
    }
    
    // Rediriger pour éviter la soumission multiple du formulaire
    header('Location: ' . $_SERVER['PHP_SELF'] . '?success=1');
    exit;
}

// Récupérer les paramètres de pagination
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$problems_per_page = 10;

// Include header
include 'header.php';

// Récupérer les favoris EXACTEMENT comme dans manage_favorites.php mais filtré
require_once 'db_connect.php';

// Initialiser les variables
$favorites = [];
$total_favorites = 0;
$total_pages = 0;
$debug_error = '';

try {
    $conn = connect();
    if ($conn === null) {
        throw new Exception("Impossible de se connecter à la base de données");
    }
    
    // UTILISER LA MÊME REQUÊTE QUE manage_favorites.php MAIS FILTRER SEULEMENT LES FAVORIS
    $stmt = $conn->prepare("
        SELECT p.*, u.username, u.avatar_url,
               (SELECT COUNT(*) FROM favorites WHERE problem_id = p.problem_id) AS favorite_count,
               (SELECT COUNT(*) FROM favorites WHERE problem_id = p.problem_id AND user_id = ?) AS is_favorite,
               (SELECT COUNT(*) FROM solutions WHERE problem_id = p.problem_id AND user_id = ?) AS has_solution
        FROM problems p
        INNER JOIN users u ON p.user_id = u.id
        WHERE p.problem_id IN (SELECT problem_id FROM favorites WHERE user_id = ?)
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$user['id'], $user['id'], $user['id']]);
    $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Compter le total pour la pagination
    $total_favorites = count($favorites);
    $total_pages = ceil($total_favorites / $problems_per_page);
    
    // S'assurer que la page courante est valide
    if ($current_page < 1) {
        $current_page = 1;
    } elseif ($current_page > $total_pages && $total_pages > 0) {
        $current_page = $total_pages;
    }
    
    // Appliquer la pagination
    $offset = ($current_page - 1) * $problems_per_page;
    $favorites = array_slice($favorites, $offset, $problems_per_page);
    
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des favoris: " . $e->getMessage());
    $favorites = [];
    $total_pages = 0;
    $current_page = 1;
    
    if ($debug_mode) {
        $debug_error = $e->getMessage();
    }
}
?>

<h2><i class="fas fa-star"></i> Mes Favoris</h2>

<?php if ($debug_mode): ?>
    <div class="debug-info">
        <h4>🔍 Informations de débogage</h4>
        <pre>
User ID: <?php echo $user['id']; ?>
Username: <?php echo $user['username']; ?>
Total favoris: <?php echo $total_favorites; ?>
Favoris récupérés: <?php echo count($favorites); ?>
Page courante: <?php echo $current_page; ?>
Total pages: <?php echo $total_pages; ?>
        </pre>
        
        <?php if (!empty($debug_error)): ?>
            <h5>❌ Erreur:</h5>
            <pre><?php echo htmlspecialchars($debug_error); ?></pre>
        <?php endif; ?>
        
        <?php
        // Test pour voir tous les favoris bruts
        try {
            $debug_stmt = $conn->prepare("SELECT * FROM favorites WHERE user_id = ?");
            $debug_stmt->execute([$user['id']]);
            $raw_favorites = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);
            echo '<h5>Favoris bruts dans la table favorites:</h5>';
            echo '<pre>' . htmlspecialchars(print_r($raw_favorites, true)) . '</pre>';
        } catch (Exception $e) {
            echo '<h5>Erreur favoris bruts: ' . htmlspecialchars($e->getMessage()) . '</h5>';
        }
        ?>
        
        <?php if (!empty($favorites)): ?>
            <h5>Échantillon des favoris récupérés:</h5>
            <pre><?php echo htmlspecialchars(print_r(array_slice($favorites, 0, 2), true)); ?></pre>
        <?php endif; ?>
    </div>
    
    <div style="margin: 20px 0; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; max-width: 800px; margin-left: auto; margin-right: auto;">
        <strong>Mode débogage activé.</strong> 
        <a href="?">Désactiver le débogage</a> | 
        <a href="manage_favorites.php">Aller à manage_favorites.php</a>
    </div>
<?php endif; ?>

<?php if (empty($favorites)): ?>
    <div class="no-favorites">
        <h3>Aucun favori trouvé</h3>
        <p>Vous n'avez pas encore ajouté de problèmes à vos favoris.</p>
        <p>Allez sur la page d'accueil et cliquez sur les cœurs (♥) pour ajouter des problèmes à vos favoris.</p>
        <?php if ($debug_mode): ?>
            <p><strong>Debug:</strong> Total favoris trouvés: <?php echo $total_favorites; ?></p>
            <p><strong>Debug:</strong> User ID utilisé: <?php echo $user['id']; ?></p>
        <?php endif; ?>
        <div style="margin-top: 20px;">
            <a href="exaccueil.php" class="btn btn-primary" style="margin-right: 10px;">
                <i class="fas fa-home"></i> Aller à l'accueil
            </a>
            <a href="manage_favorites.php" class="btn btn-success">
                <i class="fas fa-heart"></i> Gérer les favoris
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="publications-grid">
        <?php foreach ($favorites as $favorite): ?>
            <div class="publication-card">
                <!-- Bouton favori (toujours actif puisque c'est dans les favoris) -->
                <button class="favorite-btn active" 
                        data-problem-id="<?php echo htmlspecialchars($favorite['problem_id']); ?>"
                        title="Retirer des favoris">
                    <i class="fas fa-heart"></i>
                </button>
                
                <?php if ($debug_mode): ?>
                    <div style="background: #f8f9fa; padding: 5px; margin-bottom: 10px; font-size: 11px; border-radius: 3px;">
                        <strong>Debug:</strong> Problem ID: <?php echo $favorite['problem_id']; ?> | 
                        Author: <?php echo $favorite['username']; ?> | 
                        Favoris: <?php echo $favorite['favorite_count']; ?> |
                        Is Favorite: <?php echo $favorite['is_favorite']; ?>
                    </div>
                <?php endif; ?>
                
                <div class="publication-header">
                    <img src="<?php echo htmlspecialchars($favorite['avatar_url'] ?? 'default.png'); ?>" 
                         class="author-avatar" alt="Avatar">
                    <div>
                        <div class="username"><?php echo htmlspecialchars($favorite['username']); ?></div>
                        <div class="date"><?php echo date('d/m/Y H:i', strtotime($favorite['created_at'])); ?></div>
                    </div>
                </div>
                
                <div>
                    <div class="status-dot <?php echo (isset($favorite['has_solution']) && $favorite['has_solution']) ? 'solved' : 'unsolved'; ?>"></div>
                    <h3 class="publication-title">
                        <a href="problem.php?id=<?php echo $favorite['problem_id']; ?>" style="text-decoration: none; color: inherit;">
                            <?php echo htmlspecialchars($favorite['title']); ?>
                        </a>
                    </h3>
                </div>
                
                <div class="publication-description">
                    <?php 
                    $description = $favorite['description'] ?? '';
                    $preview = substr($description, 0, 100);
                    if (strlen($description) > 100) {
                        $preview .= '...';
                    }
                    echo nl2br(htmlspecialchars($preview)); 
                    ?>
                </div>
                
                <?php if (!empty($favorite['code'])): ?>
                    <div class="publication-code">
                        <pre><?php echo htmlspecialchars($favorite['code']); ?></pre>
                    </div>
                <?php endif; ?>
                
                <div class="tags-container">
                    <?php if (isset($favorite['difficulty'])): ?>
                        <span class="difficulty-<?php echo htmlspecialchars($favorite['difficulty']); ?> tag">
                            <?php 
                            $difficulty_labels = [
                                'easy' => 'Facile',
                                'medium' => 'Moyen',
                                'hard' => 'Difficile'
                            ];
                            echo $difficulty_labels[$favorite['difficulty']] ?? ucfirst($favorite['difficulty']);
                            ?>
                        </span>
                    <?php endif; ?>
                    
                    <?php if (!empty($favorite['tags'])): ?>
                        <?php foreach (explode(',', $favorite['tags']) as $tag): ?>
                            <span class="tag"><?php echo htmlspecialchars(trim($tag)); ?></span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="publication-footer">
                    <div class="publication-stats">
                        <span><i class="fas fa-code"></i> <?php echo htmlspecialchars(ucfirst($favorite['language'] ?? 'N/A')); ?></span>
                        <span><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($favorite['solution_count'] ?? 0); ?> résolutions</span>
                        <span><i class="fas fa-heart"></i> <?php echo htmlspecialchars($favorite['favorite_count']); ?> favoris</span>
                    </div>
                    
                    <div class="action-buttons">
                        <a href="problem.php?id=<?php echo htmlspecialchars($favorite['problem_id']); ?>" 
                           class="btn btn-primary">
                            <i class="fas fa-eye"></i> Voir
                        </a>
                        <a href="submit_solution.php?problem_id=<?php echo htmlspecialchars($favorite['problem_id']); ?>" 
                           class="btn btn-success">
                            <i class="fas fa-paper-plane"></i> Soumettre
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($current_page > 1): ?>
                <a href="?page=<?php echo $current_page - 1; ?><?php echo $debug_mode ? '&debug=1' : ''; ?>">
                    <i class="fas fa-chevron-left"></i> Précédent
                </a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                <?php if ($i == $current_page): ?>
                    <span><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?><?php echo $debug_mode ? '&debug=1' : ''; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($current_page < $total_pages): ?>
                <a href="?page=<?php echo $current_page + 1; ?><?php echo $debug_mode ? '&debug=1' : ''; ?>">
                    Suivant <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
    <div id="success-message" style="position: fixed; top: 20px; right: 20px; background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; border: 1px solid #c3e6cb; z-index: 9999; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        ✅ Favori supprimé avec succès!
    </div>
    <script>
        setTimeout(function() {
            const successMsg = document.getElementById('success-message');
            if (successMsg) {
                successMsg.style.opacity = '0';
                successMsg.style.transition = 'opacity 0.3s ease';
                setTimeout(() => successMsg.remove(), 300);
            }
        }, 3000);
    </script>
<?php endif; ?>

<?php
// Additional scripts
$additional_scripts = "
    // Gestion des favoris dans la page favorites
    document.querySelectorAll('.favorite-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const problemId = this.dataset.problemId;
            const heartIcon = this.querySelector('i');
            const card = this.closest('.publication-card');
            
            // Confirmation avant suppression
            if (!confirm('Êtes-vous sûr de vouloir retirer ce problème de vos favoris?')) {
                return;
            }
            
            // Désactiver le bouton pendant la requête
            this.disabled = true;
            heartIcon.className = 'fas fa-spinner fa-spin';
            
            // Préparer les données pour la requête
            const formData = new FormData();
            formData.append('problem_id', problemId);
            formData.append('favorite_action', 'remove');
            
            // Envoyer la requête vers manage_favorites.php
            fetch('manage_favorites.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Animation de suppression
                    card.style.opacity = '0';
                    card.style.transform = 'translateX(-100%)';
                    card.style.transition = 'all 0.5s ease';
                    
                    setTimeout(() => {
                        card.remove();
                        
                        // Vérifier s'il reste des favoris
                        const remainingCards = document.querySelectorAll('.publication-card');
                        if (remainingCards.length === 0) {
                            // Recharger la page pour afficher le message \"aucun favori\"
                            window.location.reload();
                        }
                    }, 500);
                    
                    showMessage('Retiré des favoris!', 'success');
                } else {
                    throw new Error(data.message || 'Erreur serveur');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showMessage('Erreur: ' + error.message, 'error');
                
                // Réactiver le bouton en cas d'erreur
                this.disabled = false;
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
        
        setTimeout(() => {
            messageDiv.style.opacity = '0';
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.parentNode.removeChild(messageDiv);
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
    
    // Animation hover pour les cartes
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.publication-card');
        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                if (this.style.opacity !== '0') { // Ne pas animer si en cours de suppression
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 4px 15px rgba(0,0,0,0.1)';
                }
            });
            
            card.addEventListener('mouseleave', function() {
                if (this.style.opacity !== '0') { // Ne pas animer si en cours de suppression
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '0 2px 8px rgba(0,0,0,0.05)';
                }
            });
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
    
    // Fonction pour copier le lien d'un problème
    function copyProblemLink(problemId) {
        const link = window.location.origin + '/problem.php?id=' + problemId;
        navigator.clipboard.writeText(link).then(function() {
            showMessage('Lien copié dans le presse-papiers!', 'success');
        }).catch(function() {
            showMessage('Impossible de copier le lien', 'error');
        });
    }
    
    // Ajouter des boutons de partage
    document.addEventListener('DOMContentLoaded', function() {
        const actionContainers = document.querySelectorAll('.action-buttons');
        actionContainers.forEach(container => {
            const card = container.closest('.publication-card');
            const problemId = card.querySelector('[data-problem-id]').dataset.problemId;
            
            // Bouton de partage
            const shareButton = document.createElement('a');
            shareButton.className = 'btn';
            shareButton.style.backgroundColor = '#6c757d';
            shareButton.style.color = 'white';
            shareButton.style.borderColor = '#6c757d';
            shareButton.innerHTML = '<i class=\"fas fa-share\"></i> Partager';
            shareButton.href = '#';
            shareButton.onclick = (e) => {
                e.preventDefault();
                copyProblemLink(problemId);
            };
            shareButton.title = 'Copier le lien du problème';
            
            container.appendChild(shareButton);
        });
    });
    
    // Fonction pour actualiser la liste des favoris
    function refreshFavorites() {
        window.location.reload();
    }
    
    // Ajouter un bouton d'actualisation si en mode debug
    " . ($debug_mode ? "
    document.addEventListener('DOMContentLoaded', function() {
        const debugInfo = document.querySelector('.debug-info');
        if (debugInfo) {
            const refreshButton = document.createElement('button');
            refreshButton.textContent = '🔄 Actualiser les favoris';
            refreshButton.onclick = refreshFavorites;
            refreshButton.style.margin = '10px 5px';
            refreshButton.style.padding = '8px 12px';
            refreshButton.style.backgroundColor = '#28a745';
            refreshButton.style.color = 'white';
            refreshButton.style.border = '1px solid #28a745';
            refreshButton.style.borderRadius = '4px';
            refreshButton.style.cursor = 'pointer';
            
            const testContainer = document.createElement('div');
            testContainer.style.marginTop = '15px';
            testContainer.style.padding = '10px';
            testContainer.style.background = '#e9ecef';
            testContainer.style.borderRadius = '5px';
            
            const testTitle = document.createElement('h5');
            testTitle.textContent = '🧪 Actions de test:';
            testTitle.style.marginTop = '0';
            
            testContainer.appendChild(testTitle);
            testContainer.appendChild(refreshButton);
            
            debugInfo.appendChild(testContainer);
        }
    });
    " : "") . "
";

// Include
