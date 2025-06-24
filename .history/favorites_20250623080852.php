<?php
// Vérifications et redirections AVANT tout output
session_start();

// Fonction pour vérifier si l'utilisateur est connecté (si pas définie ailleurs)
if (!function_exists('isLoggedIn')) {
    function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

// Rediriger si l'utilisateur n'est pas connecté AVANT tout output
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

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

    .debug-info {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 5px;
        padding: 15px;
        margin: 20px 0;
        font-family: monospace;
        font-size: 12px;
    }

    .debug-info h4 {
        margin-top: 0;
        color: #495057;
    }

    .debug-info pre {
        background: white;
        padding: 10px;
        border-radius: 3px;
        overflow-x: auto;
    }
";

// Récupérer l'ID de l'utilisateur connecté
$user_id = $_SESSION['user_id'];

// Récupérer les paramètres de pagination
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$problems_per_page = 10;

// Variables de débogage
$debug_info = [];
$debug_mode = isset($_GET['debug']) && $_GET['debug'] == '1';

// Inclure les fonctions de base de données si nécessaire
if (!function_exists('connect')) {
    include_once 'config.php'; // ou le fichier contenant la fonction connect()
}

try {
    $pdo = connect();
    
    if (!$pdo) {
        throw new Exception("Erreur de connexion à la base de données");
    }
    
    // DEBUG: Vérifier la structure des tables
    if ($debug_mode) {
        // Vérifier la table favorites
        $debug_info['favorites_structure'] = [];
        try {
            $stmt_debug = $pdo->query("DESCRIBE favorites");
            $debug_info['favorites_structure'] = $stmt_debug->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $debug_info['favorites_structure_error'] = $e->getMessage();
        }
        
        // Vérifier la table problems
        $debug_info['problems_structure'] = [];
        try {
            $stmt_debug = $pdo->query("DESCRIBE problems");
            $debug_info['problems_structure'] = $stmt_debug->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $debug_info['problems_structure_error'] = $e->getMessage();
        }
        
        // Vérifier la table users
        $debug_info['users_structure'] = [];
        try {
            $stmt_debug = $pdo->query("DESCRIBE users");
            $debug_info['users_structure'] = $stmt_debug->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $debug_info['users_structure_error'] = $e->getMessage();
        }
        
        // Compter les enregistrements dans chaque table
        try {
            $stmt_debug = $pdo->query("SELECT COUNT(*) as count FROM favorites");
            $debug_info['favorites_count'] = $stmt_debug->fetchColumn();
        } catch (Exception $e) {
            $debug_info['favorites_count_error'] = $e->getMessage();
        }
        
        try {
            $stmt_debug = $pdo->query("SELECT COUNT(*) as count FROM problems");
            $debug_info['problems_count'] = $stmt_debug->fetchColumn();
        } catch (Exception $e) {
            $debug_info['problems_count_error'] = $e->getMessage();
        }
        
        // Vérifier les favoris pour cet utilisateur
        try {
            $stmt_debug = $pdo->prepare("SELECT * FROM favorites WHERE user_id = ? LIMIT 5");
            $stmt_debug->execute([$user_id]);
            $debug_info['user_favorites_sample'] = $stmt_debug->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $debug_info['user_favorites_error'] = $e->getMessage();
        }
    }
    
    // Compter le nombre total de favoris
    $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ?");
    $stmt_count->execute([$user_id]);
    $total_favorites = $stmt_count->fetchColumn();
    
    if ($debug_mode) {
        $debug_info['total_favorites'] = $total_favorites;
        $debug_info['user_id'] = $user_id;
    }
    
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
    
    // Adapter la requête selon le type de base de données
    $sql = "
        SELECT p.*, u.username, u.avatar_url, f.created_at as favorite_date,
           (SELECT COUNT(*) FROM solutions 
            WHERE solutions.problem_id = p.problem_id 
            AND solutions.user_id = :user_id_solutions 
            AND (solutions.status = 'accepted')) AS is_solved_by_me
        FROM favorites f
        JOIN problems p ON f.problem_id = p.problem_id
        JOIN users u ON p.user_id = u.id
        WHERE f.user_id = :user_id_main
        ORDER BY f.created_at DESC
        LIMIT :limit OFFSET :offset
    ";
    
    // Pour MySQL, utiliser LIMIT avec OFFSET
    if (strpos($pdo->getAttribute(PDO::ATTR_DRIVER_NAME), 'mysql') !== false) {
        $sql = "
            SELECT p.*, u.username, u.avatar_url, f.created_at as favorite_date,
               (SELECT COUNT(*) FROM solutions 
                WHERE solutions.problem_id = p.problem_id 
                AND solutions.user_id = :user_id_solutions 
                AND (solutions.status = 'accepted')) AS is_solved_by_me
            FROM favorites f
            JOIN problems p ON f.problem_id = p.problem_id
            JOIN users u ON p.user_id = u.id
            WHERE f.user_id = :user_id_main
            ORDER BY f.created_at DESC
            LIMIT :limit OFFSET :offset
        ";
    }
    
    if ($debug_mode) {
        $debug_info['main_query'] = $sql;
        $debug_info['query_params'] = [
            'user_id_solutions' => $user_id,
            'user_id_main' => $user_id,
            'limit' => $problems_per_page,
            'offset' => $offset
        ];
        $debug_info['database_driver'] = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':user_id_solutions', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':user_id_main', $user_id, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $problems_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($debug_mode) {
        $debug_info['favorites_result_count'] = count($favorites);
        $debug_info['favorites_sample'] = array_slice($favorites, 0, 2); // Premiers 2 résultats
        
        // Vérifier s'il y a des problèmes qui ne sont pas dans les favoris
        $stmt_all_problems = $pdo->prepare("
            SELECT p.problem_id, p.title, 
                   CASE WHEN f.problem_id IS NOT NULL THEN 1 ELSE 0 END as is_favorite
            FROM problems p
            LEFT JOIN favorites f ON p.problem_id = f.problem_id AND f.user_id = ?
            ORDER BY p.problem_id
            LIMIT 10
        ");
        $stmt_all_problems->execute([$user_id]);
        $debug_info['problems_favorite_status'] = $stmt_all_problems->fetchAll(PDO::FETCH_ASSOC);
    }
    
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des favoris: " . $e->getMessage());
    $favorites = [];
    $total_pages = 0;
    $current_page = 1;
    
    if ($debug_mode) {
        $debug_info['main_error'] = $e->getMessage();
        $debug_info['error_trace'] = $e->getTraceAsString();
    }
}

// MAINTENANT inclure le header après toutes les vérifications
include 'header.php';
?>

<h1><i class="fas fa-star"></i> Mes Favoris</h1>

<?php if ($debug_mode): ?>
    <div class="debug-info">
        <h4>🔍 Informations de débogage</h4>
        <pre><?php echo htmlspecialchars(print_r($debug_info, true)); ?></pre>
        
        <?php if (!empty($debug_info['favorites_structure'])): ?>
            <h5>Structure de la table 'favorites':</h5>
            <pre><?php echo htmlspecialchars(print_r($debug_info['favorites_structure'], true)); ?></pre>
        <?php endif; ?>
        
        <?php if (!empty($debug_info['problems_structure'])): ?>
            <h5>Structure de la table 'problems':</h5>
            <pre><?php echo htmlspecialchars(print_r($debug_info['problems_structure'], true)); ?></pre>
        <?php endif; ?>
        
        <?php if (!empty($debug_info['problems_favorite_status'])): ?>
            <h5>Statut des favoris pour les 10 premiers problèmes:</h5>
            <pre><?php echo htmlspecialchars(print_r($debug_info['problems_favorite_status'], true)); ?></pre>
        <?php endif; ?>
    </div>
    
    <div style="margin: 20px 0; padding: 10px; background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px;">
        <strong>Mode débogage activé.</strong> 
        <a href="?">Désactiver le débogage</a> | 
        <a href="?debug=1">Actualiser avec débogage</a>
    </div>
<?php else: ?>
    <div style="margin: 20px 0; padding: 10px; background: #d1ecf1; border: 1px solid #bee5eb; border-radius: 5px;">
        <strong>Problème avec les favoris ?</strong> 
        <a href="?debug=1">Activer le mode débogage</a> pour diagnostiquer le problème.
    </div>
<?php endif; ?>

<?php if (empty($favorites)): ?>
    <div class="no-favorites">
        <h3>Aucun favori trouvé</h3>
        <p>Ajoutez des problèmes à vos favoris pour les voir ici.</p>
        <?php if ($debug_mode): ?>
            <p><strong>Debug:</strong> Total favoris trouvés: <?php echo $total_favorites; ?></p>
            <p><strong>Debug:</strong> User ID: <?php echo $user_id; ?></p>
        <?php endif; ?>
        <a href="problems.php" class="btn btn-primary">
            <i class="fas fa-search"></i> Parcourir les problèmes
        </a>
    </div>
<?php else: ?>
    <div class="favorites-list">
        <?php foreach ($favorites as $favorite): ?>
            <div class="favorite-card">
                <?php if ($debug_mode): ?>
                    <div style="background: #f8f9fa; padding: 5px; margin-bottom: 10px; font-size: 11px; border-radius: 3px;">
                        <strong>Debug:</strong> Problem ID: <?php echo $favorite['problem_id']; ?> | 
                        User ID: <?php echo $favorite['user_id']; ?> | 
                        Favorite Date: <?php echo $favorite['favorite_date']; ?>
                    </div>
                <?php endif; ?>
                
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
                        <span class="difficulty-<?php echo htmlspecialchars($favorite['difficulty'] ?? 'unknown'); ?>">
                            <i class="fas fa-signal"></i>
                            <?php 
                            $difficulty_labels = [
                                'easy' => 'Facile',
                                'medium' => 'Moyen',
                                'hard' => 'Difficile'
                            ];
                            echo $difficulty_labels[$favorite['difficulty'] ?? 'unknown'] ?? ($favorite['difficulty'] ?? 'Inconnu');
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
                        <span>
                            <i class="fas fa-user"></i>
                            <?php echo htmlspecialchars($favorite['username'] ?? 'Utilisateur inconnu'); ?>
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

<?php if ($debug_mode): ?>
    <div class="debug-info">
        <h4>🔧 Tests de diagnostic supplémentaires</h4>
        
        <?php
        // Test de connexion à la base de données
        try {
            $test_pdo = connect();
            if ($test_pdo) {
                echo "<p>✅ Connexion à la base de données: OK</p>";
                
                // Test d'existence des tables
                $tables_to_check = ['favorites', 'problems', 'users', 'solutions'];
                foreach ($tables_to_check as $table) {
                    try {
                        $stmt = $test_pdo->query("SELECT 1 FROM $table LIMIT 1");
                        echo "<p>✅ Table '$table': Existe</p>";
                    } catch (Exception $e) {
                        echo "<p>❌ Table '$table': Erreur - " . htmlspecialchars($e->getMessage()) . "</p>";
                    }
                }
                
                // Test de la requête de base pour les favoris
                try {
                    $test_stmt = $test_pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ?");
                    $test_stmt->execute([$user_id]);
                    $count = $test_stmt->fetchColumn();
                    echo "<p>✅ Requête favoris de base: $count favoris trouvés</p>";
                } catch (Exception $e) {
                    echo "<p>❌ Requête favoris de base: Erreur - " . htmlspecialchars($e->getMessage()) . "</p>";
                }
                
                // Test de la requête JOIN
                try {
                    $test_sql = "
                        SELECT f.problem_id, p.title, u.username 
                        FROM favorites f
                        LEFT JOIN problems p ON f.problem_id = p.problem_id
                        LEFT JOIN users u ON p.user_id = u.id
                        WHERE f.user_id = ?
                        LIMIT 3
                    ";
                    $test_stmt = $test_pdo->prepare($test_sql);
                    $test_stmt->execute([$user_id]);
                    $test_results = $test_stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo "<p>✅ Requête JOIN: " . count($test_results) . " résultats</p>";
                    if (!empty($test_results)) {
                        echo "<pre>" . htmlspecialchars(print_r($test_results, true)) . "</pre>";
                    }
                } catch (Exception $e) {
                    echo "<p>❌ Requête JOIN: Erreur - " . htmlspecialchars($e->getMessage()) . "</p>";
                }
                
                // Vérifier les clés étrangères
                try {
                    $orphan_favorites = $test_pdo->prepare("
                        SELECT f.*, p.problem_id as problem_exists 
                        FROM favorites f 
                        LEFT JOIN problems p ON f.problem_id = p.problem_id 
                        WHERE f.user_id = ? AND p.problem_id IS NULL
                    ");
                    $orphan_favorites->execute([$user_id]);
                    $orphans = $orphan_favorites->fetchAll(PDO::FETCH_ASSOC);
                    if (empty($orphans)) {
                        echo "<p>✅ Intégrité des données: Aucun favori orphelin</p>";
                    } else {
                        echo "<p>⚠️ Intégrité des données: " . count($orphans) . " favoris orphelins trouvés</p>";
                        echo "<pre>" . htmlspecialchars(print_r($orphans, true)) . "</pre>";
                    }
                } catch (Exception $e) {
                    echo "<p>❌ Test d'intégrité: Erreur - " . htmlspecialchars($e->getMessage()) . "</p>";
                }
                
            } else {
                echo "<p>❌ Connexion à la base de données: ÉCHEC</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Test de connexion: Erreur - " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        ?>
        
        <h5>Variables de session:</h5>
        <pre><?php echo htmlspecialchars(print_r($_SESSION, true)); ?></pre>
        
        <h5>Informations sur l'utilisateur connecté:</h5>
        <p>User ID: <?php echo $user_id; ?></p>
        <p>Est connecté: <?php echo isLoggedIn() ? 'Oui' : 'Non'; ?></p>
    </div>
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

    // Fonction de débogage pour tester l'ajout de favoris
    function testAddFavorite(problemId) {
        console.log('Test d\'ajout de favori pour le problème ID:', problemId);
        
        fetch('toggle_favorite.php?problem_id=' + problemId + '&ajax=1&debug=1', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(data => {
            console.log('Réponse du serveur:', data);
            try {
                const jsonData = JSON.parse(data);
                console.log('Données JSON:', jsonData);
            } catch (e) {
                console.log('Réponse non-JSON:', data);
            }
        })
        .catch(error => {
            console.error('Erreur lors du test:', error);
        });
    }

    // Ajouter un bouton de test si en mode debug
    " . ($debug_mode ? "
    document.addEventListener('DOMContentLoaded', function() {
        const debugInfo = document.querySelector('.debug-info');
        if (debugInfo) {
            const testButton = document.createElement('button');
            testButton.textContent = 'Tester l\\'ajout de favori (ID: 1)';
            testButton.onclick = () => testAddFavorite(1);
            testButton.style.margin = '10px 0';
            testButton.className = 'btn btn-primary';
            debugInfo.appendChild(testButton);
        }
    });
    " : "") . "
";

// Include footer
include 'footer.php';
?>
