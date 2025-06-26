<?php
// Démarrer la session si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
";

// Include header
include 'header.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: exlogin.php');
    exit;
}

// Récupérer l'ID de l'utilisateur connecté
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo '<div class="alert alert-danger">Erreur: ID utilisateur non trouvé dans la session.</div>';
    include 'footer.php';
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
            $where_clause .= " AND s.status = ?";
            $params[] = $filter;
        }
        
        $debug_info[] = "🔍 Clause WHERE: $where_clause";
        $debug_info[] = "📊 Paramètres: " . implode(', ', $params);
        
        // Compter le total pour la pagination - SQL Server
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
            // CORRECTION: Spécifier explicitement les colonnes pour éviter les doublons
            $main_query = "
                WITH SolutionsCTE AS (
                    SELECT 
                        s.id as solution_id,
                        s.problem_id,
                        s.user_id as solution_user_id,
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
                        pr.currency,
                        ROW_NUMBER() OVER (ORDER BY s.created_at DESC) as RowNum
                    FROM solutions s
                    INNER JOIN problems p ON s.problem_id = p.problem_id
                    INNER JOIN users u ON s.user_id = u.id
                    LEFT JOIN prices pr ON s.price_id = pr.price_id
                    $where_clause
                )
                SELECT *
                FROM SolutionsCTE
                WHERE RowNum > $offset AND RowNum <= " . ($offset + $per_page);
            
            $debug_info[] = "🔍 Requête principale (CTE): " . str_replace("\n", " ", $main_query);
            
            $stmt = $pdo->prepare($main_query);
            $stmt->execute($params);
            $solutions_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Reformater les données pour correspondre à l'ancien format
            $received_solutions = [];
            foreach ($solutions_raw as $row) {
                $received_solutions[] = [
                    'id' => $row['solution_id'],
                    'problem_id' => $row['problem_id'],
                    'user_id' => $row['solution_user_id'],
                    'solution_code' => $row['solution_code'],
                    'explanation' => $row['explanation'],
                    'status' => $row['status'],
                    'created_at' => $row['created_at'],
                    'evaluated_at' => $row['evaluated_at'],
                    'price_id' => $row['price_id'],
                    'problem_title' => $row['problem_title'],
                    'points' => $row['points'],
                    'username' => $row['username'],
                    'solver_name' => $row['solver_name'],
                    'price' => $row['price'],
                    'currency' => $row['currency']
                ];
            }
            
            $debug_info[] = "✅ Solutions récupérées: " . count($received_solutions);
            
            // Alternative si CTE ne fonctionne pas - utiliser une approche différente
            if (empty($received_solutions) && $total_count > 0) {
                $debug_info[] = "⚠️ CTE n'a pas fonctionné, essai avec méthode alternative";
                
                // Méthode alternative: Récupérer tous les résultats et utiliser array_slice
                if ($total_count <= 1000) { // Limite raisonnable
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
                    ";
                    
                    $debug_info[] = "🔍 Requête simple: " . str_replace("\n", " ", $simple_query);
                    
                    $stmt = $pdo->prepare($simple_query);
                    $stmt->execute($params);
                    $all_solutions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Appliquer la pagination avec array_slice
                    $received_solutions = array_slice($all_solutions, $offset, $per_page);
                    
                    $debug_info[] = "✅ Solutions récupérées avec méthode alternative: " . count($received_solutions);
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

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($pdo)) {
    try {
        if (isset($_POST['accept_solution'])) {
            $solution_id = isset($_POST['solution_id']) ? (int)$_POST['solution_id'] : 0;
            
            if ($solution_id > 0) {
                // Vérifier que la solution existe et appartient à un problème de l'utilisateur
                $stmt = $pdo->prepare("
                    SELECT s.*, p.user_id as problem_owner, p.points
                    FROM solutions s
                    INNER JOIN problems p ON s.problem_id = p.problem_id
                    WHERE s.id = ? AND p.user_id = ?
                ");
                
                $stmt->execute([$solution_id, $user_id]);
                $solution = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($solution) {
                    if ($solution['status'] === 'pending') {
                        // Mettre à jour le statut de la solution - SQL Server avec GETDATE()
                        $stmt = $pdo->prepare("
                            UPDATE solutions
                            SET status = 'accepted', evaluated_at = GETDATE()
                            WHERE id = ?
                        ");
                        
                        $result = $stmt->execute([$solution_id]);
                        
                        if ($result) {
                            // Mettre à jour les points de l'utilisateur qui a soumis la solution
                            $stmt = $pdo->prepare("
                                UPDATE users
                                SET score = score + ?, problems_solved = problems_solved + 1
                                WHERE id = ?
                            ");
                            $stmt->execute([$solution['points'], $solution['user_id']]);
                            
                            $success_message = "La solution a été acceptée avec succès. Vous serez redirigé vers la page de paiement.";
                            
                            // Rediriger vers la page de paiement après 2 secondes
                            // Redirection immédiate vers la page de paiement (sans JavaScript)
    header("Location: payment.php?solution_id=$solution_id");
    exit; // Important : arrêter l'exécution du script
} else {
    $error_message = "Une erreur est survenue lors de l'acceptation de la solution.";
}
                    } else {
                        $error_message = "Cette solution a déjà été évaluée.";
                    }
                } else {
                    $error_message = "Solution introuvable ou vous n'êtes pas autorisé à l'accepter.";
                }
            } else {
                $error_message = "ID de solution invalide.";
            }
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
                        // Mettre à jour le statut de la solution - SQL Server avec GETDATE()
                        $stmt = $pdo->prepare("
                            UPDATE solutions
                            SET status = 'rejected', evaluated_at = GETDATE()
                            WHERE id = ?
                        ");
                        
                        $result = $stmt->execute([$solution_id]);
                        
                        if ($result) {
                            $success_message = "La solution a été rejetée avec succès.";
                            
                            // Recharger la page pour actualiser la liste
                            header("Location: " . $_SERVER['PHP_SELF'] . "?filter=$filter&page=$page" . ($debug_mode ? "&debug=1" : ""));
                            exit;
                        } else {
                            $error_message = "Une erreur est survenue lors du rejet de la solution.";
                        }
                    } else {
                        $error_message = "Cette solution a déjà été évaluée.";
                    }
                } else {
                    $error_message = "Solution introuvable ou vous n'êtes pas autorisé à la rejeter.";
                }
            } else {
                $error_message = "ID de solution invalide.";
            }
        }
        
    } catch (PDOException $e) {
        $error_message = "Erreur lors du traitement: " . $e->getMessage();
        $debug_info[] = "❌ Erreur PDO lors du traitement POST: " . $e->getMessage();
        error_log("Erreur PDO lors du traitement POST dans user_feedback.php: " . $e->getMessage());
    } catch (Exception $e) {
        $error_message = "Erreur lors du traitement: " . $e->getMessage();
        $debug_info[] = "❌ Erreur lors du traitement POST: " . $e->getMessage();
        error_log("Erreur lors du traitement POST dans user_feedback.php: " . $e->getMessage());
    }
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
                <span class="solution-status <?php echo htmlspecialchars($solution['status'] ?? 'pending'); ?>">
                    <?php 
                    $status_labels = [
                        'pending' => 'En attente',
                        'accepted' => 'Acceptée',
                        'rejected' => 'Rejetée'
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
                                <i class="fas fa-check"></i> Accepter (<?php echo number_format($solution['price'] ?? 0, 2); ?> €)
                            </button>
                        </form>
                        
                        <form method="POST" action="" style="display: inline;" onsubmit="return confirmReject('<?php echo addslashes($solution['problem_title'] ?? ''); ?>');">
                            <input type="hidden" name="solution_id" value="<?php echo $solution['id']; ?>">
                            <button type="submit" name="reject_solution" value="1" class="btn btn-outline">
                                <i class="fas fa-times"></i> Rejeter
                            </button>
                        </form>
                    </div>
                <?php elseif (($solution['status'] ?? '') === 'accepted'): ?>
                    <a href="payment.php?solution_id=<?php echo $solution['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-credit-card"></i> Procéder au paiement
                    </a>
                <?php elseif (($solution['status'] ?? '') === 'rejected'): ?>
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
// Additional scripts
$additional_scripts = "
    // Fonctions de confirmation améliorées
    function confirmAccept(problemTitle, price) {
        const message = 'Accepter cette solution ?\\n\\n' +
                       '📝 Problème: ' + problemTitle + '\\n' +
                       '💰 Prix à payer: ' + price + ' €\\n\\n' +
                       '⚠️ Vous serez redirigé vers la page de paiement après confirmation.';
        return confirm(message);
    }
    
    function confirmReject(problemTitle) {
        const message = 'Rejeter cette solution ?\\n\\n' +
                       '📝 Problème: ' + problemTitle + '\\n\\n' +
                       '⚠️ Cette action est définitive et ne peut pas être annulée.';
        return confirm(message);
    }
    
    // Animation pour les boutons
    document.querySelectorAll('.btn').forEach(button => {
        button.addEventListener('mouseenter', () => {
            if (!button.style.cursor || button.style.cursor !== 'default') {
                button.style.transform = 'translateY(-2px)';
                button.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
            }
        });
        
        button.addEventListener('mouseleave', () => {
            if (!button.style.cursor || button.style.cursor !== 'default') {
                button.style.transform = '';
                button.style.boxShadow = '';
            }
        });
    });
    
    // Animation pour les cartes
    document.querySelectorAll('.solution-card').forEach(card => {
        card.addEventListener('mouseenter', () => {
            card.style.transform = 'translateY(-5px)';
            card.style.boxShadow = '0 8px 15px rgba(0,0,0,0.1)';
        });
        
        card.addEventListener('mouseleave', () => {
            card.style.transform = 'translateY(-3px)';
            card.style.boxShadow = '0 5px 15px rgba(0,0,0,0.08)';
        });
    });
    
    // Gestion des formulaires avec loading
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = form.querySelector('button[type=\"submit\"]');
            if (submitBtn && !submitBtn.disabled) {
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class=\"fas fa-spinner fa-spin\"></i> Traitement...';
                
                // Réactiver après 10 secondes en cas de problème
                setTimeout(() => {
                    if (submitBtn.disabled) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalText;
                    }
                }, 10000);
            }
        });
    });
    
    // Notification toast pour les messages
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
                toast.innerHTML = '<i class=\"fas fa-check-circle\"></i> ' + message;
                break;
            case 'error':
                toast.style.backgroundColor = '#f44336';
                toast.innerHTML = '<i class=\"fas fa-exclamation-circle\"></i> ' + message;
                break;
            case 'warning':
                toast.style.backgroundColor = '#ff9800';
                toast.innerHTML = '<i class=\"fas fa-exclamation-triangle\"></i> ' + message;
                break;
            default:
                toast.style.backgroundColor = '#2196F3';
                toast.innerHTML = '<i class=\"fas fa-info-circle\"></i> ' + message;
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
    
    // Afficher les messages existants comme toast
    " . (!empty($success_message) ? "showToast('" . addslashes($success_message) . "', 'success', 8000);" : "") . "
    " . (!empty($error_message) ? "showToast('" . addslashes($error_message) . "', 'error', 10000);" : "") . "
    
    // Auto-refresh countdown si redirection prévue
    " . (!empty($success_message) && strpos($success_message, 'paiement') !== false ? "
    let countdown = 3;
    const countdownElement = document.createElement('div');
    countdownElement.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #4CAF50;
        color: white;
        padding: 15px 20px;
        border-radius: 5px;
        font-weight: 600;
        z-index: 9999;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    `;
    countdownElement.innerHTML = '<i class=\"fas fa-clock\"></i> Redirection dans ' + countdown + ' secondes...';
    document.body.appendChild(countdownElement);
    
    const countdownInterval = setInterval(() => {
        countdown--;
        if (countdown > 0) {
            countdownElement.innerHTML = '<i class=\"fas fa-clock\"></i> Redirection dans ' + countdown + ' secondes...';
        } else {
            clearInterval(countdownInterval);
            countdownElement.innerHTML = '<i class=\"fas fa-spinner fa-spin\"></i> Redirection en cours...';
        }
    }, 1000);
    " : "") . "
    
    // Animation d'apparition des cartes
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.solution-card');
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
            copyBtn.innerHTML = '<i class=\"fas fa-copy\"></i>';
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
    
    // Raccourcis clavier
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
    });
    
    // Statistiques en temps réel
    function updateStats() {
        const statusElements = document.querySelectorAll('.solution-status');
        const stats = {
            pending: 0,
            accepted: 0,
            rejected: 0
        };
        
        statusElements.forEach(element => {
            const status = element.textContent.toLowerCase();
            if (status.includes('attente')) stats.pending++;
            else if (status.includes('acceptée')) stats.accepted++;
            else if (status.includes('rejetée')) stats.rejected++;
        });
        
        // Mettre à jour le titre de la page avec les stats
        const totalSolutions = stats.pending + stats.accepted + stats.rejected;
        if (totalSolutions > 0) {
            document.title = 'Notifications (' + totalSolutions + ') - CodeChallenge';
        }
        
        return stats;
    }
    
    // Mettre à jour les stats au chargement
    document.addEventListener('DOMContentLoaded', updateStats);
    
    // Fonction pour actualiser automatiquement la page (optionnel)
    function enableAutoRefresh(intervalMinutes = 5) {
        const refreshInterval = intervalMinutes * 60 * 1000;
        setTimeout(() => {
            if (document.visibilityState === 'visible' && confirm('Actualiser la page pour voir les nouvelles solutions?')) {
                window.location.reload();
            } else {
                enableAutoRefresh(intervalMinutes); // Redemander plus tard
            }
        }, refreshInterval);
    }
    
    // Activer l'auto-refresh seulement si on est sur la page des solutions en attente
    " . ($filter === 'pending' ? "enableAutoRefresh(5);" : "") . "
    
    // Amélioration de l'accessibilité
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
    
    // Gestion des états de chargement
    function setLoadingState(element, isLoading) {
        if (isLoading) {
            element.dataset.originalText = element.innerHTML;
            element.innerHTML = '<i class=\"fas fa-spinner fa-spin\"></i> Chargement...';
            element.disabled = true;
            element.style.opacity = '0.7';
        } else {
            element.innerHTML = element.dataset.originalText || element.innerHTML;
            element.disabled = false;
            element.style.opacity = '1';
        }
    }
    
    // Fonction pour valider les formulaires côté client
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
    
    // Fonction pour gérer les erreurs réseau
    function handleNetworkError() {
        showToast('Erreur de connexion. Vérifiez votre connexion internet.', 'error', 8000);
    }
    
    // Détecter les problèmes de connexion
    window.addEventListener('online', () => {
        showToast('Connexion rétablie', 'success', 3000);
    });
    
    window.addEventListener('offline', () => {
        showToast('Connexion perdue', 'warning', 5000);
    });
    
    // Fonction pour sauvegarder l'état de la page
    function savePageState() {
        const state = {
            filter: '" . $filter . "',
            page: " . $page . ",
            timestamp: Date.now()
        };
        localStorage.setItem('userFeedbackState', JSON.stringify(state));
    }
    
    // Sauvegarder l'état lors des changements
    document.addEventListener('DOMContentLoaded', savePageState);
    
    // Fonction pour restaurer l'état de la page
    function restorePageState() {
        const savedState = localStorage.getItem('userFeedbackState');
        if (savedState) {
            try {
                const state = JSON.parse(savedState);
                // Vérifier si l'état n'est pas trop ancien (30 minutes)
                if (Date.now() - state.timestamp < 30 * 60 * 1000) {
                    return state;
                }
            } catch (e) {
                console.warn('Erreur lors de la restauration de l\'état:', e);
            }
        }
        return null;
    }
    
    // Fonction pour exporter les données (optionnel)
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
    
    // Ajouter un bouton d'export si en mode debug
    " . ($debug_mode ? "
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
    " : "") . "
    
    // Fonction pour imprimer la page
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
    
    // Fonction pour gérer les notifications push (si supportées)
    function requestNotificationPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission().then(permission => {
                if (permission === 'granted') {
                    showToast('Notifications activées', 'success');
                }
            });
        }
    }
    
    // Demander la permission pour les notifications si on a des solutions en attente
    " . (count($received_solutions) > 0 && $filter === 'pending' ? "requestNotificationPermission();" : "") . "
    
    // Fonction pour créer une notification
    function createNotification(title, body, icon = '/favicon.ico') {
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(title, {
                body: body,
                icon: icon,
                tag: 'user-feedback'
            });
        }
    }
    
    // Fonction de nettoyage au déchargement de la page
    window.addEventListener('beforeunload', function() {
        // Nettoyer les timers et événements
        const timers = window.timers || [];
        timers.forEach(timer => clearTimeout(timer));
        
        // Sauvegarder l'état final
        savePageState();
    });
    
    // Performance monitoring
    if (window.performance) {
        window.addEventListener('load', function() {
            setTimeout(() => {
                const loadTime = window.performance.timing.loadEventEnd - window.performance.timing.navigationStart;
                if (loadTime > 3000) {
                    console.warn('Page chargée lentement:', loadTime + 'ms');
                }
            }, 0);
        });
    }
    
    // Gestion des erreurs de chargement d'images (si applicable)
    document.addEventListener('DOMContentLoaded', function() {
        const images = document.querySelectorAll('img');
        images.forEach(img => {
            img.addEventListener('error', function() {
                this.style.display = 'none';
            });
        });
    });
    
    // Fonction pour actualiser une section spécifique
    function refreshSection(sectionId) {
        const section = document.getElementById(sectionId);
        if (section) {
            section.style.opacity = '0.7';
            setTimeout(() => {
                section.style.opacity = '1';
            }, 500);
        }
    }
    
    // Amélioration des animations CSS
    const additionalStyles = `
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .shake {
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
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
    `;
    
    // Ajouter les styles CSS supplémentaires
    const styleSheet = document.createElement('style');
    styleSheet.textContent = additionalStyles;
    document.head.appendChild(styleSheet);
    
    // Initialisation finale
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Page user_feedback.php chargée avec succès');
        console.log('Solutions affichées: ' + document.querySelectorAll('.solution-card').length);
        console.log('Filtre actuel: " . $filter . "');
        console.log('Page actuelle: " . $page . "');
        console.log('Total solutions: " . $total_count . "');
        
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
        
        // Vérifier la connectivité
        if (!navigator.onLine) {
            showToast('Vous êtes hors ligne. Certaines fonctionnalités peuvent ne pas fonctionner.', 'warning', 8000);
        }
        
        // Afficher un message si aucune solution n'est trouvée mais que l'utilisateur a des problèmes
        " . ($total_count == 0 && isset($user_problems_count) && $user_problems_count > 0 ? "
        showToast('Vous avez publié des problèmes mais n\\'avez pas encore reçu de solutions.', 'info', 6000);
        " : "") . "
    });
    
    // Fonction utilitaire pour déboguer
    function debugLog(message, data = null) {
        if (" . ($debug_mode ? 'true' : 'false') . ") {
            console.log('[DEBUG] ' + message, data || '');
        }
    }
    
    // Logs de débogage
    debugLog('Mode debug activé');
    debugLog('Filtre actuel', '" . $filter . "');
    debugLog('Page actuelle', " . $page . ");
    debugLog('Total solutions', " . $total_count . ");
    debugLog('Solutions affichées', document.querySelectorAll('.solution-card').length);
";

// Include footer
include 'footer.php';
?>


