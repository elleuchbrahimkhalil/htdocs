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
";

// Include header
include 'header.php';

// Rediriger si l'utilisateur n'est pas connecté
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Récupérer l'ID de l'utilisateur connecté
$user_id = $_SESSION['user_id'];

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
            AND solutions.user_id = :user_id 
            AND (solutions.status = 'accepted')) AS is_solved_by_me
        FROM favorites f
        JOIN problems p ON f.problem_id = p.problem_id
        JOIN users u ON p.user_id = u.id
        WHERE f.user_id = :user_id
        ORDER BY f.created_at DESC
        OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT); // Répété car utilisé deux fois
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $problems_per_page, PDO::PARAM_INT);
    $stmt->execute();
    $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des favoris: " . $e->getMessage());
    $favorites = [];
    $total_pages = 0;
    $current_page = 1;
}
?>

<h1><i class="fas fa-star"></i> Mes Favoris</h1>

<?php if (empty($favorites)): ?>
    <div class="no-favorites">
        <h3>Aucun favori trouvé</h3>
        <p>Ajoutez des problèmes à vos favoris pour les voir ici.</p>
        <a href="problems.php" class="btn btn-primary">
            <i class="fas fa-search"></i> Parcourir les problèmes
        </a>
    </div>
<?php else: ?>
    <div class="favorites-list">
        <?php foreach ($favorites as $favorite): ?>
            <div class="favorite-card">
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
                        <button onclick="removeFavorite(this, <?php echo $favorite['problem_id']; ?>)" class="btn btn-remove">
                            <i class="fas fa-trash"></i> Retirer
                        </button>
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
    // Fonction pour supprimer un favori
    function removeFavorite(button, problemId) {
        if (confirm('Êtes-vous sûr de vouloir retirer ce problème de vos favoris?')) {
            // Effet visuel immédiat
            const card = button.closest('.favorite-card');
            card.style.opacity = '0.5';
            
            // Envoyer la requête AJAX
            fetch('toggle_favorite.php?problem_id=' + problemId + '&ajax=1', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Animation de suppression
                    card.style.height = card.offsetHeight + 'px';
                    card.style.overflow = 'hidden';
                    setTimeout(() => {
                        card.style.height = '0';
                        card.style.padding = '0';
                        card.style.margin = '0';
                        setTimeout(() => {
                            card.remove();
                            
                            // Si plus aucun favori, afficher le message \"Aucun favori trouvé\"
                            const favoritesList = document.querySelector('.favorites-list');
                            if (favoritesList && favoritesList.children.length === 0) {
                                const container = document.querySelector('.content');
                                favoritesList.remove();
                                
                                const noFavorites = document.createElement('div');
                                noFavorites.className = 'no-favorites';
                                noFavorites.innerHTML = `
                                    <h3>Aucun favori trouvé</h3>
                                    <p>Ajoutez des problèmes à vos favoris pour les voir ici.</p>
                                    <a href=\"problems.php\" class=\"btn btn-primary\">
                                        <i class=\"fas fa-search\"></i> Parcourir les problèmes
                                    </a>
                                `;
                                container.appendChild(noFavorites);
                            }
                        }, 300);
                    }, 10);
                } else {
                    // Erreur, rétablir l'apparence
                    card.style.opacity = '1';
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                // Erreur, rétablir l'apparence
                card.style.opacity = '1';
                console.error('Erreur lors de la suppression du favori:', error);
                alert('Une erreur est survenue lors de la communication avec le serveur.');
            });
        }
    }
";

// Include footer
include 'footer.php';
?>