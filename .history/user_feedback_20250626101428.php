<?php
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

    @media (max-width: 768px) {
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
";

// Include header
include 'header.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: exlogin.php');
    exit;
}

// Récupérer l'ID de l'utilisateur connecté
$user_id = $_SESSION['user_id'];

// Initialiser les variables
$received_solutions = [];
$error_message = '';
$success_message = '';

// Paramètres de filtrage et pagination
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    $pdo = connect();
    
    if (!$pdo) {
        throw new Exception("Erreur de connexion à la base de données");
    }
    
    // Construire la requête avec filtres
    $where_clause = "WHERE p.user_id = ? AND s.user_id != ?";
    $params = [$user_id, $user_id];
    
    if ($filter !== 'all') {
        $where_clause .= " AND s.status = ?";
        $params[] = $filter;
    }
    
    // Compter le total pour la pagination
    $count_stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM solutions s
        JOIN problems p ON s.problem_id = p.problem_id
        JOIN users u ON s.user_id = u.id
        LEFT JOIN prices pr ON s.price_id = pr.price_id
        $where_clause
    ");
    $count_stmt->execute($params);
    $total_count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    $total_pages = ceil($total_count / $per_page);
    
    // Récupérer les solutions avec pagination
    $stmt = $pdo->prepare("
        SELECT s.*, p.title as problem_title, u.username, u.name as solver_name,
               pr.amount as price, pr.currency
        FROM solutions s
        JOIN problems p ON s.problem_id = p.problem_id
        JOIN users u ON s.user_id = u.id
        LEFT JOIN prices pr ON s.price_id = pr.price_id
        $where_clause
        ORDER BY s.created_at DESC
        LIMIT $per_page OFFSET $offset
    ");
    $stmt->execute($params);
    $received_solutions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des solutions: " . $e->getMessage());
    $error_message = "Une erreur est survenue lors de la récupération des données.";
}

// Traiter l'acceptation d'une solution
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_solution'])) {
    $solution_id = isset($_POST['solution_id']) ? (int)$_POST['solution_id'] : 0;
    
    if ($solution_id > 0) {
        try {
            // Vérifier que la solution existe et appartient à un problème de l'utilisateur
            $stmt = $pdo->prepare("
                SELECT s.*, p.user_id as problem_owner, p.points
                FROM solutions s
                JOIN problems p ON s.problem_id = p.problem_id
                WHERE s.id = ? AND p.user_id = ?
            ");
            
            $stmt->execute([$solution_id, $user_id]);
            $solution = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($solution) {
                if ($solution['status'] === 'pending') {
                    // Mettre à jour le statut de la solution
                    $stmt = $pdo->prepare("
                        UPDATE solutions
                        SET status = 'accepted', evaluated_at = NOW()
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
        } catch (Exception $e) {
            error_log("Erreur lors de l'acceptation de la solution: " . $e->getMessage());
            $error_message = "Une erreur est survenue lors de l'acceptation de la solution.";
        }
    } else {
        $error_message = "ID de solution invalide.";
    }
}

// Traiter le rejet d'une solution
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_solution'])) {
    $solution_id = isset($_POST['solution_id']) ? (int)$_POST['solution_id'] : 0;
    
    if ($solution_id > 0) {
        try {
            // Vérifier que la solution existe et appartient à un problème de l'utilisateur
            $stmt = $pdo->prepare("
                SELECT s.*, p.user_id as problem_owner
                FROM solutions s
                JOIN problems p ON s.problem_id = p.problem_id
                WHERE s.id = ? AND p.user_id = ?
            ");
            
            $stmt->execute([$solution_id, $user_id]);
            $solution = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($solution) {
                if ($solution['status'] === 'pending') {
                    // Mettre à jour le statut de la solution
                    $stmt = $pdo->prepare("
                        UPDATE solutions
                        SET status = 'rejected', evaluated_at = NOW()
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
        } catch (Exception $e) {
            error_log("Erreur lors du rejet de la solution: " . $e->getMessage());
            $error_message = "Une erreur est survenue lors du rejet de la solution.";
        }
    } else {
        $error_message = "ID de solution invalide.";
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

<!-- Section de filtrage -->
<div class="filter-section">
    <form method="GET" action="">
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
        <?php else: ?>
            <p>Aucune solution avec le statut "<?php echo htmlspecialchars($filter); ?>" trouvée.</p>
            <a href="?filter=all" class="btn btn-primary">Voir toutes les solutions</a>
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
                    $date = new DateTime($solution['created_at'] ?? date('Y-m-d H:i:s'));
                    echo $date->format('d/m/Y à H:i'); 
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
                <pre><?php echo htmlspecialchars($solution['solution_code']); ?></pre>
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
                        <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir accepter cette solution? Vous serez redirigé vers la page de paiement.');">
                            <input type="hidden" name="solution_id" value="<?php echo $solution['id']; ?>">
                            <button type="submit" name="accept_solution" value="1" class="btn btn-primary">
                                <i class="fas fa-check"></i> Accepter (<?php echo number_format($solution['price'] ?? 0, 2); ?> €)
                            </button>
                        </form>
                        
                        <form method="POST" action="" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir rejeter cette solution?');">
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
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    
    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
        <div style="display: flex; justify-content: center; margin-top: 30px; gap: 10px;">
            <?php if ($page > 1): ?>
                <a href="?filter=<?php echo urlencode($filter); ?>&page=<?php echo $page - 1; ?>" class="btn btn-outline">
                    <i class="fas fa-chevron-left"></i> Précédent
                </a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <?php if ($i == $page): ?>
                    <span class="btn btn-primary" style="cursor: default;">
                        <?php echo $i; ?>
                    </span>
                <?php else: ?>
                    <a href="?filter=<?php echo urlencode($filter); ?>&page=<?php echo $i; ?>" class="btn btn-outline">
                        <?php echo $i; ?>
                    </a>
                <?php endif; ?>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?filter=<?php echo urlencode($filter); ?>&page=<?php echo $page + 1; ?>" class="btn btn-outline">
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
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class=\"fas fa-spinner fa-spin\"></i> Traitement...';
                
                // Réactiver après 5 secondes en cas de problème
                setTimeout(() => {
                    submitBtn.disabled = false;
                    if (submitBtn.name === 'accept_solution') {
                        submitBtn.innerHTML = '<i class=\"fas fa-check\"></i> Accepter';
                    } else if (submitBtn.name === 'reject_solution') {
                        submitBtn.innerHTML = '<i class=\"fas fa-times\"></i> Rejeter';
                    }
                }, 5000);
            }
        });
    });
    
    // Auto-refresh si une action est en cours
    " . (!empty($success_message) && strpos($success_message, 'paiement') !== false ? "
    let countdown = 3;
    const countdownInterval = setInterval(() => {
        countdown--;
        if (countdown <= 0) {
            clearInterval(countdownInterval);
        }
    }, 1000);
    " : "") . "
    
    // Confirmation améliorée pour les actions
    function confirmAction(action, solutionTitle, price) {
        if (action === 'accept') {
            return confirm('Accepter cette solution pour \"' + solutionTitle + '\" ?\\n\\nVous devrez payer ' + price + ' € au développeur.');
        } else if (action === 'reject') {
            return confirm('Rejeter cette solution pour \"' + solutionTitle + '\" ?\\n\\nCette action est définitive.');
        }
        return true;
    }
    
    // Améliorer les confirmations existantes
    document.querySelectorAll('form[onsubmit]').forEach(form => {
        const originalOnsubmit = form.getAttribute('onsubmit');
        form.removeAttribute('onsubmit');
        
        form.addEventListener('submit', function(e) {
            const isAccept = form.querySelector('button[name=\"accept_solution\"]');
            const isReject = form.querySelector('button[name=\"reject_solution\"]');
            const solutionTitle = form.closest('.solution-card').querySelector('.solution-title').textContent.trim();
            const price = form.closest('.solution-card').querySelector('.solution-price').textContent.trim();
            
            let confirmed = false;
            if (isAccept) {
                confirmed = confirmAction('accept', solutionTitle, price);
            } else if (isReject) {
                confirmed = confirmAction('reject', solutionTitle, price);
            } else {
                confirmed = eval(originalOnsubmit);
            }
            
            if (!confirmed) {
                e.preventDefault();
                return false;
            }
        });
    });
    
    // Notification toast pour les messages
    function showToast(message, type = 'info') {
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
            max-width: 300px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transform: translateX(100%);
            transition: transform 0.3s ease;
        `;
        
        switch(type) {
            case 'success':
                toast.style.backgroundColor = '#4CAF50';
                break;
            case 'error':
                toast.style.backgroundColor = '#f44336';
                break;
            case 'warning':
                toast.style.backgroundColor = '#ff9800';
                break;
            default:
                toast.style.backgroundColor = '#2196F3';
        }
        
        toast.textContent = message;
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
        }, 5000);
    }
    
    // Afficher les messages existants comme toast
    " . (!empty($success_message) ? "showToast('" . addslashes($success_message) . "', 'success');" : "") . "
    " . (!empty($error_message) ? "showToast('" . addslashes($error_message) . "', 'error');" : "") . "
";

// Include footer
include 'footer.php';
?>
