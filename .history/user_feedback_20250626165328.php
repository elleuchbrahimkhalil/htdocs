<?php
// ===== TRAITEMENT POST EN PREMIER (AVANT TOUT AFFICHAGE) =====

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure les fichiers nécessaires
require_once 'verification.php';

// Vérifier la connexion
if (!isLoggedIn()) {
    header('Location: exlogin.php');
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('Location: exlogin.php');
    exit;
}

// ===== TRAITEMENT DES ACTIONS POST (AVANT TOUT HTML) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Nettoyer le buffer de sortie
    if (ob_get_level()) {
        ob_clean();
    }
    
    try {
        require_once 'db_connect.php';
        $pdo = connect();
        
        if (!$pdo) {
            throw new Exception("Impossible de se connecter à la base de données");
        }
        
        // ===== ACCEPTATION DE SOLUTION =====
        if (isset($_POST['accept_solution'])) {
            $solution_id = isset($_POST['solution_id']) ? (int)$_POST['solution_id'] : 0;
            
            if ($solution_id > 0) {
                // Vérifier que la solution existe
                $stmt = $pdo->prepare("
                    SELECT s.*, p.user_id as problem_owner, p.points
                    FROM solutions s
                    INNER JOIN problems p ON s.problem_id = p.problem_id
                    WHERE s.id = ? AND p.user_id = ?
                ");
                
                $stmt->execute([$solution_id, $user_id]);
                $solution = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($solution) {
                    $current_status = strtolower($solution['status']);
                    
                    if ($current_status === 'pending') {
                        $pdo->beginTransaction();
                        
                        try {
                            // Mettre à jour le statut
                            $stmt = $pdo->prepare("
                                UPDATE solutions
                                SET status = 'Accepted', evaluated_at = GETDATE()
                                WHERE id = ?
                            ");
                            
                            $result = $stmt->execute([$solution_id]);
                            
                            if ($result && $stmt->rowCount() > 0) {
                                // Mettre à jour les points
                                $stmt = $pdo->prepare("
                                    UPDATE users
                                    SET score = score + ?, problems_solved = problems_solved + 1
                                    WHERE id = ?
                                ");
                                $stmt->execute([$solution['points'], $solution['user_id']]);
                                
                                $pdo->commit();
                                
                                // ===== REDIRECTION GARANTIE (PAS DE HTML AVANT) =====
                                $_SESSION['payment_message'] = "Solution acceptée avec succès !";
                                $_SESSION['payment_solution_id'] = $solution_id;
                                
                                header("Location: payment.php?solution_id=$solution_id&from=accept");
                                exit; // ARRÊT COMPLET ICI
                                
                            } else {
                                $pdo->rollback();
                                $_SESSION['error_message'] = "Impossible de mettre à jour le statut de la solution.";
                            }
                            
                        } catch (Exception $e) {
                            $pdo->rollback();
                            $_SESSION['error_message'] = "Erreur lors de la transaction: " . $e->getMessage();
                            error_log("Erreur transaction acceptation: " . $e->getMessage());
                        }
                        
                    } else {
                        $_SESSION['error_message'] = "Cette solution a déjà été évaluée (statut: " . $solution['status'] . ").";
                    }
                } else {
                    $_SESSION['error_message'] = "Solution introuvable ou non autorisée.";
                }
            } else {
                $_SESSION['error_message'] = "ID de solution invalide.";
            }
            
            // Redirection après erreur
            $filter = $_POST['filter'] ?? 'all';
            $page = $_POST['page'] ?? 1;
            header("Location: " . $_SERVER['PHP_SELF'] . "?filter=$filter&page=$page");
            exit;
        }
        
        // ===== REJET DE SOLUTION =====
        if (isset($_POST['reject_solution'])) {
            $solution_id = isset($_POST['solution_id']) ? (int)$_POST['solution_id'] : 0;
            
            if ($solution_id > 0) {
                $stmt = $pdo->prepare("
                    SELECT s.*, p.user_id as problem_owner
                    FROM solutions s
                    INNER JOIN problems p ON s.problem_id = p.problem_id
                    WHERE s.id = ? AND p.user_id = ?
                ");
                
                $stmt->execute([$solution_id, $user_id]);
                $solution = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($solution) {
                    $current_status = strtolower($solution['status']);
                    
                    if ($current_status === 'pending') {
                        $stmt = $pdo->prepare("
                            UPDATE solutions
                            SET status = 'Rejected', evaluated_at = GETDATE()
                            WHERE id = ?
                        ");
                        
                        $result = $stmt->execute([$solution_id]);
                        
                        if ($result) {
                            $_SESSION['success_message'] = "La solution a été rejetée avec succès.";
                        } else {
                            $_SESSION['error_message'] = "Erreur lors du rejet de la solution.";
                        }
                    } else {
                        $_SESSION['error_message'] = "Cette solution a déjà été évaluée.";
                    }
                } else {
                    $_SESSION['error_message'] = "Solution introuvable.";
                }
            } else {
                $_SESSION['error_message'] = "ID de solution invalide.";
            }
            
            // Redirection après rejet
            $filter = $_POST['filter'] ?? 'all';
            $page = $_POST['page'] ?? 1;
            header("Location: " . $_SERVER['PHP_SELF'] . "?filter=$filter&page=$page");
            exit;
        }
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Erreur: " . $e->getMessage();
        error_log("Erreur POST user_feedback.php: " . $e->getMessage());
        
        $filter = $_POST['filter'] ?? 'all';
        $page = $_POST['page'] ?? 1;
        header("Location: " . $_SERVER['PHP_SELF'] . "?filter=$filter&page=$page");
        exit;
    }
}

// ===== RÉCUPÉRATION DES MESSAGES DEPUIS LA SESSION =====
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';

// Nettoyer les messages de la session après récupération
unset($_SESSION['success_message'], $_SESSION['error_message']);

// ===== MAINTENANT ON PEUT COMMENCER L'AFFICHAGE =====

// Set page title
$page_title = "Notifications des Problèmes";

// Additional CSS specific to this page
$additional_css = "
    .solution-card {
        border: 1px solid #ddd;
        border-radius: 12px;
        padding: 20px;
        background-color: white;
        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        transition: transform 0.3s, box-shadow 0.3s;
        margin-bottom: 20px;
    }

    .solution-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.12);
    }

    .solution-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
        border-bottom: 1px solid #f0f0f0;
        padding-bottom: 15px;
    }

    .solution-title {
        font-weight: bold;
        font-size: 1.2em;
        color: #2c3e50;
        margin: 0;
    }

    .solution-price {
        font-weight: bold;
        color: #4CAF50;
        font-size: 1.2em;
    }

    .solution-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 15px;
        font-size: 0.9em;
        color: #7f8c8d;
    }

    .solution-meta span {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .solution-content {
        margin-bottom: 15px;
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        font-family: 'Courier New', monospace;
        overflow-x: auto;
        border: 1px solid #e0e0e0;
        max-height: 300px;
        overflow-y: auto;
    }

    .solution-explanation {
        margin-bottom: 15px;
        padding: 15px;
        background-color: #f0f7fb;
        border-radius: 8px;
        border-left: 4px solid #3498db;
    }

    .solution-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid #f0f0f0;
        padding-top: 15px;
        flex-wrap: wrap;
        gap: 10px;
    }

    .solution-status {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 5px 10px;
        border-radius: 15px;
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
    }

    .solution-status.pending {
        background: #fef9e7;
        color: #f39c12;
    }

    .solution-status.accepted {
        background: #d5f5e3;
        color: #27ae60;
    }

    .solution-status.rejected {
        background: #fdedec;
        color: #e74c3c;
    }

    .solution-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .btn {
        padding: 8px 16px;
        border-radius: 4px;
        text-decoration: none;
        font-size: 14px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .btn-primary {
        background-color: #4CAF50;
        color: white;
    }

    .btn-primary:hover {
        background-color: #45a049;
        transform: translateY(-2px);
    }

    .btn-outline {
        background-color: transparent;
        color: #e74c3c;
        border: 1px solid #e74c3c;
    }

    .btn-outline:hover {
        background-color: #e74c3c;
        color: white;
    }

    .empty-state {
        text-align: center;
        padding: 40px 20px;
        background-color: white;
        border-radius: 12px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
    }

    .empty-state i {
        font-size: 3em;
        color: #ddd;
        margin-bottom: 20px;
    }

    .empty-state h3 {
        color: #2c3e50;
        margin-bottom: 10px;
    }

    .empty-state p {
        color: #7f8c8d;
        margin-bottom: 20px;
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

    .filter-section {
        background: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .filter-row {
        display: flex;
        gap: 15px;
        align-items: center;
        flex-wrap: wrap;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .filter-group label {
        font-weight: 600;
        color: #333;
        font-size: 14px;
    }

    .filter-group select {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }

    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border: 1px solid transparent;
        border-radius: 4px;
    }

    .alert-success {
        color: #155724;
        background-color: #d4edda;
        border-color: #c3e6cb;
    }

    .alert-danger {
        color: #721c24;
        background-color: #f8d7da;
        border-color: #f5c6cb;
    }

    .status-indicator {
        display: inline-block;
        width: 8px;
        height: 8px;
        border-radius: 50%;
        margin-right: 5px;
    }

    .status-indicator.pending {
        background-color: #f39c12;
        animation: pulse 2s infinite;
    }

    .status-indicator.accepted {
        background-color: #27ae60;
    }

    .status-indicator.rejected {
        background-color: #e74c3c;
    }

    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }
";

// Include header
include 'header.php';

// Initialiser les variables
$received_solutions = [];
$debug_info = [];

// Paramètres de filtrage et pagination
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;
$total_count = 0;
$total_pages = 0;

// Mode debug
$debug_mode = isset($_GET['debug']) && $_GET['debug'] == '1';

try {
    // Inclure le fichier de connexion
    require_once 'db_connect.php';
    
    $pdo = connect();
    
    if (!$pdo) {
        throw new Exception("Impossible de se connecter à la base de données");
    }
    
    $debug_info[] = "✅ Connexion à la base de données réussie";
    $debug_info[] = "👤 User ID: $user_id";
    $debug_info[] = "🔍 Filtre: $filter";
    $debug_info[] = "📄 Page: $page";
    $debug_info[] = "📊 Éléments par page: $per_page";
    $debug_info[] = "📍 Offset: $offset";
    
    // Vérifier d'abord si l'utilisateur a des problèmes publiés
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM problems WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user_problems_count = $stmt->fetchColumn();
    
    $debug_info[] = "📝 Problèmes publiés par l'utilisateur: $user_problems_count";
    
    if ($user_problems_count == 0) {
        $debug_info[] = "ℹ️ Aucun problème publié par cet utilisateur";
    } else {
        // Construire la requête avec filtres - SQL Server compatible
        $where_clause = "WHERE p.user_id = ? AND s.user_id != ?";
        $params = [$user_id, $user_id];
        
        if ($filter !== 'all') {
            $where_clause .= " AND LOWER(s.status) = ?";
            $params[] = strtolower($filter);
        }
        
        $debug_info[] = "🔍 Clause WHERE: $where_clause";
        $debug_info[] = "📊 Paramètres: " . implode(', ', $params);
        
        // Compter le total pour la pagination
        $count_query = "
            SELECT COUNT(*) as total
            FROM solutions s
            INNER JOIN problems p ON s.problem_id = p.problem_id
            INNER JOIN users u ON s.user_id = u.id
            LEFT JOIN prices pr ON s.price_id = pr.price_id
            $where_clause
        ";
        
        $debug_info[] = "📊 Requête de comptage: " . str_replace("\n", " ", $count_query);
        
        $count_stmt = $pdo->prepare($count_query);
        $count_stmt->execute($params);
        $count_result = $count_stmt->fetch(PDO::FETCH_ASSOC);
        $total_count = $count_result['total'] ?? 0;
        $total_pages = ceil($total_count / $per_page);
        
        $debug_info[] = "📈 Total solutions: $total_count";
        $debug_info[] = "📄 Total pages: $total_pages";
        
        if ($total_count > 0) {
            // Requête principale avec pagination
            $main_query = "
                SELECT 
                    s.id,
                    s.problem_id,
                    s.user_id,
                    s.solution_code,
                    s.explanation,
                    s.status,
                    s.created_at,
                    s.evaluated_at,
                    s.price_id,
                    p.title as problem_title,
                    p.points,
                    u.username,
                    u.name as solver_name,
                    pr.amount as price,
                    pr.currency
                FROM solutions s
                INNER JOIN problems p ON s.problem_id = p.problem_id
                INNER JOIN users u ON s.user_id = u.id
                LEFT JOIN prices pr ON s.price_id = pr.price_id
                $where_clause
                ORDER BY s.created_at DESC
                OFFSET $offset ROWS
                FETCH NEXT $per_page ROWS ONLY
            ";
            
            $debug_info[] = "🔍 Requête principale: " . str_replace("\n", " ", $main_query);
            
            $stmt = $pdo->prepare($main_query);
            $stmt->execute($params);
            $received_solutions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $debug_info[] = "✅ Solutions récupérées: " . count($received_solutions);
            
            // Alternative si OFFSET/FETCH ne fonctionne pas
            if (empty($received_solutions) && $total_count > 0) {
                $debug_info[] = "⚠️ OFFSET/FETCH n'a pas fonctionné, essai avec méthode alternative";
                
                // Méthode alternative: LIMIT/OFFSET classique
                $simple_query = "
                    SELECT 
                        s.id,
                        s.problem_id,
                        s.user_id,
                        s.solution_code,
                        s.explanation,
                        s.status,
                        s.created_at,
                        s.evaluated_at,
                        s.price_id,
                        p.title as problem_title,
                        p.points,
                        u.username,
                        u.name as solver_name,
                        pr.amount as price,
                        pr.currency
                    FROM solutions s
                    INNER JOIN problems p ON s.problem_id = p.problem_id
                    INNER JOIN users u ON s.user_id = u.id
                    LEFT JOIN prices pr ON s.price_id = pr.price_id
                    $where_clause
                    ORDER BY s.created_at DESC
                    LIMIT $per_page OFFSET $offset
                ";
                
                $debug_info[] = "🔍 Requête simple: " . str_replace("\n", " ", $simple_query);
                
                try {
                    $stmt = $pdo->prepare($simple_query);
                    $stmt->execute($params);
                    $received_solutions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $debug_info[] = "✅ Solutions récupérées avec LIMIT/OFFSET: " . count($received_solutions);
                } catch (Exception $e) {
                    $debug_info[] = "❌ LIMIT/OFFSET échoué aussi: " . $e->getMessage();
                    
                    // Dernière méthode: récupérer tout et utiliser array_slice
                    if ($total_count <= 1000) {
                        $all_query = str_replace("ORDER BY s.created_at DESC LIMIT $per_page OFFSET $offset", "ORDER BY s.created_at DESC", $simple_query);
                        $stmt = $pdo->prepare($all_query);
                        $stmt->execute($params);
                        $all_solutions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        $received_solutions = array_slice($all_solutions, $offset, $per_page);
                        $debug_info[] = "✅ Solutions récupérées avec array_slice: " . count($received_solutions);
                    }
                }
            }
        } else {
            $debug_info[] = "ℹ️ Aucune solution trouvée avec les critères actuels";
        }
    }
    
} catch (PDOException $e) {
    $error_message = "Erreur de base de données: " . $e->getMessage();
    $debug_info[] = "❌ Erreur PDO: " . $e->getMessage();
    $debug_info[] = "📍 Code erreur: " . $e->getCode();
    error_log("Erreur PDO dans user_feedback.php: " . $e->getMessage());
} catch (Exception $e) {
    $error_message = "Erreur: " . $e->getMessage();
    $debug_info[] = "❌ Erreur générale: " . $e->getMessage();
    error_log("Erreur dans user_feedback.php: " . $e->getMessage());
}
?>

<h1><i class="fas fa-bell"></i> Notifications des Solutions</h1>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
    </div>
<?php endif; ?>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
    </div>
<?php endif; ?>

<?php if ($debug_mode && !empty($debug_info)): ?>
    <div class="debug-info">
        <h4>🔍 Informations de débogage</h4>
        <?php foreach ($debug_info as $info): ?>
            <div><?php echo htmlspecialchars($info); ?></div>
        <?php endforeach; ?>
        <div style="margin-top: 10px;">
            <a href="?filter=<?php echo urlencode($filter); ?>&page=<?php echo $page; ?>" class="btn btn-outline" style="font-size: 12px;">Désactiver le débogage</a>
        </div>
    </div>
<?php endif; ?>

<?php if (!$debug_mode): ?>
    <div style="margin-bottom: 20px;">
        <a href="?filter=<?php echo urlencode($filter); ?>&page=<?php echo $page; ?>&debug=1" class="btn btn-outline" style="font-size: 12px;">
            <i class="fas fa-bug"></i> Mode débogage
        </a>
    </div>
<?php endif; ?>

<!-- Section de filtrage -->
<div class="filter-section">
    <form method="GET" action="">
        <?php if ($debug_mode): ?>
            <input type="hidden" name="debug" value="1">
        <?php endif; ?>
        <div class="filter-row">
            <div class="filter-group">
                <label for="filter">Filtrer par statut :</label>
                <select name="filter" id="filter" onchange="this.form.submit()">
                    <option value="all" <?php echo $filter === 'all' ? 'selected' : ''; ?>>Toutes</option>
                    <option value="pending" <?php echo $filter === 'pending' ? 'selected' : ''; ?>>En attente</option>
                    <option value="accepted" <?php echo $filter === 'accepted' ? 'selected' : ''; ?>>Acceptées</option>
                    <option value="rejected" <?php echo $filter === 'rejected' ? 'selected' : ''; ?>>Rejetées</option>
                </select>
            </div>
            <div class="filter-group">
                <span><strong>Total :</strong> <?php echo $total_count; ?> solution(s)</span>
                <?php if ($total_pages > 1): ?>
                    <span><strong>Page :</strong> <?php echo $page; ?> / <?php echo $total_pages; ?></span>
                <?php endif; ?>
            </div>
        </div>
    </form>
</div>

<h2><i class="fas fa-inbox"></i> Solutions reçues pour vos problèmes</h2>

<?php if (empty($received_solutions)): ?>
    <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <h3>Aucune solution trouvée</h3>
        <?php if ($filter === 'all'): ?>
            <p>Vous n'avez pas encore reçu de solutions pour vos problèmes publiés.</p>
            <div style="margin-top: 20px;">
                <a href="expublier.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Publier un problème
                </a>
                <a href="exacueil.php" class="btn btn-outline">
                    <i class="fas fa-home"></i> Retour à l'accueil
                </a>
            </div>
        <?php else: ?>
            <p>Aucune solution avec le statut "<?php echo htmlspecialchars($filter); ?>" trouvée.</p>
            <a href="?filter=all<?php echo $debug_mode ? '&debug=1' : ''; ?>" class="btn btn-primary">Voir toutes les solutions</a>
        <?php endif; ?>
    </div>
<?php else: ?>
    <?php foreach ($received_solutions as $solution): ?>
        <div class="solution-card">
            <div class="solution-header">
                <h3 class="solution-title">
                    <i class="fas fa-file-code"></i> 
                    <?php echo htmlspecialchars($solution['problem_title'] ?? 'Titre non disponible'); ?>
                </h3>
                <span class="solution-price">
                    <i class="fas fa-tag"></i>
                    <?php echo number_format($solution['price'] ?? 0, 2); ?> €
                </span>
            </div>
            
            <div class="solution-meta">
                <span>
                    <i class="fas fa-user"></i> 
                    <?php echo htmlspecialchars($solution['solver_name'] ?? $solution['username'] ?? 'Utilisateur inconnu'); ?>
                </span>
                <span>
                    <i class="fas fa-calendar-alt"></i> 
                    <?php 
                    try {
                        $date = new DateTime($solution['created_at'] ?? date('Y-m-d H:i:s'));
                        echo $date->format('d/m/Y à H:i'); 
                    } catch (Exception $e) {
                        echo 'Date non disponible';
                    }
                    ?>
                </span>
                <?php 
                $current_status = strtolower($solution['status'] ?? 'pending');
                $status_labels = [
                    'pending' => 'En attente',
                    'accepted' => 'Acceptée',
                    'rejected' => 'Rejetée'
                ];
                $status_label = $status_labels[$current_status] ?? htmlspecialchars($solution['status']);
                ?>
                <span class="solution-status <?php echo $current_status; ?>">
                    <span class="status-indicator <?php echo $current_status; ?>"></span>
                    <?php echo $status_label; ?>
                </span>
            </div>
            
            <div class="solution-content">
                <pre><?php echo htmlspecialchars($solution['solution_code'] ?? 'Code non disponible'); ?></pre>
            </div>
            
            <?php if (!empty($solution['explanation'])): ?>
                <div class="solution-explanation">
                    <h4><i class="fas fa-comment-alt"></i> Explication du développeur</h4>
                    <?php echo nl2br(htmlspecialchars($solution['explanation'])); ?>
                </div>
            <?php endif; ?>
            
            <div class="solution-footer">
                <a href="problem.php?id=<?php echo $solution['problem_id']; ?>" class="btn btn-outline">
                    <i class="fas fa-eye"></i> Voir le problème
                </a>
                
                <?php if ($current_status === 'pending'): ?>
                    <div class="solution-actions">
                        <form method="POST" action="" style="display: inline;" id="accept-form-<?php echo $solution['id']; ?>">
                            <input type="hidden" name="solution_id" value="<?php echo $solution['id']; ?>">
                            <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                            <input type="hidden" name="page" value="<?php echo $page; ?>">
                            <button type="button" 
                                    onclick="confirmAndSubmitAccept(<?php echo $solution['id']; ?>, '<?php echo addslashes($solution['problem_title'] ?? ''); ?>', '<?php echo number_format($solution['price'] ?? 0, 2); ?>')" 
                                    class="btn btn-primary">
                                <i class="fas fa-check"></i> Accepter (<?php echo number_format($solution['price'] ?? 0, 2); ?> €)
                            </button>
                        </form>
                        
                        <form method="POST" action="" style="display: inline;" id="reject-form-<?php echo $solution['id']; ?>">
                            <input type="hidden" name="solution_id" value="<?php echo $solution['id']; ?>">
                            <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                            <input type="hidden" name="page" value="<?php echo $page; ?>">
                            <button type="button" 
                                    onclick="confirmAndSubmitReject(<?php echo $solution['id']; ?>, '<?php echo addslashes($solution['problem_title'] ?? ''); ?>')" 
                                    class="btn btn-outline">
                                <i class="fas fa-times"></i> Rejeter
                            </button>
                        </form>
                    </div>
                <?php elseif ($current_status === 'accepted'): ?>
                    <a href="payment.php?solution_id=<?php echo $solution['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-credit-card"></i> Procéder au paiement
                    </a>
                <?php elseif ($current_status === 'rejected'): ?>
                    <span class="btn" style="background-color: #f8d7da; color: #721c24; cursor: default;">
                        <i class="fas fa-times-circle"></i> Solution rejetée
                        <?php if (!empty($solution['evaluated_at'])): ?>
                            <?php 
                            try {
                                $eval_date = new DateTime($solution['evaluated_at']);
                                echo ' le ' . $eval_date->format('d/m/Y');
                            } catch (Exception $e) {
                                // Ignorer l'erreur de date
                            }
                            ?>
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div style="display: flex; justify-content: center; margin-top: 30px; gap: 10px; flex-wrap: wrap;">
            <?php if ($page > 1): ?>
                <a href="?filter=<?php echo urlencode($filter); ?>&page=<?php echo $page - 1; ?><?php echo $debug_mode ? '&debug=1' : ''; ?>" class="btn btn-outline">
                    <i class="fas fa-chevron-left"></i> Précédent
                </a>
            <?php endif; ?>
            
            <?php 
            $start_page = max(1, $page - 2);
            $end_page = min($total_pages, $page + 2);
            
            for ($i = $start_page; $i <= $end_page; $i++): 
            ?>
                <?php if ($i == $page): ?>
                    <span class="btn btn-primary" style="cursor: default;">
                        <?php echo $i; ?>
                    </span>
                <?php else: ?>
                    <a href="?filter=<?php echo urlencode($filter); ?>&page=<?php echo $i; ?><?php echo $debug_mode ? '&debug=1' : ''; ?>" class="btn btn-outline">
                        <?php echo $i; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?filter=<?php echo urlencode($filter); ?>&page=<?php echo $page + 1; ?><?php echo $debug_mode ? '&debug=1' : ''; ?>" class="btn btn-outline">
                    Suivant <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center; margin-top: 15px; color: #666;">
            Page <?php echo $page; ?> sur <?php echo $total_pages; ?> 
            (<?php echo $total_count; ?> solution(s) au total)
        </div>
    <?php endif; ?>
<?php endif; ?>

<script>
// ===== FONCTIONS DE CONFIRMATION ET SOUMISSION =====

function confirmAndSubmitAccept(solutionId, problemTitle, price) {
    const message = 'Accepter cette solution ?\n\n' +
                   '📝 Problème: ' + problemTitle + '\n' +
                   '💰 Prix à payer: ' + price + ' €\n\n' +
                   '⚠️ Vous serez redirigé vers la page de paiement après confirmation.';
    
    if (confirm(message)) {
        const form = document.getElementById('accept-form-' + solutionId);
        const button = form.querySelector('button');
        
        // Désactiver le bouton et changer le texte
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';
        
        // Ajouter le champ accept_solution
        const acceptInput = document.createElement('input');
        acceptInput.type = 'hidden';
        acceptInput.name = 'accept_solution';
        acceptInput.value = '1';
        form.appendChild(acceptInput);
        
        // Soumettre le formulaire
        form.submit();
    }
}

function confirmAndSubmitReject(solutionId, problemTitle) {
    const message = 'Rejeter cette solution ?\n\n' +
                   '📝 Problème: ' + problemTitle + '\n\n' +
                   '⚠️ Cette action est définitive et ne peut pas être annulée.';
    
    if (confirm(message)) {
        const form = document.getElementById('reject-form-' + solutionId);
        const button = form.querySelector('button');
        
        // Désactiver le bouton et changer le texte
        button.disabled = true;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';
        
        // Ajouter le champ reject_solution
        const rejectInput = document.createElement('input');
        rejectInput.type = 'hidden';
        rejectInput.name = 'reject_solution';
        rejectInput.value = '1';
        form.appendChild(rejectInput);
        
        // Soumettre le formulaire
        form.submit();
    }
}

// ===== FONCTIONS UTILITAIRES =====

// Fonction pour afficher des notifications toast
function showToast(message, type = 'info', duration = 5000) {
    const toast = document.createElement('div');
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        font-weight: 600;
        z-index: 9999;
        max-width: 350px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        transform: translateX(100%);
        transition: transform 0.3s ease;
        word-wrap: break-word;
    `;
    
    switch(type) {
        case 'success':
            toast.style.backgroundColor = '#4CAF50';
            toast.innerHTML = '<i class="fas fa-check-circle"></i> ' + message;
            break;
        case 'error':
            toast.style.backgroundColor = '#f44336';
            toast.innerHTML = '<i class="fas fa-exclamation-circle"></i> ' + message;
            break;
        case 'warning':
            toast.style.backgroundColor = '#ff9800';
            toast.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + message;
            break;
        default:
            toast.style.backgroundColor = '#2196F3';
            toast.innerHTML = '<i class="fas fa-info-circle"></i> ' + message;
    }
    
    document.body.appendChild(toast);
    
    // Animation d'entrée
    setTimeout(() => {
        toast.style.transform = 'translateX(0)';
    }, 100);
    
    // Suppression automatique
    setTimeout(() => {
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, duration);
    
    // Permettre la fermeture manuelle
    toast.addEventListener('click', () => {
        toast.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    });
}

// ===== ANIMATIONS ET INTERACTIONS =====

document.addEventListener('DOMContentLoaded', function() {
    // Animation pour les boutons
    document.querySelectorAll('.btn').forEach(button => {
        button.addEventListener('mouseenter', () => {
            if (!button.disabled && button.style.cursor !== 'default') {
                button.style.transform = 'translateY(-2px)';
                button.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
            }
        });
        
        button.addEventListener('mouseleave', () => {
            if (!button.disabled && button.style.cursor !== 'default') {
                button.style.transform = '';
                button.style.boxShadow = '';
            }
        });
    });
    
    // Animation pour les cartes
    const cards = document.querySelectorAll('.solution-card');
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
        
        // Hover effect pour les cartes
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-5px)';
            card.style.boxShadow = '0 8px 15px rgba(0,0,0,0.1)';
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(-3px)';
            card.style.boxShadow = '0 5px 15px rgba(0,0,0,0.08)';
        });
    });
    
    // Afficher les messages existants comme toast
    <?php if (!empty($success_message)): ?>
    showToast('<?php echo addslashes($success_message); ?>', 'success', 8000);
    <?php endif; ?>
    
    <?php if (!empty($error_message)): ?>
    showToast('<?php echo addslashes($error_message); ?>', 'error', 10000);
    <?php endif; ?>
    
    // Vérifier la connectivité
    if (!navigator.onLine) {
        showToast('Vous êtes hors ligne. Certaines fonctionnalités peuvent ne pas fonctionner.', 'warning', 8000);
    }
    
    // Afficher un message si aucune solution n'est trouvée mais que l'utilisateur a des problèmes
    <?php if ($total_count == 0 && isset($user_problems_count) && $user_problems_count > 0): ?>
    showToast('Vous avez publié des problèmes mais n\'avez pas encore reçu de solutions.', 'info', 6000);
    <?php endif; ?>
    
    // Mettre à jour le titre de la page avec les stats
    const totalSolutions = <?php echo $total_count; ?>;
    if (totalSolutions > 0) {
        document.title = 'Notifications (' + totalSolutions + ') - CodeChallenge';
    }
    
    console.log('✅ Page user_feedback.php chargée avec succès');
    console.log('📊 Solutions affichées: ' + cards.length);
    console.log('🔍 Filtre actuel: <?php echo $filter; ?>');
    console.log('📄 Page actuelle: <?php echo $page; ?>');
    console.log('📈 Total solutions: <?php echo $total_count; ?>');
});

// ===== GESTION DES ERREURS =====

// Gestion des erreurs globales
window.addEventListener('error', function(e) {
    console.error('❌ Erreur JavaScript:', e.error);
    <?php if ($debug_mode): ?>
    showToast('Erreur JavaScript: ' + e.message, 'error', 8000);
    <?php endif; ?>
});

window.addEventListener('unhandledrejection', function(e) {
    console.error('❌ Promise rejetée:', e.reason);
    <?php if ($debug_mode): ?>
    showToast('Promise rejetée: ' + e.reason, 'error', 8000);
    <?php endif; ?>
});

// ===== RACCOURCIS CLAVIER =====

document.addEventListener('keydown', function(e) {
    // Ctrl + R pour actualiser
    if (e.ctrlKey && e.key === 'r') {
        e.preventDefault();
        window.location.reload();
    }
    
    // Échap pour fermer les toasts
    if (e.key === 'Escape') {
        const toasts = document.querySelectorAll('[style*="position: fixed"][style*="top: 20px"]');
        toasts.forEach(toast => {
            if (toast.style.transform !== 'translateX(100%)') {
                toast.click();
            }
        });
    }
});

// ===== GESTION DE LA CONNECTIVITÉ =====

window.addEventListener('online', () => {
    showToast('Connexion rétablie', 'success', 3000);
});

window.addEventListener('offline', () => {
    showToast('Connexion perdue', 'warning', 5000);
});

// ===== FONCTION DE COPIE DU CODE =====

function copyToClipboard(text) {
    if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('Code copié dans le presse-papiers', 'success', 2000);
        }).catch(() => {
            showToast('Erreur lors de la copie', 'error', 3000);
        });
    } else {
        // Fallback pour les navigateurs plus anciens
        const textArea = document.createElement('textarea');
        textArea.value = text;
        document.body.appendChild(textArea);
        textArea.select();
        try {
            document.execCommand('copy');
            showToast('Code copié dans le presse-papiers', 'success', 2000);
        } catch (err) {
            showToast('Erreur lors de la copie', 'error', 3000);
        }
        document.body.removeChild(textArea);
    }
}

// Ajouter des boutons de copie pour le code
document.addEventListener('DOMContentLoaded', function() {
    const codeBlocks = document.querySelectorAll('.solution-content pre');
    codeBlocks.forEach(block => {
        const copyBtn = document.createElement('button');
        copyBtn.innerHTML = '<i class="fas fa-copy"></i>';
        copyBtn.className = 'btn btn-outline';
        copyBtn.style.cssText = `
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 5px 8px;
            font-size: 12px;
            opacity: 0.7;
            z-index: 10;
        `;
        copyBtn.title = 'Copier le code';
        copyBtn.onclick = (e) => {
            e.preventDefault();
            copyToClipboard(block.textContent);
        };
        
        const container = block.parentElement;
        container.style.position = 'relative';
        container.appendChild(copyBtn);
        
        // Afficher/masquer le bouton au survol
        container.addEventListener('mouseenter', () => {
            copyBtn.style.opacity = '1';
        });
        container.addEventListener('mouseleave', () => {
            copyBtn.style.opacity = '0.7';
        });
    });
});

// ===== AUTO-REFRESH POUR LES SOLUTIONS EN ATTENTE =====

<?php if ($filter === 'pending' && $total_count > 0): ?>
function enableAutoRefresh(intervalMinutes = 5) {
    const refreshInterval = intervalMinutes * 60 * 1000;
    setTimeout(() => {
        if (document.visibilityState === 'visible' && 
            confirm('Actualiser la page pour voir les nouvelles solutions?')) {
            window.location.reload();
        } else {
            enableAutoRefresh(intervalMinutes); // Redemander plus tard
        }
    }, refreshInterval);
}

// Activer l'auto-refresh pour les solutions en attente
enableAutoRefresh(5);
<?php endif; ?>

// ===== GESTION DES NOTIFICATIONS PUSH =====

<?php if (count($received_solutions) > 0 && $filter === 'pending'): ?>
function requestNotificationPermission() {
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission().then(permission => {
            if (permission === 'granted') {
                showToast('Notifications activées', 'success');
            }
        });
    }
}

// Demander la permission pour les notifications
requestNotificationPermission();
<?php endif; ?>

// ===== SAUVEGARDE DE L'ÉTAT DE LA PAGE =====

function savePageState() {
    const state = {
        filter: '<?php echo $filter; ?>',
        page: <?php echo $page; ?>,
        timestamp: Date.now()
    };
    localStorage.setItem('userFeedbackState', JSON.stringify(state));
}

// Sauvegarder l'état lors des changements
document.addEventListener('DOMContentLoaded', savePageState);

// ===== VALIDATION DES FORMULAIRES =====

function validateForm(form) {
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            field.style.borderColor = '#e74c3c';
            isValid = false;
        } else {
            field.style.borderColor = '';
        }
    });
    
    return isValid;
}

// Appliquer la validation aux formulaires
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function(e) {
        if (!validateForm(this)) {
            e.preventDefault();
            showToast('Veuillez remplir tous les champs requis', 'error');
        }
    });
});

// ===== FONCTION D'EXPORT DES DONNÉES (MODE DEBUG) =====

<?php if ($debug_mode): ?>
function exportSolutions() {
    const solutions = [];
    document.querySelectorAll('.solution-card').forEach(card => {
        const title = card.querySelector('.solution-title').textContent.trim();
        const status = card.querySelector('.solution-status').textContent.trim();
        const developer = card.querySelector('.solution-meta span:first-child').textContent.trim();
        const price = card.querySelector('.solution-price').textContent.trim();
        
        solutions.push({
            title,
            status,
            developer,
            price
        });
    });
    
    const dataStr = JSON.stringify(solutions, null, 2);
    const dataBlob = new Blob([dataStr], {type: 'application/json'});
    const url = URL.createObjectURL(dataBlob);
    
    const link = document.createElement('a');
    link.href = url;
    link.download = 'solutions_' + new Date().toISOString().split('T')[0] + '.json';
    link.click();
    
    URL.revokeObjectURL(url);
    showToast('Données exportées avec succès', 'success');
}

// Ajouter un bouton d'export en mode debug
document.addEventListener('DOMContentLoaded', function() {
    const debugInfo = document.querySelector('.debug-info');
    if (debugInfo) {
        const exportBtn = document.createElement('button');
        exportBtn.textContent = '📊 Exporter les données';
        exportBtn.onclick = exportSolutions;
        exportBtn.className = 'btn btn-outline';
        exportBtn.style.margin = '10px 5px';
        exportBtn.style.fontSize = '12px';
        
        debugInfo.appendChild(exportBtn);
    }
});
<?php endif; ?>

// ===== FONCTION D'IMPRESSION =====

function printPage() {
    // Masquer les éléments non nécessaires pour l'impression
    const elementsToHide = document.querySelectorAll('.btn, .debug-info, .filter-section');
    elementsToHide.forEach(el => el.style.display = 'none');
    
    window.print();
    
    // Restaurer les éléments après impression
    setTimeout(() => {
        elementsToHide.forEach(el => el.style.display = '');
    }, 1000);
}

// Ajouter le raccourci Ctrl+P pour imprimer
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 'p') {
        e.preventDefault();
        printPage();
    }
});

// ===== GESTION DES ERREURS DE CHARGEMENT D'IMAGES =====

document.addEventListener('DOMContentLoaded', function() {
    const images = document.querySelectorAll('img');
    images.forEach(img => {
        img.addEventListener('error', function() {
            this.style.display = 'none';
        });
    });
});

// ===== PERFORMANCE MONITORING =====

if (window.performance) {
    window.addEventListener('load', function() {
        setTimeout(() => {
            const loadTime = window.performance.timing.loadEventEnd - window.performance.timing.navigationStart;
            if (loadTime > 3000) {
                console.warn('⚠️ Page chargée lentement:', loadTime + 'ms');
                <?php if ($debug_mode): ?>
                showToast('Page chargée lentement: ' + (loadTime/1000).toFixed(1) + 's', 'warning', 5000);
                <?php endif; ?>
            }
        }, 0);
    });
}

// ===== FONCTION DE NETTOYAGE AU DÉCHARGEMENT =====

window.addEventListener('beforeunload', function() {
    // Nettoyer les timers et événements
    const timers = window.timers || [];
    timers.forEach(timer => clearTimeout(timer));
    
    // Sauvegarder l'état final
    savePageState();
});

// ===== AMÉLIORATION DE L'ACCESSIBILITÉ =====

document.addEventListener('DOMContentLoaded', function() {
    // Ajouter des attributs ARIA
    const cards = document.querySelectorAll('.solution-card');
    cards.forEach((card, index) => {
        card.setAttribute('role', 'article');
        card.setAttribute('aria-label', 'Solution ' + (index + 1));
    });
    
    // Améliorer la navigation au clavier
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });
});

// ===== FONCTION DE DÉBOGAGE =====

function debugLog(message, data = null) {
    <?php if ($debug_mode): ?>
    console.log('[DEBUG] ' + message, data || '');
    <?php endif; ?>
}

// Logs de débogage
debugLog('Mode debug activé');
debugLog('Filtre actuel', '<?php echo $filter; ?>');
debugLog('Page actuelle', <?php echo $page; ?>);
debugLog('Total solutions', <?php echo $total_count; ?>);

// ===== STYLES CSS SUPPLÉMENTAIRES =====

const additionalStyles = `
    .fade-in {
        animation: fadeIn 0.5s ease-in;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.8);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }
    
    .loading-spinner {
        width: 50px;
        height: 50px;
        border: 5px solid #f3f3f3;
        border-top: 5px solid #4CAF50;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    
    .solution-card.highlight {
        border: 2px solid #4CAF50;
        background: #f0fff4;
    }
    
    .btn.processing {
        pointer-events: none;
        opacity: 0.7;
    }
    
    .tooltip {
        position: relative;
        display: inline-block;
    }
    
    .tooltip .tooltiptext {
        visibility: hidden;
        width: 200px;
        background-color: #333;
        color: #fff;
        text-align: center;
        border-radius: 6px;
        padding: 5px;
        position: absolute;
        z-index: 1;
        bottom: 125%;
        left: 50%;
        margin-left: -100px;
        opacity: 0;
        transition: opacity 0.3s;
        font-size: 12px;
    }
    
    .tooltip:hover .tooltiptext {
        visibility: visible;
        opacity: 1;
    }
    
    @media (max-width: 768px) {
        .solution-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
        
        .solution-footer {
            flex-direction: column;
            align-items: stretch;
        }
        
        .solution-actions {
            justify-content: center;
        }
        
        .filter-row {
            flex-direction: column;
            align-items: stretch;
        }
    }
`;

// Ajouter les styles CSS supplémentaires
const styleSheet = document.createElement('style');
styleSheet.textContent = additionalStyles;
document.head.appendChild(styleSheet);

// ===== INITIALISATION FINALE =====

document.addEventListener('DOMContentLoaded', function() {
    // Marquer la page comme entièrement chargée
    document.body.classList.add('page-loaded');
    
    // Ajouter des classes d'animation aux éléments
    const cards = document.querySelectorAll('.solution-card');
    cards.forEach(card => card.classList.add('fade-in'));
    
    // Initialiser les tooltips si nécessaire
    const tooltipElements = document.querySelectorAll('[title]');
    tooltipElements.forEach(element => {
        element.classList.add('tooltip');
        const tooltipText = document.createElement('span');
        tooltipText.className = 'tooltiptext';
        tooltipText.textContent = element.getAttribute('title');
        element.appendChild(tooltipText);
        element.removeAttribute('title');
    });
    
    debugLog('✅ Initialisation finale terminée');
});

</script>

<?php
// Additional scripts
$additional_scripts = "
    // Scripts supplémentaires si nécessaire
    console.log('📄 Page user_feedback.php entièrement chargée');
    console.log('📊 Statistiques:');
    console.log('  - Solutions affichées: " . count($received_solutions) . "');
    console.log('  - Total solutions: " . $total_count . "');
    console.log('  - Filtre actuel: " . $filter . "');
    console.log('  - Page actuelle: " . $page . "/" . $total_pages . "');
    console.log('  - Mode debug: " . ($debug_mode ? 'activé' : 'désactivé') . "');
";

// Include footer
include 'footer.php';
?>

