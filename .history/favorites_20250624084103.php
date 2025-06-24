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

// Additional CSS specific to this page
$additional_css = "
    .favorites-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .favorite-card {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 15px;
        background-color: white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        position: relative;
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .favorite-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .favorite-star {
        position: absolute;
        top: 10px;
        right: 10px;
        font-size: 24px;
        cursor: pointer;
        color: gold;
        background: none;
        border: none;
    }

    .favorite-title {
        margin-top: 0;
        margin-bottom: 15px;
        padding-right: 40px;
    }

    .favorite-title a {
        color: #2c3e50;
        text-decoration: none;
        font-weight: bold;
    }

    .favorite-title a:hover {
        color: #3498db;
    }

    .publication-title {
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 10px;
    }

    .publication-preview {
        color: #666;
        margin-bottom: 15px;
        line-height: 1.5;
    }

    .favorite-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 15px;
    }

    .favorite-stats {
        display: flex;
        gap: 15px;
        color: #666;
        flex-wrap: wrap;
        font-size: 14px;
    }

    .favorite-stats span {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .favorite-actions {
        display: flex;
        gap: 10px;
    }

    .btn {
        padding: 8px 12px;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 14px;
        border: 1px solid;
    }

    .btn-primary {
        background-color: #3498db;
        color: white;
        border-color: #3498db;
    }

    .btn-primary:hover {
        background-color: #2980b9;
        border-color: #2980b9;
    }

    .no-favorites {
        background: white;
        padding: 30px;
        text-align: center;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
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
        $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND problem_id = ?");
        $stmt->execute([$user['id'], $problemId]);
    }
    
    // Rediriger pour éviter la soumission multiple du formulaire
    header('Location: ' . $_SERVER['PHP_SELF'] . '?success=1');
    exit;
}

// Récupérer les paramètres de pagination
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$problems_per_page = 10;

// Variables de débogage
$debug_mode = isset($_GET['debug']) && $_GET['debug'] == '1';

// Include header
include 'header.php';

// Récupérer les favoris EXACTEMENT comme dans manage_favorites.php mais filtré
require_once 'db_connect.php';

try {
    // UTILISER LA MÊME REQUÊTE QUE manage_favorites.php MAIS FILTRER SEULEMENT LES FAVORIS
    // SQL Server syntax
    $stmt = $pdo->prepare("
        SELECT p.*, u.username,
               (SELECT COUNT(*) FROM favorites WHERE problem_id = p.problem_id) AS favorite_count,
               (SELECT COUNT(*) FROM favorites WHERE problem_id = p.problem_id AND user_id = ?) AS is_favorite
        FROM problems p
        INNER JOIN users u ON p.user_id = u.id
        WHERE p.problem_id IN (SELECT problem_id FROM favorites WHERE user_id = ?)
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$user['id'], $user['id']]);
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

<h1><i class="fas fa-star"></i> Mes Favoris</h1>

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
        
        <?php if (isset($debug_error)): ?>
            <h5>❌ Erreur:</h5>
            <pre><?php echo htmlspecialchars($debug_error); ?></pre>
        <?php endif; ?>
        
        <?php
        // Test pour voir tous les favoris bruts
        try {
            $debug_stmt = $pdo->prepare("SELECT * FROM favorites WHERE user_id = ?");
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
    
    <div style="margin: 20px 0; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px;">
        <strong>Mode débogage activé.</strong> 
        <a href="?">Désactiver le débogage</a> | 
        <a href="manage_favorites.php">Aller à manage_favorites.php</a>
    </div>
<?php endif; ?>

<?php if (empty($favorites)): ?>
    <div class="no-favorites">
        <h3>Aucun favori trouvé</h3>
        <p>Vous n'avez pas encore ajouté de problèmes à vos favoris.</p>
        <p>Allez sur la page d'accueil et cliquez sur les étoiles (★) pour ajouter des problèmes à vos favoris.</p>
        <?php if ($debug_mode): ?>
            <p><strong>Debug:</strong> Total favoris trouvés: <?php echo $total_favorites; ?></p>
            <p><strong>Debug:</strong> User ID utilisé: <?php echo $user['id']; ?></p>
        <?php endif; ?>
        <a href="exaccueil.php" class="btn btn-primary">
            <i class="fas fa-home"></i> Aller à l'accueil
        </a>
        <a href="manage_favorites.php" class="btn btn-primary">
            <i class="fas fa-heart"></i> Gérer les favoris
        </a>
    </div>
<?php else: ?>
    <div class="favorites-list">
        <?php foreach ($favorites as $favorite): ?>
            <div class="favorite-card">
                <!-- Étoile de favori (toujours active puisque c'est dans les favoris) -->
                <form method="POST" class="favorite-form" style="display: inline;">
                    <input type="hidden" name="problem_id" value="<?php echo htmlspecialchars($favorite['problem_id']); ?>">
                    <input type="hidden" name="favorite_action" value="remove">
                    <button type="submit" class="favorite-star" title="Retirer des favoris" onclick="return confirm('Retirer ce problème de vos favoris?')">
                        ★
                    </button>
                </form>
                
                <?php if ($debug_mode): ?>
                    <div style="background: #f8f9fa; padding: 5px; margin-bottom: 10px; font-size: 11px; border-radius: 3px;">
                        <strong>Debug:</strong> Problem ID: <?php echo $favorite['problem_id']; ?> | 
                        Author: <?php echo $favorite['username']; ?> | 
                        Favoris: <?php echo $favorite['favorite_count']; ?> |
                        Is Favorite: <?php echo $favorite['is_favorite']; ?>
                    </div>
                <?php endif; ?>
                
                <div class="publication-title">
                    <a href="problem.php?id=<?php echo $favorite['problem_id']; ?>">
                        <?php echo htmlspecialchars($favorite['title']); ?>
                    </a>
                </div>
                
                <div class="publication-preview">
                    <?php 
                    $description = $favorite['description'] ?? '';
                    $preview = substr($description, 0, 150);
                    if (strlen($description) > 150) {
                        $preview .= '...';
                    }
                    echo nl2br(htmlspecialchars($preview)); 
                    ?>
                </div>
                
                <div class="favorite-footer">
                    <div class="favorite-stats">
                        <span>
                            <i class="fas fa-user"></i>
                            Par <?php echo htmlspecialchars($favorite['username']); ?>
                        </span>
                        
                        <span>
                            <i class="fas fa-heart"></i>
                            <?php echo htmlspecialchars($favorite['favorite_count']); ?> favoris
                        </span>
                        
                        <?php if (isset($favorite['created_at'])): ?>
                        <span>
                            <i class="fas fa-calendar"></i>
                            <?php echo date('d/m/Y', strtotime($favorite['created_at'])); ?>
                        </span>
                        <?php endif; ?>
                        
                        <?php if (isset($favorite['difficulty'])): ?>
                        <span>
                            <i class="fas fa-signal"></i>
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
                    </div>
                    
                        <a href="problem.php?id=<?php echo $favorite['problem_id']; ?>" class="btn btn-primary">
                            <i class="fas fa-eye"></i> Voir le problème
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
    // Animation pour les cartes de favoris
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.favorite-card');
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

    // Gestion des formulaires de favoris avec animation
    document.addEventListener('DOMContentLoaded', function() {
        const favoriteForms = document.querySelectorAll('.favorite-form');
        favoriteForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const button = form.querySelector('.favorite-star');
                const action = form.querySelector('input[name=\"favorite_action\"]').value;
                
                if (action === 'remove') {
                    if (!confirm('Êtes-vous sûr de vouloir retirer ce problème de vos favoris?')) {
                        e.preventDefault();
                        return false;
                    }
                    
                    // Animation de suppression
                    const card = form.closest('.favorite-card');
                    card.style.opacity = '0.5';
                    card.style.transform = 'scale(0.95)';
                    
                    // Ajouter un indicateur de chargement
                    button.innerHTML = '⏳';
                    button.disabled = true;
                }
            });
        });
    });

    // Gestion des hover effects pour les cartes
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.favorite-card');
        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                if (this.style.opacity !== '0.5') { // Ne pas animer si en cours de suppression
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 8px 25px rgba(0,0,0,0.15)';
                }
            });
            
            card.addEventListener('mouseleave', function() {
                if (this.style.opacity !== '0.5') { // Ne pas animer si en cours de suppression
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
                }
            });
        });
    });

    // Fonction pour afficher des messages de statut
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
            case 'warning':
                messageDiv.style.backgroundColor = '#fff3cd';
                messageDiv.style.color = '#856404';
                messageDiv.style.border = '1px solid #ffeaa7';
                messageDiv.innerHTML = '⚠️ ' + message;
                break;
            default:
                messageDiv.style.backgroundColor = '#d1ecf1';
                messageDiv.style.color = '#0c5460';
                messageDiv.style.border = '1px solid #bee5eb';
                messageDiv.innerHTML = 'ℹ️ ' + message;
        }
        
        document.body.appendChild(messageDiv);
        
        // Supprimer le message après 5 secondes
        setTimeout(() => {
            messageDiv.style.opacity = '0';
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.parentNode.removeChild(messageDiv);
                }
            }, 300);
        }, 5000);
    }

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
        const actionContainers = document.querySelectorAll('.favorite-actions');
        actionContainers.forEach(container => {
            const card = container.closest('.favorite-card');
            const problemId = card.querySelector('input[name=\"problem_id\"]').value;
            
            // Bouton de partage
            const shareButton = document.createElement('button');
            shareButton.className = 'btn';
            shareButton.style.backgroundColor = '#6c757d';
            shareButton.style.color = 'white';
            shareButton.style.borderColor = '#6c757d';
            shareButton.innerHTML = '<i class=\"fas fa-share\"></i> Partager';
            shareButton.onclick = () => copyProblemLink(problemId);
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

// Include footer
include 'footer.php';
?>
