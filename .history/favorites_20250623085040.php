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
    }

    .favorite-title {
        margin-top: 0;
        margin-bottom: 15px;
        padding-right: 40px;
    }

    .favorite-title a {
        color: #2c3e50;
        text-decoration: none;
    }

    .favorite-title a:hover {
        color: #3498db;
    }

    .favorite-description {
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

    .btn-remove {
        background-color: transparent;
        color: #e74c3c;
        border: 1px solid #e74c3c;
        padding: 8px 12px;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .btn-remove:hover {
        background-color: #e74c3c;
        color: white;
    }

    .btn-primary {
        background-color: #3498db;
        color: white;
        border: 1px solid #3498db;
        padding: 8px 12px;
        border-radius: 4px;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
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
    }

    .pagination a:hover {
        background-color: #ebf5fb;
    }

    .pagination span {
        background-color: #3498db;
        color: white;
    }

    .difficulty-easy { color: #28a745; }
    .difficulty-medium { color: #ffc107; }
    .difficulty-hard { color: #dc3545; }

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

// Récupérer les favoris avec les détails des problèmes (même requête que manage_favorites.php mais filtrée)
require_once 'db_connect.php';

try {
    // Compter le nombre total de favoris
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ?");
    $stmt_count->execute([$user['id']]);
    $total_favorites = $stmt_count->fetchColumn();
    
    // Calculer le nombre total de pages
    $total_pages = ceil($total_favorites / $problems_per_page);
    
    // S'assurer que la page courante est valide
    if ($current_page < 1) {
        $current_page = 1;
    } elseif ($current_page > $total_pages && $total_pages > 0) {
        $current_page = $total_pages;
    }
    
    // Calculer l'offset pour la pagination
    $offset = ($current_page - 1) * $problems_per_page;
    
    // Utiliser la même requête que manage_favorites.php mais seulement pour les favoris
    $stmt = $pdo->prepare("
        SELECT p.*, u.username,
               (SELECT COUNT(*) FROM favorites WHERE problem_id = p.problem_id) AS favorite_count,
               1 AS is_favorite
        FROM favorites f
        JOIN problems p ON f.problem_id = p.problem_id
        JOIN users u ON p.user_id = u.id
        WHERE f.user_id = ?
        ORDER BY f.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$user['id'], $problems_per_page, $offset]);
    $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des favoris: " . $e->getMessage());
    $favorites = [];
    $total_pages = 0;
    $current_page = 1;
    
    if ($debug_mode) {
        $debug_error = $e->getMessage();
    }
}

// Include header
include 'header.php';
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
        
        <?php if (!empty($favorites)): ?>
            <h5>Échantillon des favoris:</h5>
            <pre><?php echo htmlspecialchars(print_r(array_slice($favorites, 0, 2), true)); ?></pre>
        <?php endif; ?>
    </div>
    
    <div style="margin: 20px 0; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px;">
        <strong>Mode débogage activé.</strong> 
        <a href="?">Désactiver le débogage</a>
    </div>
<?php endif; ?>

<?php if (empty($favorites)): ?>
    <div class="no-favorites">
        <h3>Aucun favori trouvé</h3>
        <p>Vous n'avez pas encore ajouté de problèmes à vos favoris.</p>
        <?php if ($debug_mode): ?>
            <p><strong>Debug:</strong> Total favoris trouvés: <?php echo $total_favorites; ?></p>
        <?php endif; ?>
        <a href="problems.php" class="btn btn-primary">
            <i class="fas fa-search"></i> Parcourir les problèmes
        </a>
    </div>
<?php else: ?>
    <div class="favorites-list">
        <?php foreach ($favorites as $favorite): ?>
            <div class="favorite-card">
                <!-- Étoile de favori (toujours active puisque c'est dans les favoris) -->
                <form method="POST" class="favorite-form">
                    <input type="hidden" name="problem_id" value="<?php echo htmlspecialchars($favorite['problem_id']); ?>">
                    <input type="hidden" name="favorite_action" value="remove">
                    <button type="submit" class="favorite-star active" title="Retirer des favoris" onclick="return confirm('Retirer ce problème de vos favoris?')">
                        ★
                    </button>
                </form>
                
                <?php if ($debug_mode): ?>
                    <div style="background: #f8f9fa; padding: 5px; margin-bottom: 10px; font-size: 11px; border-radius: 3px;">
                        <strong>Debug:</strong> Problem ID: <?php echo $favorite['problem_id']; ?> | 
                        Author: <?php echo $favorite['username']; ?> | 
                        Favoris: <?php echo $favorite['favorite_count']; ?>
                    </div>
                <?php endif; ?>
                
                <div class="favorite-title">
                    <a href="problem.php?id=<?php echo $favorite['problem_id']; ?>">
                        <?php echo htmlspecialchars($favorite['title']); ?>
                    </a>
                </div>
                
                <div class="favorite-description">
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
                        <?php if (isset($favorite['difficulty'])): ?>
                        <span class="difficulty-<?php echo htmlspecialchars($favorite['difficulty']); ?>">
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
                        
                        <?php if (isset($favorite['points'])): ?>
                        <span>
                            <i class="fas fa-award"></i>
                            <?php echo htmlspecialchars($favorite['points']); ?> points
                        </span>
                        <?php endif; ?>
                        
                        <?php if (isset($favorite['language'])): ?>
                        <span>
                            <i class="fas fa-code"></i>
                            <?php echo htmlspecialchars(ucfirst($favorite['language'])); ?>
                        </span>
                        <?php endif; ?>
                        
                        <span>
                            <i class="fas fa-user"></i>
                            <?php echo htmlspecialchars($favorite['username']); ?>
                        </span>
                        
                        <span>
                            <i class="fas fa-heart"></i>
                            <?php echo htmlspecialchars($favorite['favorite_count']); ?> favoris
                        </span>
                    </div>
                    
                    <div class="favorite-actions">
                        <a href="problem.php?id=<?php echo $favorite['problem_id']; ?>" class="btn btn-primary">
                            <i class="fas fa-eye"></i> Voir
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
                    <i class="fas fa-chevron-left"></i>
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
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
    <div style="position: fixed; top: 20px; right: 20px; background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; border: 1px solid #c3e6cb; z-index: 9999;">
        ✅ Favori supprimé avec succès!
    </div>
    <script>
        setTimeout(function() {
            const successMsg = document.querySelector('[style*="position: fixed"]');
            if (successMsg) {
                successMsg.style.opacity = '0';
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

    // Gestion des formulaires de favoris
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

    // Gestion des hover effects pour les cartes
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.favorite-card');
        cards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px)';
                this.style.boxShadow = '0 8px 25px rgba(0,0,0,0.15)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0)';
                this.style.boxShadow = '0 2px 4px rgba(0,0,0,0.1)';
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

    // Ajouter des boutons de partage si nécessaire
    document.addEventListener('DOMContentLoaded', function() {
        const actionContainers = document.querySelectorAll('.favorite-actions');
        actionContainers.forEach(container => {
            const card = container.closest('.favorite-card');
            const problemId = card.querySelector('input[name=\"problem_id\"]').value;
            
            // Bouton de partage
            const shareButton = document.createElement('button');
            shareButton.className = 'btn btn-secondary';
            shareButton.innerHTML = '<i class=\"fas fa-share\"></i> Partager';
            shareButton.style.backgroundColor = '#6c757d';
            shareButton.style.color = 'white';
            shareButton.style.border = '1px solid #6c757d';
            shareButton.onclick = () => copyProblemLink(problemId);
            
            container.appendChild(shareButton);
        });
    });
";

// Include footer
include 'footer.php';
?>
