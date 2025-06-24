<?php
// Set page title
$page_title = "Mes Favoris";

// Additional CSS specific to this page
$additional_css = "
    .favorites-list {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
    }

    .favorite-card {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        transition: transform 0.3s, box-shadow 0.3s;
        padding: 20px;
        position: relative;
    }

    .favorite-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }

    .favorite-title {
        margin-top: 0;
        margin-bottom: 15px;
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
    }

    .btn-remove:hover {
        background-color: #e74c3c;
        color: white;
    }

    .problem-status-indicator {
        display: inline-block;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        margin-right: 8px;
        position: relative;
        top: 1px;
    }

    .problem-status-solved {
        background-color: #4CAF50;
        box-shadow: 0 0 5px rgba(76, 175, 80, 0.5);
    }

    .problem-status-unsolved {
        background-color: #f44336;
        box-shadow: 0 0 5px rgba(244, 67, 54, 0.5);
    }

    .problem-title-with-status {
        display: flex;
        align-items: center;
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

    .favorite-btn-inline {
        position: absolute;
        top: 15px;
        right: 15px;
        background: none;
        border: none;
        font-size: 1.2em;
        color: #EC4899;
        cursor: pointer;
        transition: all 0.2s;
    }

    .favorite-btn-inline:hover {
        transform: scale(1.1);
    }
";

// Include header
include 'header.php';

// Rediriger si l'utilisateur n'est pas connecté
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Récupérer l'ID de l'utilisateur connecté
$user = getCurrentUser();
$user_id = $user['id'];

// Récupérer les paramètres de pagination
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$problems_per_page = 10;

try {
    $pdo = connect();
    
    if (!$pdo) {
        throw new Exception("Erreur de connexion à la base de données");
    }
    
    // Compter le nombre total de favoris
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ?");
    $stmt_count->execute([$user_id]);
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
    
    // Récupérer les favoris avec les détails des problèmes
    $sql = "
        SELECT p.*, u.username, u.avatar_url,
           (SELECT COUNT(*) FROM solutions 
            WHERE solutions.problem_id = p.problem_id 
            AND solutions.user_id = ? 
            AND (solutions.status = 'accepted')) AS is_solved_by_me
        FROM favorites f
        JOIN problems p ON f.problem_id = p.problem_id
        JOIN users u ON p.user_id = u.id
        WHERE f.user_id = ?
        ORDER BY f.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $user_id, $problems_per_page, $offset]);
    $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des favoris: " . $e->getMessage());
    $favorites = [];
    $total_pages = 0;
    $current_page = 1;
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1><i class="fas fa-star"></i> Mes Favoris (<?php echo $total_favorites; ?>)</h1>
    <a href="exaccueil.php" class="btn btn-primary">
        <i class="fas fa-arrow-left"></i> Retour à l'accueil
    </a>
</div>

<?php if (empty($favorites)): ?>
    <div class="no-favorites">
        <h3>Aucun favori trouvé</h3>
        <p>Ajoutez des problèmes à vos favoris pour les voir ici.</p>
        <a href="exaccueil.php" class="btn btn-primary">
            <i class="fas fa-search"></i> Parcourir les problèmes
        </a>
    </div>
<?php else: ?>
    <div class="favorites-list">
        <?php foreach ($favorites as $favorite): ?>
            <div class="favorite-card">
                <button class="favorite-btn-inline" 
                        data-problem-id="<?php echo $favorite['problem_id']; ?>"
                        title="Retirer des favoris">
                    <i class="fas fa-heart"></i>
                </button>
                
                <h3 class="favorite-title">
                    <div class="problem-title-with-status">
                        <span class="problem-status-indicator <?php echo $favorite['is_solved_by_me'] > 0 ? 'problem-status-solved' : 'problem-status-unsolved'; ?>" 
                              title="<?php echo $favorite['is_solved_by_me'] > 0 ? 'Vous avez résolu ce problème' : 'Vous n\'avez pas encore résolu ce problème'; ?>">
                        </span>
                        <a href="problem.php?id=<?php echo $favorite['problem_id']; ?>">
                            <?php echo htmlspecialchars($favorite['title']); ?>
                        </a>
                    </div>
                </h3>
                
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
                        <span class="difficulty-<?php echo htmlspecialchars($favorite['difficulty']); ?>">
                            <i class="fas fa-signal"></i>
                            <?php 
                            $difficulty_labels = [
                                'easy' => 'Facile',
                                'medium' => 'Moyen',
                                'hard' => 'Difficile'
                            ];
                            echo $difficulty_labels[$favorite['difficulty']] ?? $favorite['difficulty'];
                            ?>
                        </span>
                        <span>
                            <i class="fas fa-award"></i>
                            <?php echo htmlspecialchars($favorite['points'] ?? 0); ?> points
                        </span>
                        <span>
                            <i class="fas fa-code"></i>
                            <?php echo htmlspecialchars(ucfirst($favorite['language'] ?? 'inconnu')); ?>
                        </span>
                    </div>
                    
                    <div class="favorite-actions">
                        <a href="problem.php?id=<?php echo $favorite['problem_id']; ?>" class="btn btn-primary">
                            <i class="fas fa-eye"></i> Voir
                        </a>
                        <a href="submit_solution.php?problem_id=<?php echo $favorite['problem_id']; ?>" class="btn btn-success">
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
                <a href="?page=<?php echo $current_page - 1; ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                <?php if ($i == $current_page): ?>
                    <span><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="?page=<?php echo $i; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($current_page < $total_pages): ?>
                <a href="?page=<?php echo $current_page + 1; ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php
// Additional scripts
$additional_scripts = "
    // Fonction pour supprimer un favori depuis la page favorites
    document.querySelectorAll('.favorite-btn-inline').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            
            const problemId = this.dataset.problemId;
            const card = this.closest('.favorite-card');
            
            if (confirm('Êtes-vous sûr de vouloir retirer ce problème de vos favoris?')) {
                // Effet visuel immédiat
                card.style.opacity = '0.5';
                this.disabled = true;
                
                // Préparer les données
                const formData = new FormData();
                formData.append('problem_id', problemId);
                formData.append('favorite_action', 'remove');
                
                // Envoyer la requête vers toggle_favorite.php
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
                        card.style.transition = 'height 0.3s ease, padding 0.3s ease, margin 0.3s ease';
                        
                        setTimeout(() => {
                            card.style.height = '0';
                            card.style.padding = '0';
                            card.style.margin = '0';
                            
                            setTimeout(() => {
                                card.remove();
                                
                                // Vérifier s'il reste des favoris
                                const favoritesList = document.querySelector('.favorites-list');
                                if (favoritesList && favoritesList.children.length === 0) {
                                    // Recharger la page pour afficher le message \"Aucun favori\"
                                    window.location.reload();
                                } else {
                                    // Mettre à jour le compteur dans le titre
                                    const titleElement = document.querySelector('h1');
                                    if (titleElement) {
                                        const currentCount = parseInt(titleElement.textContent.match(/\\((\\d+)\\)/)[1]);
                                        const newCount = Math.max(0, currentCount - 1);
                                        titleElement.innerHTML = '<i class=\"fas fa-star\"></i> Mes Favoris (' + newCount + ')';
                                    }
                                }
                            }, 300);
                        }, 10);
                        
                        showMessage('Favori retiré avec succès!', 'success');
                    } else {
                        throw new Error(data.message || 'Erreur inconnue');
                    }
                })
                .catch(error => {
                    // Erreur, rétablir l'apparence
                    card.style.opacity = '1';
                    this.disabled = false;
                    console.error('Erreur lors de la suppression du favori:', error);
                    showMessage('Erreur: ' + error.message, 'error');
                });
            }
        });
    });
    
    // Fonction pour afficher des messages (même que dans exaccueil.php)
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
        messageDiv.style.fontWeight = 'bold';
        
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
    
    // Animation d'apparition des cartes
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
    
    // Animation hover des cartes
    document.querySelectorAll('.favorite-card').forEach(card => {
        card.addEventListener('mouseenter', () => {
            if (!card.style.opacity || card.style.opacity === '1') {
                card.style.transform = 'translateY(-5px) scale(1.02)';
            }
        });
        card.addEventListener('mouseleave', () => {
            if (!card.style.opacity || card.style.opacity === '1') {
                card.style.transform = 'translateY(0) scale(1)';
            }
        });
    });
    
    // Raccourci clavier pour retourner à l'accueil (Échap)
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            window.location.href = 'exaccueil.php';
        }
    });
";

// Include footer
include 'footer.php';
?>

