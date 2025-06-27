<?php
// Démarrer la session si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure les fichiers nécessaires AVANT tout traitement
require_once 'verification.php';
require_once 'db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Récupérer l'ID de l'utilisateur connecté
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    $_SESSION['error_message'] = 'Erreur: ID utilisateur non trouvé dans la session.';
    header('Location: exacueil.php');
    exit;
}

// Initialiser les variables
$received_solutions = [];
$error_message = '';
$success_message = '';
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

// TRAITEMENT DES ACTIONS POST AVANT TOUT OUTPUT HTML
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = connect();
        
        if (!$pdo) {
            throw new Exception("Impossible de se connecter à la base de données");
        }
        
        if (isset($_POST['accept_solution'])) {
            $solution_id = isset($_POST['solution_id']) ? (int)$_POST['solution_id'] : 0;
            
            if ($solution_id > 0) {
                // Vérifier que la solution existe et appartient à un problème de l'utilisateur
                $stmt = $pdo->prepare("
                    SELECT s.*, p.user_id as problem_owner, p.points, p.title as problem_title
                    FROM solutions s
                    INNER JOIN problems p ON s.problem_id = p.problem_id
                    WHERE s.id = ? AND p.user_id = ?
                ");
                
                $stmt->execute([$solution_id, $user_id]);
                $solution = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($solution) {
                    if ($solution['status'] === 'pending') {
                        try {
                            // Commencer une transaction
                            $pdo->beginTransaction();
                            
                            // Mettre à jour le statut de la solution à 'accepted'
                            $stmt = $pdo->prepare("
                                UPDATE solutions
                                SET status = 'accepted', evaluated_at = CURRENT_TIMESTAMP
                                WHERE id = ?
                            ");
                            
                            $result = $stmt->execute([$solution_id]);
                            
                            if ($result && $stmt->rowCount() > 0) {
                                // Mettre à jour les points de l'utilisateur qui a soumis la solution
                                $stmt = $pdo->prepare("
                                    UPDATE users
                                    SET score = score + ?, problems_solved = problems_solved + 1
                                    WHERE id = ?
                                ");
                                $stmt->execute([$solution['points'], $solution['user_id']]);
                                
                                // Valider la transaction
                                $pdo->commit();
                                
                                // REDIRECTION IMMÉDIATE vers la page de paiement
                                $_SESSION['success_message'] = "Solution acceptée avec succès. Redirection vers le paiement...";
                                
                                // Construire l'URL de redirection
                                $redirect_url = "payment.php?solution_id=" . $solution_id;
                                if ($debug_mode) {
                                    $redirect_url .= "&debug=1";
                                }
                                
                                // Debug: Log de la redirection
                                if ($debug_mode) {
                                    error_log("DEBUG: Redirection vers: " . $redirect_url);
                                }
                                
                                // Redirection avec flush pour s'assurer que les headers sont envoyés
                                header("Location: " . $redirect_url);
                                exit(); // ARRÊT COMPLET DE L'EXÉCUTION
                                
                            } else {
                                $pdo->rollBack();
                                throw new Exception("Impossible de mettre à jour le statut de la solution");
                            }
                            
                        } catch (PDOException $e) {
                            $pdo->rollBack();
                            error_log("Erreur PDO lors de l'acceptation: " . $e->getMessage());
                            $_SESSION['error_message'] = "Erreur lors de l'acceptation de la solution: " . $e->getMessage();
                        }
                        
                    } else {
                        $_SESSION['error_message'] = "Cette solution a déjà été évaluée (statut: " . $solution['status'] . ").";
                    }
                } else {
                    $_SESSION['error_message'] = "Solution introuvable ou vous n'êtes pas autorisé à l'accepter.";
                }
            } else {
                $_SESSION['error_message'] = "ID de solution invalide.";
            }
            
            // Redirection après erreur
            $redirect_url = $_SERVER['PHP_SELF'] . "?filter=$filter&page=$page";
            if ($debug_mode) {
                $redirect_url .= "&debug=1";
            }
            header("Location: $redirect_url");
            exit;
        }
        
        if (isset($_POST['reject_solution'])) {
            $solution_id = isset($_POST['solution_id']) ? (int)$_POST['solution_id'] : 0;
            
            if ($solution_id > 0) {
                // Vérifier que la solution existe et appartient à un problème de l'utilisateur
                $stmt = $pdo->prepare("
                    SELECT s.*, p.user_id as problem_owner
                    FROM solutions s
                    INNER JOIN problems p ON s.problem_id = p.problem_id
                    WHERE s.id = ? AND p.user_id = ?
                ");
                
                $stmt->execute([$solution_id, $user_id]);
                $solution = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($solution) {
                    if ($solution['status'] === 'pending') {
                        try {
                            // Mettre à jour le statut de la solution à 'rejected'
                            $stmt = $pdo->prepare("
                                UPDATE solutions
                                SET status = 'rejected', evaluated_at = CURRENT_TIMESTAMP
                                WHERE id = ?
                            ");
                            
                            $result = $stmt->execute([$solution_id]);
                            
                            if ($result && $stmt->rowCount() > 0) {
                                $_SESSION['success_message'] = "La solution a été rejetée avec succès.";
                            } else {
                                throw new Exception("Impossible de rejeter la solution");
                            }
                            
                        } catch (PDOException $e) {
                            error_log("Erreur PDO lors du rejet: " . $e->getMessage());
                            $_SESSION['error_message'] = "Erreur lors du rejet de la solution: " . $e->getMessage();
                        }
                        
                    } else {
                        $_SESSION['error_message'] = "Cette solution a déjà été évaluée.";
                    }
                } else {
                    $_SESSION['error_message'] = "Solution introuvable ou vous n'êtes pas autorisé à la rejeter.";
                }
            } else {
                $_SESSION['error_message'] = "ID de solution invalide.";
            }
            
            // Redirection après traitement du rejet
            $redirect_url = $_SERVER['PHP_SELF'] . "?filter=$filter&page=$page";
            if ($debug_mode) {
                $redirect_url .= "&debug=1";
            }
            header("Location: $redirect_url");
            exit;
        }
        
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Erreur de base de données: " . $e->getMessage();
        error_log("Erreur PDO lors du traitement POST dans user_feedback.php: " . $e->getMessage());
        
        $redirect_url = $_SERVER['PHP_SELF'] . "?filter=$filter&page=$page";
        if ($debug_mode) {
            $redirect_url .= "&debug=1";
        }
        header("Location: $redirect_url");
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = "Erreur lors du traitement: " . $e->getMessage();
        error_log("Erreur lors du traitement POST dans user_feedback.php: " . $e->getMessage());
        
        $redirect_url = $_SERVER['PHP_SELF'] . "?filter=$filter&page=$page";
        if ($debug_mode) {
            $redirect_url .= "&debug=1";
        }
        header("Location: $redirect_url");
        exit;
    }
}

// RÉCUPÉRATION DES DONNÉES (après traitement POST) - LOGIQUE ORIGINALE RESTAURÉE
try {
    $pdo = connect();
    
    if (!$pdo) {
        throw new Exception("Impossible de se connecter à la base de données");
    }
    
    $debug_info[] = "✅ Connexion à la base de données réussie";
    $debug_info[] = "👤 User ID: $user_id";
    $debug_info[] = "🔍 Filtre: $filter";
    $debug_info[] = "📄 Page: $page";
    
    // Vérifier d'abord si l'utilisateur a des problèmes publiés
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM problems WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user_problems_count = $stmt->fetchColumn();
    $debug_info[] = "📝 Problèmes publiés par l'utilisateur: $user_problems_count";
    
    if ($user_problems_count == 0) {
        $debug_info[] = "ℹ️ L'utilisateur n'a publié aucun problème";
        $received_solutions = [];
        $total_count = 0;
        $total_pages = 0;
    } else {
        // Construire la clause WHERE pour le filtre - LOGIQUE ORIGINALE
        $where_filter = "";
        $params = [$user_id, $user_id];
        
        if ($filter !== 'all') {
            // Adapter le filtre selon les valeurs possibles dans la base - LOGIQUE ORIGINALE
            $filter_mapping = [
                'pending' => 'pending',
                'accepted' => ['approved', 'accepted', 'approve', 'accept'],
                'rejected' => ['rejected', 'reject', 'declined', 'refuse']
            ];
            
            if (isset($filter_mapping[$filter])) {
                if (is_array($filter_mapping[$filter])) {
                    $placeholders = str_repeat('?,', count($filter_mapping[$filter]) - 1) . '?';
                    $where_filter = " AND s.status IN ($placeholders)";
                    $params = array_merge($params, $filter_mapping[$filter]);
                } else {
                    $where_filter = " AND s.status = ?";
                    $params[] = $filter_mapping[$filter];
                }
            }
        }
        
        // Compter le total d'abord
        $count_sql = "
            SELECT COUNT(*) 
            FROM solutions s
            INNER JOIN problems p ON s.problem_id = p.problem_id
            WHERE p.user_id = ? AND s.user_id != ?" . $where_filter;
        
        $stmt = $pdo->prepare($count_sql);
        $stmt->execute($params);
        $total_count = $stmt->fetchColumn();
        $total_pages = ceil($total_count / $per_page);
        
        $debug_info[] = "📈 Total solutions trouvées: $total_count";
        $debug_info[] = "📄 Total pages: $total_pages";
        
        if ($total_count > 0) {
            // Récupérer les solutions avec pagination - LOGIQUE ORIGINALE
            $sql = "
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
                    p.user_id as problem_owner_id,
                    p.points,
                    u.username,
                    u.name as solver_name,
                    pr.amount as price,
                    pr.currency
                FROM solutions s
                INNER JOIN problems p ON s.problem_id = p.problem_id
                INNER JOIN users u ON s.user_id = u.id
                LEFT JOIN prices pr ON s.price_id = pr.price_id
                WHERE p.user_id = ? AND s.user_id != ?" . $where_filter . "
                ORDER BY s.created_at DESC
                OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
            
            $pagination_params = array_merge($params, [$offset, $per_page]);
            
            try {
                $stmt = $pdo->prepare($sql);
                $stmt->execute($pagination_params);
                $received_solutions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $debug_info[] = "✅ Solutions récupérées: " . count($received_solutions);
                
            } catch (Exception $sql_error) {
                $debug_info[] = "❌ Erreur SQL avec OFFSET/FETCH: " . $sql_error->getMessage();
                
                // Fallback: récupérer toutes les solutions et paginer avec PHP - LOGIQUE ORIGINALE
                $fallback_sql = "
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
                        p.user_id as problem_owner_id,
                        p.points,
                        u.username,
                        u.name as solver_name,
                        pr.amount as price,
                        pr.currency
                    FROM solutions s
                    INNER JOIN problems p ON s.problem_id = p.problem_id
                    INNER JOIN users u ON s.user_id = u.id
                    LEFT JOIN prices pr ON s.price_id = pr.price_id
                    WHERE p.user_id = ? AND s.user_id != ?" . $where_filter . "
                    ORDER BY s.created_at DESC";
                
                $stmt = $pdo->prepare($fallback_sql);
                $stmt->execute($params);
                $all_solutions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Appliquer la pagination avec array_slice
                $received_solutions = array_slice($all_solutions, $offset, $per_page);
                
                $debug_info[] = "✅ Solutions récupérées avec méthode alternative: " . count($received_solutions);
            }
        } else {
            $debug_info[] = "ℹ️ Aucune solution trouvée avec les critères actuels";
        }
    }
    
} catch (PDOException $e) {
    $error_message = "Erreur de base de données: " . $e->getMessage();
    $debug_info[] = "❌ Erreur PDO: " . $e->getMessage();
    error_log("Erreur PDO dans user_feedback.php: " . $e->getMessage());
} catch (Exception $e) {
    $error_message = "Erreur: " . $e->getMessage();
    $debug_info[] = "❌ Erreur générale: " . $e->getMessage();
    error_log("Erreur dans user_feedback.php: " . $e->getMessage());
}

// Récupérer les messages de session
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Set page title
$page_title = "Notifications des Problèmes";

// Additional CSS specific to this page - IDENTIQUE À L'ORIGINAL
$additional_css = "
    .filter-section {
        background: white;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .filter-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }

    .filter-group {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .filter-group label {
        font-weight: 600;
        color: #333;
        margin: 0;
    }

    .filter-group select {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        background: white;
        font-size: 14px;
    }

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
        position: relative;
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

    .solution-status.approved,
    .solution-status.accepted,
    .solution-status.approve,
    .solution-status.accept {
        background: #d5f5e3;
        color: #27ae60;
    }

    .solution-status.rejected,
    .solution-status.reject,
    .solution-status.declined,
    .solution-status.refuse {
        background: #fdedec;
        color: #e74c3c;
    }

    .solution-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
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
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
        font-family: monospace;
        font-size: 12px;
        max-height: 300px;
        overflow-y: auto;
    }

    .debug-info h4 {
        margin-top: 0;
        color: #495057;
    }

    .debug-info div {
        margin: 5px 0;
        padding: 2px 0;
    }

    @media (max-width: 768px) {
        .filter-row {
            flex-direction: column;
            align-items: stretch;
        }
        
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
            width: 100%;
        }
        
        .solution-actions .btn {
            flex: 1;
            text-align: center;
        }
    }
";

// Include header APRÈS le traitement POST
include 'header.php';
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
                <span class="solution-status <?php echo htmlspecialchars($solution['status'] ?? 'pending'); ?>">
                    <?php 
                    $status_labels = [
                        'pending' => 'En attente',
                        'approved' => 'Acceptée',
                        'accepted' => 'Acceptée',
                        'approve' => 'Acceptée',
                        'accept' => 'Acceptée',
                        'rejected' => 'Rejetée',
                        'reject' => 'Rejetée',
                        'declined' => 'Rejetée',
                        'refuse' => 'Rejetée'
                    ];
                    $current_status = $solution['status'] ?? 'pending';
                    echo isset($status_labels[$current_status]) ? 
                        $status_labels[$current_status] : 
                        htmlspecialchars($current_status);
                    ?>
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
                
                <?php if (($solution['status'] ?? 'pending') === 'pending'): ?>
                    <div class="solution-actions">
                        <form method="POST" action="" style="display: inline;" onsubmit="return confirmAccept('<?php echo addslashes($solution['problem_title'] ?? ''); ?>', '<?php echo number_format($solution['price'] ?? 0, 2); ?>');">
                            <input type="hidden" name="solution_id" value="<?php echo $solution['id']; ?>">
                            <button type="submit" name="accept_solution" value="1" class="btn btn-primary">
                                <i class="fas fa-check"></i> Accepter et Payer (<?php echo number_format($solution['price'] ?? 0, 2); ?> €)
                            </button>
                        </form>
                        
                        <form method="POST" action="" style="display: inline;" onsubmit="return confirmReject('<?php echo addslashes($solution['problem_title'] ?? ''); ?>');">
                            <input type="hidden" name="solution_id" value="<?php echo $solution['id']; ?>">
                            <button type="submit" name="reject_solution" value="1" class="btn btn-outline">
                                <i class="fas fa-times"></i> Rejeter
                            </button>
                        </form>
                    </div>
                <?php elseif (in_array($solution['status'] ?? '', ['approved', 'accepted', 'approve', 'accept'])): ?>
                    <div class="solution-actions">
                        <a href="payment.php?solution_id=<?php echo $solution['id']; ?><?php echo $debug_mode ? '&debug=1' : ''; ?>" class="btn btn-primary">
                            <i class="fas fa-credit-card"></i> Procéder au paiement (<?php echo number_format($solution['price'] ?? 0, 2); ?> €)
                        </a>
                        <span class="btn" style="background-color: #d4edda; color: #155724; cursor: default;">
                            <i class="fas fa-check-circle"></i> Solution acceptée
                        </span>
                    </div>
                <?php elseif (in_array($solution['status'] ?? '', ['rejected', 'reject', 'declined', 'refuse'])): ?>
                    <span class="btn" style="background-color: #f8d7da; color: #721c24; cursor: default;">
                        <i class="fas fa-times-circle"></i> Solution rejetée
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

<?php
// Additional scripts - VERSION CORRIGÉE AVEC DEBUG
$additional_scripts = "
    // Fonctions de confirmation avec messages clairs
    function confirmAccept(problemTitle, price) {
        const message = '🎯 ACCEPTER CETTE SOLUTION\\n\\n' +
                       '📝 Problème: ' + problemTitle + '\\n' +
                       '💰 Prix à payer: ' + price + ' €\\n\\n' +
                       '✅ En acceptant, vous serez automatiquement redirigé vers la page de paiement.\\n' +
                       '⚠️ Cette action ne peut pas être annulée.\\n\\n' +
                       'Voulez-vous continuer ?';
        return confirm(message);
    }
    
    function confirmReject(problemTitle) {
        const message = '❌ REJETER CETTE SOLUTION\\n\\n' +
                       '📝 Problème: ' + problemTitle + '\\n\\n' +
                       '⚠️ Cette action est définitive et ne peut pas être annulée.\\n' +
                       '❗ Le développeur sera notifié du rejet.\\n\\n' +
                       'Êtes-vous sûr de vouloir rejeter cette solution ?';
        return confirm(message);
    }
    
    // Gestion des formulaires avec loading et feedback visuel
    document.querySelectorAll('form[method=\"POST\"]').forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type=\"submit\"]');
            if (submitBtn && !submitBtn.disabled) {
                const originalText = submitBtn.innerHTML;
                const isAccept = submitBtn.name === 'accept_solution';
                
                // Debug: Log de soumission
                " . ($debug_mode ? "console.log('DEBUG: Soumission du formulaire', {action: submitBtn.name, solutionId: form.querySelector('input[name=\"solution_id\"]').value});" : "") . "
                
                // Délai court pour permettre la soumission
                setTimeout(() => {
                    submitBtn.disabled = true;
                    if (isAccept) {
                        submitBtn.innerHTML = '<i class=\"fas fa-spinner fa-spin\"></i> Redirection vers le paiement...';
                        submitBtn.style.backgroundColor = '#28a745';
                        
                        // Debug: Log de redirection attendue
                        " . ($debug_mode ? "console.log('DEBUG: Redirection vers payment.php attendue');" : "") . "
                    } else {
                        submitBtn.innerHTML = '<i class=\"fas fa-spinner fa-spin\"></i> Traitement du rejet...';
                        submitBtn.style.backgroundColor = '#dc3545';
                    }
                    submitBtn.style.opacity = '0.8';
                    
                    // Désactiver tous les autres boutons de la carte
                    const card = form.closest('.solution-card');
                    if (card) {
                        const allButtons = card.querySelectorAll('button, .btn');
                        allButtons.forEach(btn => {
                            if (btn !== submitBtn) {
                                btn.style.opacity = '0.5';
                                btn.style.pointerEvents = 'none';
                            }
                        });
                    }
                }, 100);
            }
        });
    });
    
    // Debug: Vérification de la page au chargement
    " . ($debug_mode ? "
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🔧 DEBUG user_feedback.php:');
        console.log('👤 User ID: " . $user_id . "');
        console.log('🔍 Filtre actuel: " . $filter . "');
        console.log('📄 Page actuelle: " . $page . "');
        console.log('📊 Total solutions: " . $total_count . "');
        console.log('📋 Solutions affichées:', document.querySelectorAll('.solution-card').length);
        console.log('⏳ Solutions en attente:', document.querySelectorAll('.solution-status.pending').length);
        console.log('✅ Solutions acceptées:', document.querySelectorAll('.solution-status.accepted, .solution-status.approved, .solution-status.approve, .solution-status.accept').length);
        
        // Vérifier les liens de paiement
        const paymentLinks = document.querySelectorAll('a[href*=\"payment.php\"]');
        console.log('💳 Liens de paiement trouvés:', paymentLinks.length);
        paymentLinks.forEach((link, index) => {
            console.log('  Lien ' + (index + 1) + ':', link.href);
        });
        
        // Vérifier les formulaires d'acceptation
        const acceptForms = document.querySelectorAll('form button[name=\"accept_solution\"]');
        console.log('✅ Boutons d\\'acceptation trouvés:', acceptForms.length);
        
        // Test de redirection (fonction utilitaire)
        window.testPaymentRedirect = function(solutionId) {
            const url = 'payment.php?solution_id=' + solutionId + '&debug=1';
            console.log('🧪 Test de redirection vers:', url);
            if (confirm('Tester la redirection vers la page de paiement?')) {
                window.location.href = url;
            }
        };
        
        console.log('💡 Tapez testPaymentRedirect(SOLUTION_ID) pour tester une redirection');
    });
    " : "") . "
    
    // Fonction utilitaire pour copier du texte
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
            textArea.style.position = 'fixed';
            textArea.style.opacity = '0';
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
    
    // Notification toast améliorée
    function showToast(message, type = 'info', duration = 5000) {
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            z-index: 9999;
            max-width: 350px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateX(100%);
            transition: transform 0.3s ease;
            word-wrap: break-word;
            cursor: pointer;
            border-left: 4px solid rgba(255,255,255,0.3);
        `;
        
        switch(type) {
            case 'success':
                toast.style.backgroundColor = '#28a745';
                toast.innerHTML = '<i class=\"fas fa-check-circle\"></i> ' + message;
                break;
            case 'error':
                toast.style.backgroundColor = '#dc3545';
                toast.innerHTML = '<i class=\"fas fa-exclamation-circle\"></i> ' + message;
                break;
            case 'warning':
                toast.style.backgroundColor = '#ffc107';
                toast.style.color = '#212529';
                toast.innerHTML = '<i class=\"fas fa-exclamation-triangle\"></i> ' + message;
                break;
            default:
                toast.style.backgroundColor = '#17a2b8';
                toast.innerHTML = '<i class=\"fas fa-info-circle\"></i> ' + message;
        }
        
        document.body.appendChild(toast);
        
        // Animation d'entrée
        setTimeout(() => {
            toast.style.transform = 'translateX(0)';
        }, 100);
        
        // Suppression automatique
        const autoRemove = setTimeout(() => {
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, duration);
        
        // Permettre la fermeture manuelle
        toast.addEventListener('click', () => {
            clearTimeout(autoRemove);
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        });
    }
    
    // Ajouter des boutons de copie pour le code
    document.addEventListener('DOMContentLoaded', function() {
        const codeBlocks = document.querySelectorAll('.solution-content pre');
        codeBlocks.forEach(block => {
            const copyBtn = document.createElement('button');
            copyBtn.innerHTML = '<i class=\"fas fa-copy\"></i>';
            copyBtn.className = 'btn';
            copyBtn.style.cssText = `
                position: absolute;
                top: 10px;
                right: 10px;
                padding: 5px 8px;
                font-size: 12px;
                opacity: 0.7;
                z-index: 10;
                background: rgba(255,255,255,0.9);
                color: #333;
                border: 1px solid #ddd;
                border-radius: 4px;
                transition: all 0.3s;
            `;
            copyBtn.title = 'Copier le code';
            copyBtn.onclick = (e) => {
                e.preventDefault();
                copyToClipboard(block.textContent);
                copyBtn.innerHTML = '<i class=\"fas fa-check\"></i>';
                copyBtn.style.backgroundColor = '#28a745';
                copyBtn.style.color = 'white';
                setTimeout(() => {
                    copyBtn.innerHTML = '<i class=\"fas fa-copy\"></i>';
                    copyBtn.style.backgroundColor = 'rgba(255,255,255,0.9)';
                    copyBtn.style.color = '#333';
                }, 2000);
            };
            
            const container = block.parentElement;
            container.style.position = 'relative';
            container.appendChild(copyBtn);
            
            // Afficher/masquer le bouton au survol
            container.addEventListener('mouseenter', () => {
                copyBtn.style.opacity = '1';
                copyBtn.style.transform = 'scale(1.05)';
            });
            container.addEventListener('mouseleave', () => {
                copyBtn.style.opacity = '0.7';
                copyBtn.style.transform = 'scale(1)';
            });
        });
    });
    
    // Animation d'apparition des cartes avec effet de cascade
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.solution-card');
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
    
    // Raccourcis clavier utiles
    document.addEventListener('keydown', function(e) {
        // Ctrl + R pour actualiser
        if (e.ctrlKey && e.key === 'r') {
            e.preventDefault();
            window.location.reload();
        }
        
        // Échap pour fermer les toasts
        if (e.key === 'Escape') {
            const toasts = document.querySelectorAll('[style*=\"position: fixed\"][style*=\"top: 20px\"]');
            toasts.forEach(toast => {
                if (toast.style.transform !== 'translateX(100%)') {
                    toast.click();
                }
            });
        }
        
        // F pour filtrer (focus sur le select)
        if (e.key === 'f' && !e.ctrlKey && !e.altKey) {
            const filterSelect = document.getElementById('filter');
            if (filterSelect && document.activeElement !== filterSelect) {
                e.preventDefault();
                filterSelect.focus();
            }
        }
        
        // Touches numériques pour navigation rapide des pages
        if (e.key >= '1' && e.key <= '9' && e.ctrlKey) {
            e.preventDefault();
            const pageNum = parseInt(e.key);
            if (pageNum <= " . $total_pages . ") {
                window.location.href = '?filter=" . urlencode($filter) . "&page=' + pageNum + '" . ($debug_mode ? "&debug=1" : "") . "';
            }
        }
    });
    
    // Détecter les problèmes de connexion
    window.addEventListener('online', () => {
        showToast('Connexion rétablie', 'success', 3000);
    });
    
    window.addEventListener('offline', () => {
        showToast('Connexion perdue - Certaines fonctionnalités peuvent ne pas fonctionner', 'warning', 8000);
    });
    
    // Auto-refresh pour les solutions en attente (optionnel)
    function enableAutoRefresh(intervalMinutes = 5) {
        const refreshInterval = intervalMinutes * 60 * 1000;
        setTimeout(() => {
            if (document.visibilityState === 'visible' && 
                document.querySelectorAll('.solution-status.pending').length > 0) {
                
                const shouldRefresh = confirm('🔄 Actualiser la page pour voir les nouvelles solutions?\\n\\n' +
                    'Cliquez sur OK pour actualiser ou Annuler pour reporter de ' + intervalMinutes + ' minutes.');
                
                if (shouldRefresh) {
                    window.location.reload();
                } else {
                    enableAutoRefresh(intervalMinutes); // Redemander plus tard
                }
            } else {
                enableAutoRefresh(intervalMinutes); // Continuer à vérifier
            }
        }, refreshInterval);
    }
    
    // Activer l'auto-refresh seulement si on est sur la page des solutions en attente
    " . ($filter === 'pending' ? "enableAutoRefresh(5);" : "") . "
    
    // Fonction pour afficher des statistiques en temps réel
    function updateStats() {
        const pendingCount = document.querySelectorAll('.solution-status.pending').length;
        const acceptedCount = document.querySelectorAll('.solution-status.accepted, .solution-status.approved, .solution-status.approve, .solution-status.accept').length;
        const rejectedCount = document.querySelectorAll('.solution-status.rejected, .solution-status.reject, .solution-status.declined, .solution-status.refuse').length;
        
        " . ($debug_mode ? "
        console.log('📊 Statistiques actuelles:');
        console.log('⏳ Solutions en attente:', pendingCount);
        console.log('✅ Solutions acceptées:', acceptedCount);
        console.log('❌ Solutions rejetées:', rejectedCount);
        " : "") . "
        
        // Mettre à jour le titre de la page avec le nombre de solutions en attente
        if (pendingCount > 0) {
            document.title = '(' + pendingCount + ') Notifications - CodeChallenge';
        } else {
            document.title = 'Notifications - CodeChallenge';
        }
    }
    
    // Gestion des formulaires avec prévention de double soumission
    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.querySelectorAll('form[method=\"POST\"]');
        forms.forEach(form => {
            let isSubmitting = false;
            
            form.addEventListener('submit', function(e) {
                if (isSubmitting) {
                    e.preventDefault();
                    " . ($debug_mode ? "console.warn('DEBUG: Double soumission empêchée');" : "") . "
                    return false;
                }
                
                isSubmitting = true;
                
                // Réactiver après 10 secondes en cas de problème
                setTimeout(() => {
                    isSubmitting = false;
                    " . ($debug_mode ? "console.log('DEBUG: Formulaire réactivé après timeout');" : "") . "
                }, 10000);
            });
        });
        
        // Mettre à jour les statistiques
        updateStats();
        
        // Vérifier la connectivité
        if (!navigator.onLine) {
            showToast('Vous êtes hors ligne. Certaines fonctionnalités peuvent ne pas fonctionner.', 'warning', 8000);
        }
        
        // Message de bienvenue pour les nouvelles solutions
        const pendingSolutions = document.querySelectorAll('.solution-status.pending').length;
        if (pendingSolutions > 0) {
            setTimeout(() => {
                showToast('Vous avez ' + pendingSolutions + ' solution(s) en attente de traitement', 'info', 5000);
            }, 1000);
        }
    });
    
    // Fonction utilitaire pour déboguer (disponible dans la console)
    window.debugUserFeedback = function() {
        const stats = {
            userId: '" . $user_id . "',
            filter: '" . $filter . "',
            page: " . $page . ",
            totalCount: " . $total_count . ",
            displayedSolutions: document.querySelectorAll('.solution-card').length,
            pendingSolutions: document.querySelectorAll('.solution-status.pending').length,
            acceptedSolutions: document.querySelectorAll('.solution-status.accepted, .solution-status.approved, .solution-status.approve, .solution-status.accept').length,
            rejectedSolutions: document.querySelectorAll('.solution-status.rejected, .solution-status.reject, .solution-status.declined, .solution-status.refuse').length
        };
        
        console.log('🔧 Informations de débogage user_feedback.php:', stats);
        return stats;
    };
    
    // Message d'aide pour les développeurs
    " . ($debug_mode ? "console.log('💡 Fonctions de debug disponibles: debugUserFeedback(), testPaymentRedirect(id)');" : "") . "
";

// Include footer
include 'footer.php';
?>

