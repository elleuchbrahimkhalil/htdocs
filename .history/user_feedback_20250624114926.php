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
        background-color: #f8fafc;
        padding: 15px;
        border-radius: 8px;
        font-family: 'Courier New', monospace;
        overflow-x: auto;
        border: 1px solid #e2e8f0;
        max-height: 150px;
        font-size: 0.85em;
        margin-bottom: 10px;
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
        font-weight: 500;
        cursor: pointer;
        border: 1px solid;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .btn-primary {
        background: #3498db;
        color: white;
        border-color: #3498db;
    }

    .btn-primary:hover {
        background: #2980b9;
        border-color: #2980b9;
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

    .btn-success {
        background: #27ae60;
        color: white;
        border-color: #27ae60;
    }

    .btn-success:hover {
        background: #219653;
        border-color: #219653;
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

    .notification-tabs {
        display: flex;
        margin-bottom: 20px;
        background: white;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .notification-tab {
        flex: 1;
        padding: 15px 20px;
        text-align: center;
        cursor: pointer;
        border-bottom: 3px solid transparent;
        transition: all 0.3s;
        font-weight: 600;
    }

    .notification-tab.active {
        background: #f8f9fa;
        border-bottom-color: #3498db;
        color: #3498db;
    }

    .notification-tab:hover {
        background: #f8f9fa;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
        animation: fadeIn 0.3s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
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
        
        .btn {
            flex: 1;
            justify-content: center;
        }
        
        .notification-tabs {
            flex-direction: column;
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
$user = getCurrentUser();
$user_id = $user['id'];

// Initialiser les variables
$received_solutions = [];
$my_solutions = [];
$error_message = '';
$success_message = '';

try {
    $pdo = connect();
    
    if (!$pdo) {
        throw new Exception("Erreur de connexion à la base de données");
    }
    
    // Récupérer les solutions soumises pour les problèmes créés par l'utilisateur
    $stmt = $pdo->prepare("
        SELECT s.*, p.title as problem_title, u.username, u.name as solver_name,
               pr.amount as price, pr.currency
        FROM solutions s
        JOIN problems p ON s.problem_id = p.problem_id
        JOIN users u ON s.user_id = u.id
        LEFT JOIN prices pr ON s.price_id = pr.price_id
        WHERE p.user_id = ? AND s.user_id != ?
        ORDER BY s.created_at DESC
    ");
    
    $stmt->execute([$user_id, $user_id]);
    $received_solutions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer mes solutions soumises
    $stmt = $pdo->prepare("
        SELECT s.*, p.title as problem_title, p.user_id as problem_owner_id,
               u.username as problem_owner_username, u.name as problem_owner_name,
               pr.amount as price, pr.currency
        FROM solutions s
        JOIN problems p ON s.problem_id = p.problem_id
        JOIN users u ON p.user_id = u.id
        LEFT JOIN prices pr ON s.price_id = pr.price_id
        WHERE s.user_id = ?
        ORDER BY s.created_at DESC
    ");
    
    $stmt->execute([$user_id]);
    $my_solutions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
                SELECT s.*, p.user_id as problem_owner
                FROM solutions s
                JOIN problems p ON s.problem_id = p.problem_id
                WHERE s.id = ? AND p.user_id = ?
            ");
            
            $stmt->execute([$solution_id, $user_id]);
            $solution = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($solution) {
                // Mettre à jour le statut de la solution
                $stmt = $pdo->prepare("
                    UPDATE solutions
                    SET status = 'accepted', evaluated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                
                $result = $stmt->execute([$solution_id]);
                
                if ($result) {
                    $success_message = "La solution a été acceptée avec succès. Vous serez redirigé vers la page de paiement.";
                    
                    // Rediriger vers la page de paiement après 2 secondes
                    header("refresh:2;url=payment.php?solution_id=$solution_id");
                    
                    // Rafraîchir la liste des solutions
                    $stmt = $pdo->prepare("
                        SELECT s.*, p.title as problem_title, u.username, u.name as solver_name,
                               pr.amount as price, pr.currency
                        FROM solutions s
                        JOIN problems p ON s.problem_id = p.problem_id
                        JOIN users u ON s.user_id = u.id
                        LEFT JOIN prices pr ON s.price_id = pr.price_id
                        WHERE p.user_id = ? AND s.user_id != ?
                        ORDER BY s.created_at DESC
                    ");
                    
                    $stmt->execute([$user_id, $user_id]);
                    $received_solutions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $error_message = "Une erreur est survenue lors de l'acceptation de la solution.";
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
                // Mettre à jour le statut de la solution
                $stmt = $pdo->prepare("
                    UPDATE solutions
                    SET status = 'rejected', evaluated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                
                $result = $stmt->execute([$solution_id]);
                
                if ($result) {
                    $success_message = "La solution a été rejetée avec succès.";
                    
                    // Rafraîchir la liste des solutions
                    $stmt = $pdo->prepare("
                        SELECT s.*, p.title as problem_title, u.username, u.name as solver_name,
                               pr.amount as price, pr.currency
                        FROM solutions s
                        JOIN problems p ON s.problem_id = p.problem_id
                        JOIN users u ON s.user_id = u.id
                        LEFT JOIN prices pr ON s.price_id = pr.price_id
                        WHERE p.user_id = ? AND s.user_id != ?
                        ORDER BY s.created_at DESC
                    ");
                    
                    $stmt->execute([$user_id, $user_id]);
                    $received_solutions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $error_message = "Une erreur est survenue lors du rejet de la solution.";
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

<div class="notification-tabs">
    <div class="notification-tab active" data-tab="received">
        <i class="fas fa-inbox"></i> Solutions reçues (<?php echo count($received_solutions); ?>)
    </div>
    <div class="notification-tab" data-tab="sent">
        <i class="fas fa-paper-plane"></i> Mes solutions (<?php echo count($my_solutions); ?>)
    </div>
</div>

<!-- Onglet Solutions reçues -->
<div class="tab-content active" id="received-tab">
    <?php if (empty($received_solutions)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h3>Aucune solution reçue</h3>
            <p>Vous n'avez pas encore reçu de solutions pour vos problèmes publiés.</p>
            <a href="expublier.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Publier un problème
            </a>
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
                        <i class="fas fa-euro-sign"></i>
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
                            <form method="POST" action="" onsubmit="return confirm('Êtes-vous sûr de vouloir accepter cette solution? Vous serez redirigé vers la page de paiement.');" style="display: inline;">
                                <input type="hidden" name="solution_id" value="<?php echo $solution['id']; ?>">
                                <button type="submit" name="accept_solution" class="btn btn-success">
                                    <i class="fas fa-check"></i> Accepter (<?php echo number_format($solution['price'] ?? 0, 2); ?> €)
                                </button>
                            </form>
                            
                            <form method="POST" action="" onsubmit="return confirm('Êtes-vous sûr de vouloir rejeter cette solution?');" style="display: inline;">
                                <input type="hidden" name="solution_id" value="<?php echo $solution['id']; ?>">
                                <input type="hidden" name="reject_solution" value="1">
                                <button type="submit" class="btn btn-outline">
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
    <?php endif; ?>
</div>

<!-- Onglet Mes solutions -->
<div class="tab-content" id="sent-tab">
    <?php if (empty($my_solutions)): ?>
        <div class="empty-state">
            <i class="fas fa-paper-plane"></i>
            <h3>Aucune solution soumise</h3>
            <p>Vous n'avez pas encore soumis de solutions.</p>
            <a href="exacueil.php" class="btn btn-primary">
                <i class="fas fa-search"></i> Parcourir les problèmes
            </a>
        </div>
    <?php else: ?>
        <?php foreach ($my_solutions as $solution): ?>
            <div class="solution-card">
                <div class="solution-header">
                    <h3 class="solution-title">
                        <i class="fas fa-file-code"></i> 
                        <?php echo htmlspecialchars($solution['problem_title']); ?>
                    </h3>
                    <span class="solution-price">
                        <i class="fas fa-euro-sign"></i>
                        <?php echo number_format($solution['price'] ?? 0, 2); ?> €
                    </span>
                </div>
                
                <div class="solution-meta">
                    <span>
                        <i class="fas fa-user"></i> 
                        Auteur: <?php echo htmlspecialchars($solution['problem_owner_name'] ?? $solution['problem_owner_username']); ?>
                    </span>
                    <span>
                        <i class="fas fa-calendar-alt"></i> 
                        Soumis le <?php 
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
                        <h4><i class="fas fa-comment-alt"></i> Mon explication</h4>
                        <?php echo nl2br(htmlspecialchars($solution['explanation'])); ?>
                    </div>
                <?php endif; ?>
                
                <div class="solution-footer">
                    <a href="problem.php?id=<?php echo $solution['problem_id']; ?>" class="btn btn-outline">
                        <i class="fas fa-eye"></i> Voir le problème
                    </a>
                    
                    <?php if ($solution['status'] === 'pending'): ?>
                        <a href="submit_solution.php?problem_id=<?php echo $solution['problem_id']; ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Modifier ma solution
                        </a>
                    <?php elseif ($solution['status'] === 'accepted'): ?>
                        <span class="btn btn-success" style="cursor: default;">
                            <i class="fas fa-check-circle"></i> Solution acceptée
                        </span>
                    <?php elseif ($solution['status'] === 'rejected'): ?>
                        <a href="submit_solution.php?problem_id=<?php echo $solution['problem_id']; ?>" class="btn btn-primary">
                            <i class="fas fa-redo"></i> Soumettre une nouvelle solution
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php
// Additional scripts
$additional_scripts = "
    // Gestion des onglets
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('.notification-tab');
        const contents = document.querySelectorAll('.tab-content');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                const targetTab = this.dataset.tab;
                
                // Désactiver tous les onglets
                tabs.forEach(t => t.classList.remove('active'));
                contents.forEach(c => c.classList.remove('active'));
                
                // Activer l'onglet cliqué
                this.classList.add('active');
                document.getElementById(targetTab + '-tab').classList.add('active');
            });
        });
    });

    // Animation pour les boutons
    document.querySelectorAll('.btn').forEach(button => {
        button.addEventListener('mouseenter', () => {
            button.style.transform = 'translateY(-2px)';
            button.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
        });
        
        button.addEventListener('mouseleave', () => {
            button.style.transform = '';
            button.style.boxShadow = '';
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

    // Confirmation améliorée pour les actions
    document.addEventListener('DOMContentLoaded', function() {
        const acceptForms = document.querySelectorAll('form[onsubmit*=\"accepter\"]');
        acceptForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const priceElement = this.querySelector('button[name=\"accept_solution\"]');
                const priceText = priceElement ? priceElement.textContent.match(/([0-9,]+\\.?[0-9]*)/)[0] : '0.00';
                
                const confirmMessage = 'Êtes-vous sûr de vouloir accepter cette solution?\\n\\n' +
                    'Prix à payer: ' + priceText + ' €\\n' +
                    'Vous serez redirigé vers la page de paiement après confirmation.';
                
                if (confirm(confirmMessage)) {
                    // Désactiver le bouton pour éviter les doubles clics
                    const submitBtn = this.querySelector('button[type=\"submit\"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class=\"fas fa-spinner fa-spin\"></i> Traitement...';
                    }
                    
                    this.submit();
                }
            });
        });
        
        const rejectForms = document.querySelectorAll('form[onsubmit*=\"rejeter\"]');
        rejectForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const confirmMessage = 'Êtes-vous sûr de vouloir rejeter cette solution?\\n\\n' +
                    'Cette action est définitive et le développeur en sera informé.';
                
                if (confirm(confirmMessage)) {
                    // Désactiver le bouton pour éviter les doubles clics
                    const submitBtn = this.querySelector('button[type=\"submit\"]');
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class=\"fas fa-spinner fa-spin\"></i> Traitement...';
                    }
                    
                    this.submit();
                }
            });
        });
    });

    // Auto-refresh pour les solutions en attente
    document.addEventListener('DOMContentLoaded', function() {
        const pendingSolutions = document.querySelectorAll('.solution-status.pending');
        if (pendingSolutions.length > 0) {
            // Rafraîchir la page toutes les 2 minutes s'il y a des solutions en attente
            setTimeout(() => {
                if (confirm('Il y a des solutions en attente. Voulez-vous actualiser la page pour voir les mises à jour?')) {
                    window.location.reload();
                }
            }, 120000); // 2 minutes
        }
    });

    // Fonction pour copier le code d'une solution
    function copySolutionCode(button) {
        const card = button.closest('.solution-card');
        const codeElement = card.querySelector('.solution-content pre');
        
        if (codeElement) {
            const code = codeElement.textContent;
            navigator.clipboard.writeText(code).then(() => {
                // Changer temporairement le texte du bouton
                const originalText = button.innerHTML;
                button.innerHTML = '<i class=\"fas fa-check\"></i> Copié!';
                button.style.backgroundColor = '#27ae60';
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.backgroundColor = '';
                }, 2000);
            }).catch(err => {
                console.error('Erreur lors de la copie:', err);
                alert('Impossible de copier le code');
            });
        }
    }

    // Ajouter des boutons de copie aux solutions
    document.addEventListener('DOMContentLoaded', function() {
        const solutionContents = document.querySelectorAll('.solution-content');
        solutionContents.forEach(content => {
            const copyButton = document.createElement('button');
            copyButton.className = 'btn btn-outline';
            copyButton.style.position = 'absolute';
            copyButton.style.top = '10px';
            copyButton.style.right = '10px';
            copyButton.style.fontSize = '12px';
            copyButton.style.padding = '5px 10px';
            copyButton.innerHTML = '<i class=\"fas fa-copy\"></i> Copier';
            copyButton.onclick = () => copySolutionCode(copyButton);
            
            content.style.position = 'relative';
            content.appendChild(copyButton);
        });
    });
";

// Include footer
include 'footer.php';
?>
