<?php
// Démarrer la session
session_start();

// Inclure les fichiers nécessaires
require_once 'verification.php';
require_once 'db_connect.php';
require_once 'exmenu.php';

// Récupérer les paramètres de filtrage et pagination
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$difficulty = isset($_GET['difficulty']) ? $_GET['difficulty'] : '';
$language = isset($_GET['language']) ? $_GET['language'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';
$favorites_only = isset($_GET['favorites']) && $_GET['favorites'] === '1';

// Nombre de problèmes par page
$problems_per_page = 10;

// Liste des langages disponibles
$languages = [
    'python' => 'Python',
    'java' => 'Java',
    'javascript' => 'JavaScript',
    'c' => 'C',
    'cpp' => 'C++',
    'csharp' => 'C#',
    'php' => 'PHP',
    'ruby' => 'Ruby',
    'swift' => 'Swift',
    'go' => 'Go',
    'rust' => 'Rust',
    'kotlin' => 'Kotlin',
    'typescript' => 'TypeScript',
    'sql' => 'SQL',
    'html' => 'HTML/CSS',
    'other' => 'Autre'
];

try {
    $pdo = connect();
    
    if (!$pdo) {
        throw new Exception("Erreur de connexion à la base de données");
    }
    
    // Construire la requête SQL avec les filtres
    if ($favorites_only && isLoggedIn()) {
        // Requête pour afficher uniquement les favoris
        $sql_count = "
            SELECT COUNT(*) 
            FROM problems p
            JOIN favorites f ON p.id = f.problem_id
            WHERE f.user_id = ?
        ";
        
        $sql = "
            SELECT p.*, u.username, u.name as author_name,
                   1 as is_favorite
            FROM problems p
            JOIN favorites f ON p.id = f.problem_id
            JOIN users u ON p.user_id = u.id
            WHERE f.user_id = ?
        ";
        
        $params = [$_SESSION['user_id']];
    } else {
        // Requête standard pour tous les problèmes
        $sql_count = "SELECT COUNT(*) FROM problems WHERE 1=1";
        $sql = "
            SELECT p.*, u.username, u.name as author_name,
                   (SELECT COUNT(*) FROM favorites WHERE problem_id = p.id AND user_id = ?) as is_favorite
            FROM problems p
            JOIN users u ON p.user_id = u.id
            WHERE 1=1
        ";
        
        $params = [isLoggedIn() ? $_SESSION['user_id'] : 0];
    }
    
    // Appliquer les filtres supplémentaires
    if (!empty($difficulty)) {
        $sql .= " AND p.difficulty = ?";
        $sql_count .= " AND difficulty = ?";
        $params[] = $difficulty;
    }
    
    if (!empty($language)) {
        $sql .= " AND p.language = ?";
        $sql_count .= " AND language = ?";
        $params[] = $language;
    }
    
    if (!empty($search)) {
        $sql .= " AND (p.title LIKE ? OR p.tags LIKE ?)";
        $sql_count .= " AND (title LIKE ? OR tags LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    // Compter le nombre total de problèmes
    $stmt_count = $pdo->prepare($sql_count);
    $stmt_count->execute($params);
    $total_problems = $stmt_count->fetchColumn();
    
    // Calculer le nombre total de pages
    $total_pages = ceil($total_problems / $problems_per_page);
    
    // S'assurer que la page courante est valide
    if ($current_page < 1) {
        $current_page = 1;
    } elseif ($current_page > $total_pages && $total_pages > 0) {
        $current_page = $total_pages;
    }
    
    // Calculer l'offset pour la pagination
    $offset = ($current_page - 1) * $problems_per_page;
    
    // Ajouter l'ordre et la pagination à la requête
    $sql .= " ORDER BY p.created_at DESC OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
    $params[] = $offset;
    $params[] = $problems_per_page;
    
    // Exécuter la requête
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $problems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des problèmes: " . $e->getMessage());
    $problems = [];
    $total_pages = 0;
    $current_page = 1;
}

// Récupérer le message de succès s'il existe
$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Problèmes de Programmation</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            background: linear-gradient(135deg, #f5f7fa, #e4e8f0);
            color: #333;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        h1 {
            color: #2c3e50;
            margin: 0;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #3498db;
            color: white;
            border: none;
        }

        .btn-primary:hover {
            background: #2980b9;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .filters {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .filter-form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #2c3e50;
        }

        .filter-group select,
        .filter-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
        }

        .btn-filter {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-reset {
            background: transparent;
            color: #3498db;
            border: 1px solid #3498db;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
        }

        .problems-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .problem-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .problem-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .problem-header {
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
        }

        .problem-title {
            margin: 0;
            font-size: 18px;
        }

        .problem-title a {
            color: #2c3e50;
            text-decoration: none;
        }

        .problem-title a:hover {
            color: #3498db;
        }

        .problem-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            padding: 15px;
            font-size: 13px;
            color: #7f8c8d;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .difficulty {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 15px;
            font-weight: 600;
            font-size: 12px;
            text-transform: uppercase;
        }

        .difficulty.easy { background: #d5f5e3; color: #27ae60; }
        .difficulty.medium { background: #fef9e7; color: #f39c12; }
        .difficulty.hard { background: #fdedec; color: #e74c3c; }

        .problem-footer {
            padding: 15px;
            border-top: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .points {
            font-weight: 600;
            color: #3498db;
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

        .no-problems {
            background: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .no-problems h3 {
            color: #2c3e50;
            margin-top: 0;
        }

        .no-problems p {
            color: #7f8c8d;
        }

        @media (max-width: 768px) {
            .container {
                margin: 20px;
            }
            
            .page-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            
            .filter-form {
                flex-direction: column;
            }
            
            .filter-group {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php 
    // Afficher l'avatar utilisateur
    require_once('exavatar.php');
    displayUserAvatar();
    ?>
    
    <div class="container">
        <div class="page-header">
            <h1>Problèmes de Programmation</h1>
            <?php if (isLoggedIn()): ?>
                <a href="expublier.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Publier un Problème
                </a>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
                  <div class="filters">
                      <form action="" method="GET" class="filter-form">
                          <div class="filter-group">
                              <label for="difficulty">Difficulté</label>
                              <select id="difficulty" name="difficulty">
                                  <option value="">Toutes les difficultés</option>
                                  <option value="easy" <?php echo $difficulty === 'easy' ? 'selected' : ''; ?>>Facile</option>
                                  <option value="medium" <?php echo $difficulty === 'medium' ? 'selected' : ''; ?>>Moyen</option>
                                  <option value="hard" <?php echo $difficulty === 'hard' ? 'selected' : ''; ?>>Difficile</option>
                              </select>
                          </div>
                
                          <div class="filter-group">
                              <label for="language">Langage</label>
                              <select id="language" name="language">
                                  <option value="">Tous les langages</option>
                                  <?php foreach ($languages as $key => $value): ?>
                                      <option value="<?php echo $key; ?>" <?php echo $language === $key ? 'selected' : ''; ?>>
                                          <?php echo $value; ?>
                                      </option>
                                  <?php endforeach; ?>
                              </select>
                          </div>
                
                          <div class="filter-group">
                              <label for="search">Recherche</label>
                              <input type="text" id="search" name="search" placeholder="Titre ou tags..." value="<?php echo htmlspecialchars($search); ?>">
                          </div>
                
                          <?php if (isLoggedIn()): ?>
                          <div class="filter-group">
                              <label for="favorites">Affichage</label>
                              <select id="favorites" name="favorites">
                                  <option value="0" <?php echo !$favorites_only ? 'selected' : ''; ?>>Tous les problèmes</option>
                                  <option value="1" <?php echo $favorites_only ? 'selected' : ''; ?>>Mes favoris uniquement</option>
                              </select>
                          </div>
                          <?php endif; ?>
                
                          <div class="filter-buttons">
                              <button type="submit" class="btn-filter">
                                  <i class="fas fa-filter"></i> Filtrer
                              </button>
                              <a href="problems.php" class="btn-reset">
                                  <i class="fas fa-sync-alt"></i> Réinitialiser
                              </a>
                          </div>
                      </form>
                  </div>
        <?php if (empty($problems)): ?>
            <div class="no-problems">
                <h3>Aucun problème trouvé</h3>
                <p>Essayez de modifier vos filtres ou revenez plus tard.</p>
            </div>
        <?php else: ?>
            <div class="problems-list">
                <?php foreach ($problems as $problem): ?>
                    <div class="problem-card">
                        <div class="problem-header">
                            <h3 class="problem-title">
                                <a href="problem.php?id=<?php echo $problem['id']; ?>">
                                    <?php echo htmlspecialchars($problem['title']); ?>
                                </a>
                            </h3>
                        </div>
                        
                        <div class="problem-meta">
                            <div class="meta-item">
                                <i class="fas fa-user"></i>
                                <?php echo htmlspecialchars($problem['author_name']); ?>
                            </div>
                            <div class="meta-item">
                                <i class="fas fa-code"></i>
                                <?php echo htmlspecialchars(ucfirst($problem['language'])); ?>
                            </div>
                            <div class="meta-item">
                                <span class="difficulty <?php echo htmlspecialchars($problem['difficulty']); ?>">
                                    <?php 
                                    $difficulty_text = '';
                                    switch($problem['difficulty']) {
                                        case 'easy': $difficulty_text = 'Facile'; break;
                                        case 'medium': $difficulty_text = 'Moyen'; break;
                                        case 'hard': $difficulty_text = 'Difficile'; break;
                                        default: $difficulty_text = $problem['difficulty'];
                                    }
                                    echo htmlspecialchars($difficulty_text);
                                    ?>
                                </span>
                            </div>
                        </div>
                                                  <div class="problem-footer">
                                                      <span class="points">
                                                          <i class="fas fa-star"></i> <?php echo htmlspecialchars($problem['points']); ?> points
                                                      </span>
                                                      <div class="problem-actions">
                                                          <?php
                                                          // Vérifier si le problème est déjà en favori
                                                          $stmt_fav = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND problem_id = ?");
                                                          $stmt_fav->execute([$_SESSION['user_id'], $problem['id']]);
                                                          $is_favorite = $stmt_fav->fetch();
                                                          ?>
                                                          <a href="toggle_favorite.php?problem_id=<?php echo $problem['id']; ?>&redirect=problems.php" class="favorite-btn">
                                                              <i class="fas fa-star <?php echo $is_favorite ? 'favorite-active' : ''; ?>"></i>
                                                          </a>
                                                          <a href="problem.php?id=<?php echo $problem['id']; ?>" class="view-problem-btn">
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
                        <a href="?page=<?php echo $current_page - 1; ?>&difficulty=<?php echo urlencode($difficulty); ?>&language=<?php echo urlencode($language); ?>&search=<?php echo urlencode($search); ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>
                    
                    <?php for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++): ?>
                        <?php if ($i == $current_page): ?>
                            <span><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>&difficulty=<?php echo urlencode($difficulty); ?>&language=<?php echo urlencode($language); ?>&search=<?php echo urlencode($search); ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($current_page < $total_pages): ?>
                        <a href="?page=<?php echo $current_page + 1; ?>&difficulty=<?php echo urlencode($difficulty); ?>&language=<?php echo urlencode($language); ?>&search=<?php echo urlencode($search); ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
