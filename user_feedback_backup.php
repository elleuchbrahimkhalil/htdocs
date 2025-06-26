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
            // Récupérer les solutions avec pagination - SQL Server avec OFFSET/FETCH
            $main_query = "
                SELECT s.*, p.title as problem_title, p.problem_id, u.username, u.name as solver_name,
                       pr.amount as price, pr.currency
                FROM solutions s
                INNER JOIN problems p ON s.problem_id = p.problem_id
                INNER JOIN users u ON s.user_id = u.id
                LEFT JOIN prices pr ON s.price_id = pr.price_id
                $where_clause
                ORDER BY s.created_at DESC
                OFFSET ? ROWS
                FETCH NEXT ? ROWS ONLY
            ";
            
            $debug_info[] = "🔍 Requête principale: " . str_replace("\n", " ", $main_query);
            
            // Ajouter les paramètres de pagination
            $pagination_params = array_merge($params, [$offset, $per_page]);
            
            $stmt = $pdo->prepare($main_query);
            $stmt->execute($pagination_params);
            $received_solutions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $debug_info[] = "✅ Solutions récupérées: " . count($received_solutions);
        }
    }
    
} catch (PDOException $e) {
    $error_message = "Erreur de base de données: " . $e->getMessage();
    $debug_info[] = "❌ Erreur PDO: " . $e->getMessage();
    $debug_info[] = "📍 Code erreur: " . $e->getCode();
    error_log("Erreur PDO dans user_feedback.php: " . $e->getMessage());
} catch (Exception $e) {
    $error_message = "Erreur générale: " . $e->getMessage();
    $debug_info[] = "❌ Erreur générale: " . $e->getMessage();
    error_log("Erreur dans user_feedback.php: " . $e->getMessage());
}

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($error_message) && $pdo) {
    try {
        if (isset($_POST['accept_solution'])) {
            $solution_id = isset($_POST['solution_id']) ? (int)$_POST['solution_id'] : 0;
            
            if ($solution_id > 0) {
                // Vérifier que la solution existe et appartient à un problème de l'utilisateur - SQL Server
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
                            echo "<script>
                                setTimeout(function() {
                                    window.location.href = 'payment.php?solution_id=$solution_id';
                                }, 2000);
                            </script>";
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
                // Vérifier que la solution existe et appartient à un problème de l'utilisateur - SQL Server
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
        error_log("Erreur PDO lors du traitement POST dans user_feedback.php: " . $e->getMessage());
    } catch (Exception $e) {
        $error_message = "Erreur lors du traitement: " . $e->getMessage();
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
            <a href="?">Désactiver le débogage</a>
        </div>
    </div>
<?php endif; ?>

<?php if (!$debug_mode): ?>
    <div style="margin-bottom: 20px;">
        <a href="?debug=1" class="btn btn-outline" style="font-size: 12px;">
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
                <span>Total : <?php echo $total_count; ?> solution(s)</span>
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
                    <?php echo htmlspecialchars($solution['problem_title']); ?>
                </h3>
                <span class="solution-price">
                    <i class="fas fa-tag"></i>
                    <?php echo number_format($solution['price'] ?? 0, 2); ?> €
                </span>
            </div>
            
            <div class="solution-meta">
                <span>
                    <i class="fas fa-user"></i> 
                    <?php echo htmlspecialchars($solution['solver_name'] ?? $solution['username']); ?>
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
                <span class="solution-status <?php echo htmlspecialchars($solution['status']); ?>">
                    <?php 
                    $status_labels = [
                        'pending' => 'En attente',
                        'accepted' => 'Acceptée',
                        'rejected' => 'Rejetée'
                    ];
                    echo isset($status_labels[$solution['status']]) ? 
                        $status_labels[$solution['status']] : 
                        htmlspecialchars($solution['status']);
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
                
                <?php if ($solution['status'] === 'pending'): ?>
                    <div class="solution-actions">
                        <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir accepter cette solution?\n\nProblème: <?php echo addslashes($solution['problem_title']); ?>\nPrix: <?php echo number_format($solution['price'] ?? 0, 2); ?> €\n\nVous serez redirigé vers la page de paiement.');">
                            <input type="hidden" name="solution_id" value="<?php echo $solution['id']; ?>">
                            <button type="submit" name="accept_solution" value="1" class="btn btn-primary">
                                <i class="fas fa-check"></i> Accepter (<?php echo number_format($solution['price'] ?? 0, 2); ?> €)
                            </button>
                        </form>
                        
                        <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir rejeter cette solution?\n\nProblème: <?php echo addslashes($solution['problem_title']); ?>\n\nCette action est définitive.');">
                            <input type="hidden" name="solution_id" value="<?php echo $solution['id']; ?>">
                            <button type="submit" name="reject_solution" value="1" class="btn btn-outline">
                                <i class="fas fa-times"></i> Rejeter
                            </button>
                        </form>
                    </div>
                <?php elseif ($solution['status'] === 'accepted'): ?>
                    <a href="payment.php?solution_id=<?php echo $solution['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-credit-card"></i> Procéder au paiement
                    </a>
                <?php elseif ($solution['status'] === 'rejected'): ?>
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
    
    // Confirmation améliorée pour les actions
    function confirmAction(action, solutionTitle, price, developername) {
        let message = '';
        if (action === 'accept') {
            message = 'Accepter cette solution ?\\n\\n';
            message += '📝 Problème: ' + solutionTitle + '\\n';
            message += '👤 Développeur: ' + developername + '\\n';
            message += '💰 Prix à payer: ' + price + ' €\\n\\n';
            message += '⚠️ Vous serez redirigé vers la page de paiement après confirmation.';
        } else if (action === 'reject') {
            message = 'Rejeter cette solution ?\\n\\n';
            message += '📝 Problème: ' + solutionTitle + '\\n';
            message += '👤 Développeur: ' + developername + '\\n\\n';
            message += '⚠️ Cette action est définitive et ne peut pas être annulée.';
        }
        return confirm(message);
    }
    
    // Améliorer les confirmations des formulaires
    document.querySelectorAll('form').forEach(form => {
        const acceptBtn = form.querySelector('button[name=\"accept_solution\"]');
        const rejectBtn = form.querySelector('button[name=\"reject_solution\"]');
        
        if (acceptBtn || rejectBtn) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const card = form.closest('.solution-card');
                const solutionTitle = card.querySelector('.solution-title').textContent.replace('📄 ', '').trim();
                const priceElement = card.querySelector('.solution-price');
                const price = priceElement ? priceElement.textContent.replace('🏷️ ', '').trim() : '0.00 €';
                const developerElement = card.querySelector('.solution-meta span:first-child');
                const developer = developerElement ? developerElement.textContent.replace('👤 ', '').trim() : 'Inconnu';
                
                let confirmed = false;
                if (acceptBtn && e.submitter === acceptBtn) {
                    confirmed = confirmAction('accept', solutionTitle, price, developer);
                } else if (rejectBtn && e.submitter === rejectBtn) {
                    confirmed = confirmAction('reject', solutionTitle, price, developer);
                }
                
                if (confirmed) {
                    // Soumettre le formulaire
                    form.removeEventListener('submit', arguments.callee);
                    form.submit();
                }
            });
        }
    });
    
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
    
    // Gestion des erreurs de chargement d'images (si applicable)
    document.addEventListener('DOMContentLoaded', function() {
        const images = document.querySelectorAll('img');
        images.forEach(img => {
            img.addEventListener('error', function() {
                this.style.display = 'none';
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
    
    // Statistiques en temps réel (optionnel)
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
        document.title = 'Notifications (' + (stats.pending + stats.accepted + stats.rejected) + ') - CodeChallenge';
        
        return stats;
    }
    
    // Mettre à jour les stats au chargement
    document.addEventListener('DOMContentLoaded', updateStats);
    
    // Fonction utilitaire pour copier du texte
    function copyToClipboard(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                showToast('Texte copié dans le presse-papiers', 'success', 2000);
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
                showToast('Texte copié dans le presse-papiers', 'success', 2000);
            } catch (err) {
                showToast('Erreur lors de la copie', 'error', 3000);
            }
            document.body.removeChild(textArea);
        }
    }
    
    // Ajouter des boutons de copie pour le code (optionnel)
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
    
    // Performance monitoring (optionnel)
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
";

// Include footer
include 'footer.php';
?>
