<?php
// Configuration de la page
$page_title = "Mes Favoris";

// CSS spécifique à cette page
$additional_css = "
    .favorites-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .favorites-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .favorites-title {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #2c3e50;
        margin: 0;
    }
    
    .favorites-count {
        background: #6f42c1;
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.9em;
        font-weight: bold;
    }
    
    .favorites-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .favorite-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        position: relative;
        border: 1px solid #e2e8f0;
    }

    .favorite-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }

    .favorite-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 15px;
    }

    .problem-status {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 10px;
    }

    .status-indicator {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .status-solved {
        background-color: #48bb78;
        box-shadow: 0 0 8px rgba(72, 187, 120, 0.4);
    }

    .status-unsolved {
        background-color: #f56565;
        box-shadow: 0 0 8px rgba(245, 101, 101, 0.4);
    }

    .favorite-title {
        font-size: 1.2em;
        font-weight: 600;
        color: #2c3e50;
        margin: 0;
        line-height: 1.3;
    }

    .favorite-title a {
        color: inherit;
        text-decoration: none;
        transition: color 0.2s;
    }

    .favorite-title a:hover {
        color: #3498db;
    }

    .favorite-remove-btn {
        background: none;
        border: none;
        color: #e74c3c;
        font-size: 1.2em;
        cursor: pointer;
        padding: 5px;
        border-radius: 50%;
        transition: all 0.2s;
        flex-shrink: 0;
    }

    .favorite-remove-btn:hover {
        background-color: #fee;
        transform: scale(1.1);
    }

    .favorite-description {
        color: #666;
        line-height: 1.5;
        margin: 15px 0;
        font-size: 0.95em;
    }

    .favorite-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin: 15px 0;
        font-size: 0.85em;
        color: #718096;
    }

    .meta-item {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .difficulty-tag {
        padding: 4px 10px;
        border-radius: 15px;
        font-size: 0.8em;
        font-weight: 600;
    }

    .difficulty-easy { background: #f0fff4; color: #38a169; }
    .difficulty-medium { background: #fffaf0; color: #dd6b20; }
    .difficulty-hard { background: #fff5f5; color: #e53e3e; }

    .favorite-actions {
        display: flex;
        gap: 10px;
        margin-top: 20px;
        flex-wrap: wrap;
    }

    .btn {
        padding: 8px 16px;
        border-radius: 6px;
        text-decoration: none;
        font-size: 0.9em;
        font-weight: 500;
        transition: all 0.2s;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .btn-primary { background: #3498db; color: white; }
    .btn-success { background: #48bb78; color: white; }
    .btn-secondary { background: #6c757d; color: white; }
    
    .btn:hover {
        opacity: 0.9;
        transform: translateY(-1px);
    }

    .no-favorites {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .no-favorites h3 {
        color: #2c3e50;
        margin-bottom: 15px;
    }

    .no-favorites p {
        color: #666;
        margin-bottom: 25px;
    }

    .pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        margin-top: 40px;
        gap: 8px;
    }

    .pagination a, .pagination span {
        padding: 10px 15px;
        border-radius: 6px;
        text-decoration: none;
        transition: all 0.2s;
        font-weight: 500;
    }

    .pagination a {
        background: white;
        color: #3498db;
        border: 1px solid #e2e8f0;
    }

    .pagination a:hover {
        background: #3498db;
        color: white;
    }

    .pagination span {
        background: #3498db;
        color: white;
        border: 1px solid #3498db;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .favorites-grid {
            grid-template-columns: 1fr;
        }
        
        .favorites-header {
            flex-direction: column;
            align-items: stretch;
        }
        
        .favorite-actions {
            justify-content: center;
        }
    }
";

// Inclure l'en-tête
include 'header.php';

// Vérifier la connexion
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Récupérer l'utilisateur
$user = getCurrentUser();
$user_id = $user['id'];

// Pagination
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$items_per_page = 12;

try {
    $pdo = connect();
    
    if (!$pdo) {
        throw new Exception("Erreur de connexion à la base de données");
    }
    
    // Compter le total des favoris
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ?");
    $stmt_count->execute([$user_id]);
    $total_favorites = $stmt_count->fetchColumn();
    
    // Calculer la pagination
    $total_pages = ceil($total_favorites / $items_per_page);
    $current_page = min($current_page, max(1, $total_pages));
    $offset = ($current_page - 1) * $items_per_page;
    
    // Récupérer les favoris
    $sql = "
        SELECT p.*, u.username, u.avatar_url,
               (SELECT COUNT(*) FROM solutions 
                WHERE solutions.problem_id = p.problem_id 
                AND solutions.user_id = ? 
                AND solutions.status = 'accepted') AS is_solved_by_me,
               f.created_at as favorited_at
        FROM favorites f
        JOIN problems p ON f.problem_id = p.problem_id
        JOIN users u ON p.user_id = u.id
        WHERE f.user_id = ?
        ORDER BY f.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $user_id, $items_per_page, $offset]);
    $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Erreur favoris: " . $e->getMessage());
    $favorites = [];
    $total_favorites = 0;
    $total_pages = 0;
}
?>

<div class="favorites-container">
    <div class="favorites-header">
        <h1 class="favorites-title">
            <i class="fas fa-star"></i>
            Mes Favoris
            <span class="favorites-count"><?= $total_favorites ?></span>
        </h1>
        <a href="exaccueil.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour à l'accueil
        </a>
    </div>

    <?php if (empty($favorites)): ?>
        <div class="no-favorites">
            <i class="fas fa-star" style="font-size: 3em; color: #ddd; margin-bottom: 20px;"></i>
            <h3>Aucun favori trouvé</h3>
            <p>Vous n'avez pas encore ajouté de problèmes à vos favoris.<br>
               Parcourez les problèmes et cliquez sur ❤️ pour les ajouter ici.</p>
            <a href="exaccueil.php" class="btn btn-primary">
                <i class="fas fa-search"></i> Parcourir les problèmes
            </a>
        </div>
    <?php else: ?>
        <div class="favorites-grid">
            <?php foreach ($favorites as $favorite): ?>
                <div class="favorite-card" data-problem-id="<?= $favorite['problem_id'] ?>">
                    <div class="favorite-card-header">
                        <div class="problem-status">
                            <span class="status-indicator <?= $favorite['is_solved_by_me'] > 0 ? 'status-solved' : 'status-unsolved' ?>" 
                                  title="<?= $favorite['is_solved_by_me'] > 0 ? 'Résolu par vous' : 'Non résolu' ?>"></span>
                            <h3 class="favorite-title">
                                <a href="problem.php?id=<?= $favorite['problem_id'] ?>">
                                    <?= htmlspecialchars($favorite['title']) ?>
                                </a>
                            </h3>
                        </div>
                        <button class="favorite-remove-btn" 
                                data-problem-id="<?= $favorite['problem_id'] ?>"
                                title="Retirer des favoris">
                            <i class="fas fa-heart"></i>
                        </button>
                    </div>
                    
                    <div class="favorite-description">
                        <?php 
                        $description = $favorite['description'] ?? '';
                        $preview = substr($description, 0, 120);
                        if (strlen($description) > 120) {
                            $preview .= '...';
                        }
                        echo nl2br(htmlspecialchars($preview)); 
                        ?>
                    </div>
                    
                    <div class="favorite-meta">
                        <div class="meta-item">
                            <span class="difficulty-tag difficulty-<?= htmlspecialchars($favorite['difficulty']) ?>">
                                <?php 
                                $difficulty_labels = [
                                    'easy' => 'Facile',
                                    'medium' => 'Moyen', 
                                    'hard' => 'Difficile'
                                ];
                                echo $difficulty_labels[$favorite['difficulty']] ?? ucfirst($favorite['difficulty']);
                                ?>
                            </span>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-award"></i>
                            <?= htmlspecialchars($favorite['points'] ?? 0) ?> pts
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-code"></i>
                            <?= htmlspecialchars(ucfirst($favorite['language'] ?? 'inconnu')) ?>
                        </div>
                        <div class="meta-item">
                            <i class="fas fa-user"></i>
                            <?= htmlspecialchars($favorite['username']) ?>
                        </div>
                    </div>
                    
                    <div class="favorite-actions">
                        <a href="problem.php?id=<?= $favorite['problem_id'] ?>" class="btn btn-primary">
                            <i class="fas fa-eye"></i> Voir le problème
                        </a>
                        <a href="submit_solution.php?problem_id=<?= $favorite['problem_id'] ?>" class="btn btn-success">
                            <i class="fas fa-paper-plane"></i> Soumettre
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($current_page > 1): ?>
                    <a href="?page=<?= $current_page - 1 ?>">
                        <i class="fas fa-chevron-left"></i> Précédent
                    </a>
                <?php endif; ?>
                
                <?php 
                $start_page = max(1, $current_page - 2);
                $end_page = min($total_pages, $current_page + 2);
                
                if ($start_page > 1): ?>
                    <a href="?page=1">1</a>
                    <?php if ($start_page > 2): ?>
                        <span>...</span>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <?php if ($i == $current_page): ?>
                        <span><?= $i ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $i ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($end_page < $total_pages): ?>
                    <?php if ($end_page < $total_pages - 1): ?>
                        <span>...</span>
                    <?php endif; ?>
                    <a href="?page=<?= $total_pages ?>"><?= $total_pages ?></a>
                <?php endif; ?>
                
                <?php if ($current_page < $total_pages): ?>
                    <a href="?page=<?= $current_page + 1 ?>">
                        Suivant <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
$additional_scripts = "
    // Gestion de la suppression des favoris
    document.querySelectorAll('.favorite-remove-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const problemId = this.dataset.problemId;
            const card = this.closest('.favorite-card');
            const problemTitle = card.querySelector('.favorite-title a').textContent.trim();
            
            // Confirmation avec le titre du problème
            if (confirm('Êtes-vous sûr de vouloir retirer \"' + problemTitle + '\" de vos favoris ?')) {
                // Effet visuel immédiat
                card.style.opacity = '0.5';
                card.style.pointerEvents = 'none';
                this.disabled = true;
                
                // Changer l'icône en spinner
                const icon = this.querySelector('i');
                const originalClass = icon.className;
                icon.className = 'fas fa-spinner fa-spin';
                
                // Préparer les données
                const formData = new FormData();
                formData.append('problem_id', problemId);
                formData.append('favorite_action', 'remove');
                
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
                        // Animation de suppression
                        card.style.height = card.offsetHeight + 'px';
                        card.style.overflow = 'hidden';
                        card.style.transition = 'all 0.4s ease';
                        
                        setTimeout(() => {
                            card.style.height = '0';
                            card.style.padding = '0';
                            card.style.margin = '0';
                            card.style.border = 'none';
                            
                            setTimeout(() => {
                                card.remove();
                                
                                // Mettre à jour le compteur
                                const countElement = document.querySelector('.favorites-count');
                                if (countElement) {
                                    const currentCount = parseInt(countElement.textContent);
                                    const newCount = Math.max(0, currentCount - 1);
                                    countElement.textContent = newCount;
                                }
                                
                                // Vérifier s'il reste des favoris
                                const remainingCards = document.querySelectorAll('.favorite-card');
                                if (remainingCards.length === 0) {
                                    // Recharger la page pour afficher le message \"Aucun favori\"
                                    setTimeout(() => {
                                        window.location.reload();
                                    }, 500);
                                }
                                
                            }, 400);
                        }, 100);
                        
                        showNotification('Favori retiré avec succès!', 'success');
                        
                    } else {
                        throw new Error(data.message || 'Erreur inconnue');
                    }
                })
                .catch(error => {
                    // Restaurer l'apparence en cas d'erreur
                    card.style.opacity = '1';
                    card.style.pointerEvents = 'auto';
                    this.disabled = false;
                    icon.className = originalClass;
                    
                    console.error('Erreur lors de la suppression:', error);
                    showNotification('Erreur: ' + error.message, 'error');
                });
            }
        });
    });
    
    // Fonction pour afficher les notifications
    function showNotification(message, type = 'info') {
        // Supprimer les notifications existantes
        const existingNotifications = document.querySelectorAll('.notification');
        existingNotifications.forEach(notif => notif.remove());
        
        const notification = document.createElement('div');
        notification.className = 'notification';
        notification.style.position = 'fixed';
        notification.style.top = '20px';
        notification.style.right = '20px';
        notification.style.padding = '15px 20px';
        notification.style.borderRadius = '8px';
        notification.style.zIndex = '9999';
        notification.style.maxWidth = '350px';
        notification.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
        notification.style.transition = 'all 0.3s ease';
        notification.style.fontWeight = '500';
        notification.style.opacity = '0';
        notification.style.transform = 'translateX(100%)';
        
        const styles = {
            success: {
                background: '#d4edda',
                color: '#155724',
                border: '1px solid #c3e6cb',
                icon: '✅'
            },
            error: {
                background: '#f8d7da',
                color: '#721c24', 
                border: '1px solid #f5c6cb',
                icon: '❌'
            },
            info: {
                background: '#d1ecf1',
                color: '#0c5460',
                border: '1px solid #bee5eb',
                icon: 'ℹ️'
            }
        };
        
        const style = styles[type] || styles.info;
        notification.style.backgroundColor = style.background;
        notification.style.color = style.color;
        notification.style.border = style.border;
        notification.innerHTML = style.icon + ' ' + message;
        
        document.body.appendChild(notification);
        
        // Animation d'apparition
        setTimeout(() => {
            notification.style.opacity = '1';
            notification.style.transform = 'translateX(0)';
        }, 10);
        
        // Suppression automatique
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 4000);
    }
    
    // Animation d'apparition des cartes au chargement
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.favorite-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            setTimeout(() => {
                card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 150);
        });
    });
    
    // Raccourcis clavier
    document.addEventListener('keydown', function(e) {
        // Échap pour retourner à l'accueil
        if (e.key === 'Escape') {
            window.location.href = 'exaccueil.php';
        }
        
        // Ctrl+R ou F5 pour actualiser
        if ((e.ctrlKey && e.key === 'r') || e.key === 'F5') {
            e.preventDefault();
            window.location.reload();
        }
    });
    
    // Gestion du scroll infini (optionnel)
    let isLoading = false;
    window.addEventListener('scroll', function() {
        if (isLoading) return;
        
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const windowHeight = window.innerHeight;
        const documentHeight = document.documentElement.scrollHeight;
        
        // Si on est proche du bas de la page
        if (scrollTop + windowHeight >= documentHeight - 100) {
            const currentPage = <?= $current_page ?>;
            const totalPages = <?= $total_pages ?>;
            
            if (currentPage < totalPages) {
                // Charger la page suivante automatiquement
                const nextPageUrl = '?page=' + (currentPage + 1);
                if (window.location.search !== nextPageUrl) {
                    isLoading = true;
                    // Optionnel: implémenter le chargement AJAX ici
                }
            }
        }
    });
";

include 'footer.php';
?>
