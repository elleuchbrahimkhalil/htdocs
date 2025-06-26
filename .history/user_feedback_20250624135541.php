<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: exlogin.php');
    exit;
}

// Configuration de la page
$page_title = "Mes Solutions";
$additional_css = "
    .feedback-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    .stats-summary {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        text-align: center;
        border-top: 4px solid #3498db;
        transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.12);
    }
    
    .stat-card.received {
        border-top-color: #3498db;
    }
    
    .stat-card.purchased {
        border-top-color: #27ae60;
    }
    
    .stat-card.earnings {
        border-top-color: #f39c12;
    }
    
    .stat-card.pending {
        border-top-color: #e74c3c;
    }
    
    .stat-number {
        font-size: 2.5em;
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 5px;
        display: block;
    }
    
    .stat-label {
        color: #7f8c8d;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .tabs-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        overflow: hidden;
        margin-bottom: 30px;
    }
    
    .tabs-header {
        display: flex;
        background: #f8f9fa;
        border-bottom: 1px solid #e0e0e0;
    }
    
    .tab-button {
        flex: 1;
        padding: 20px;
        background: none;
        border: none;
        cursor: pointer;
        font-weight: 600;
        color: #666;
        transition: all 0.3s;
        position: relative;
    }
    
    .tab-button.active {
        color: #3498db;
        background: white;
    }
    
    .tab-button.active::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: #3498db;
    }
    
    .tab-button:hover {
        background: #f0f0f0;
    }
    
    .tab-button.active:hover {
        background: white;
    }
    
    .tab-content {
        display: none;
        padding: 30px;
    }
    
    .tab-content.active {
        display: block;
        animation: fadeIn 0.3s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .solution-card {
        border: 1px solid #e0e0e0;
        border-radius: 12px;
        padding: 25px;
        background: white;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 20px;
        transition: all 0.3s;
        position: relative;
    }
    
    .solution-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    }
    
    .solution-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .solution-title {
        font-size: 20px;
        font-weight: bold;
        color: #2c3e50;
        margin: 0;
        flex: 1;
        min-width: 200px;
    }
    
    .solution-price {
        font-size: 18px;
        font-weight: bold;
        color: #27ae60;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .solution-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-bottom: 20px;
        font-size: 14px;
        color: #666;
    }
    
    .solution-meta span {
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .solution-status {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .solution-status.pending {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
    }
    
    .solution-status.accepted {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .solution-status.rejected {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .solution-status.paid {
        background: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }
    
    .solution-content {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        border: 1px solid #e0e0e0;
    }
    
    .solution-code {
        font-family: 'Courier New', monospace;
        font-size: 13px;
        line-height: 1.6;
        max-height: 200px;
        overflow-y: auto;
        white-space: pre-wrap;
        word-break: break-all;
    }
    
    .solution-explanation {
        background: #e8f4fd;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 4px solid #3498db;
    }
    
    .solution-explanation h4 {
        margin-top: 0;
        color: #2980b9;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .solution-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-top: 1px solid #f0f0f0;
        padding-top: 20px;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .solution-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .btn {
        padding: 10px 20px;
        border-radius: 6px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.3s;
        border: none;
        font-size: 14px;
    }
    
    .btn-primary {
        background: #3498db;
        color: white;
    }
    
    .btn-primary:hover {
        background: #2980b9;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
    }
    
    .btn-success {
        background: #27ae60;
        color: white;
    }
    
    .btn-success:hover {
        background: #219653;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
    }
    
    .btn-danger {
        background: #e74c3c;
        color: white;
    }
    
    .btn-danger:hover {
        background: #c0392b;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(231, 76, 60, 0.3);
    }
    
    .btn-outline {
        background: transparent;
        color: #666;
        border: 1px solid #ddd;
    }
    
    .btn-outline:hover {
        background: #f8f9fa;
        border-color: #3498db;
        color: #3498db;
    }
    
    .btn-download {
        background: #17a2b8;
        color: white;
    }
    
    .btn-download:hover {
        background: #138496;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #666;
    }
    
    .empty-state i {
        font-size: 4em;
        color: #ddd;
        margin-bottom: 20px;
    }
    
    .empty-state h3 {
        color: #666;
        margin-bottom: 15px;
    }
    
    .empty-state p {
        color: #999;
        margin-bottom: 25px;
    }
    
    .purchased-solution-card {
        border-left: 4px solid #27ae60;
        background: linear-gradient(135deg, #ffffff 0%, #f8fff8 100%);
    }
    
    .purchased-badge {
        position: absolute;
        top: 15px;
        right: 15px;
        background: #27ae60;
        color: white;
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: bold;
    }
    
    .filter-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .filter-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .filter-btn {
        padding: 8px 16px;
        border: 1px solid #ddd;
        background: white;
        border-radius: 20px;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 13px;
    }
    
    .filter-btn.active {
        background: #3498db;
        color: white;
        border-color: #3498db;
    }
    
    .search-box {
        padding: 10px 15px;
        border: 1px solid #ddd;
        border-radius: 20px;
        width: 250px;
        font-size: 14px;
    }
    
    .search-box:focus {
        border-color: #3498db;
        outline: none;
        box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
    }
    
    @media (max-width: 768px) {
        .feedback-container {
            padding: 10px;
        }
        
        .stats-summary {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .tabs-header {
            flex-direction: column;
        }
        
        .solution-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .solution-meta {
            flex-direction: column;
            gap: 10px;
        }
        
        .solution-footer {
            flex-direction: column;
            align-items: stretch;
        }
        
        .solution-actions {
            justify-content: center;
        }
        
        .filter-bar {
            flex-direction: column;
            align-items: stretch;
        }
        
        .search-box {
            width: 100%;
        }
    }
";

$user_id = $_SESSION['user_id'];

// Inclure l'en-tête
include 'header.php';

try {
    $pdo = connect();
    
    if (!$pdo) {
        throw new Exception("Erreur de connexion à la base de données");
    }
    
    // Récupérer les statistiques
    $stats = [];
    
    // Solutions reçues pour mes problèmes
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM solutions s
        JOIN problems p ON s.problem_id = p.problem_id
        WHERE p.user_id = ? AND s.user_id != ?
    ");
    $stmt->execute([$user_id, $user_id]);
    $stats['received'] = $stmt->fetchColumn();
    
    // Solutions achetées
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM payments pay
        WHERE pay.payer_id = ? AND pay.status = 'completed'
    ");
    $stmt->execute([$user_id]);
    $stats['purchased'] = $stmt->fetchColumn();
    
    // Gains totaux
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(pay.amount), 0) as total
        FROM payments pay
        JOIN solutions s ON pay.solution_id = s.id
        WHERE s.user_id = ? AND pay.status = 'completed'
    ");
    $stmt->execute([$user_id]);
    $stats['earnings'] = $stmt->fetchColumn();
    
    // Solutions en attente
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count
        FROM solutions s
        JOIN problems p ON s.problem_id = p.problem_id
        WHERE p.user_id = ? AND s.user_id != ? AND s.status = 'pending'
    ");
    $stmt->execute([$user_id, $user_id]);
    $stats['pending'] = $stmt->fetchColumn();
    
    // Récupérer les solutions reçues pour mes problèmes
    $stmt = $pdo->prepare("
        SELECT s.*, p.title as problem_title, u.username, u.name as solver_name,
               pr.amount, pr.currency, pay.id as payment_id, pay.status as payment_status
        FROM solutions s
        JOIN problems p ON s.problem_id = p.problem_id
        JOIN users u ON s.user_id = u.id
        LEFT JOIN prices pr ON s.price_id = pr.price_id
        LEFT JOIN payments pay ON s.id = pay.solution_id
        WHERE p.user_id = ? AND s.user_id != ?
        ORDER BY s.created_at DESC    ");
    $stmt->execute([$user_id, $user_id]);
    $received_solutions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les solutions achetées
    $stmt = $pdo->prepare("
        SELECT s.*, p.title as problem_title, p.description as problem_description,
               u.username as problem_owner, pr.amount, pr.currency,
               pay.payment_date, pay.transaction_id, pay.status as payment_status
        FROM payments pay
        JOIN solutions s ON pay.solution_id = s.id
        JOIN problems p ON s.problem_id = p.problem_id
        JOIN users u ON p.user_id = u.id
        LEFT JOIN prices pr ON s.price_id = pr.price_id
        WHERE pay.payer_id = ? AND pay.status = 'completed'
        ORDER BY pay.payment_date DESC
    ");
    $stmt->execute([$user_id]);
    $purchased_solutions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des données: " . $e->getMessage());
    $error_message = "Une erreur est survenue lors de la récupération des données.";
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['accept_solution'])) {
        $solution_id = (int)$_POST['solution_id'];
        
        try {
            $stmt = $pdo->prepare("
                UPDATE solutions 
                SET status = 'accepted', evaluated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND problem_id IN (
                    SELECT problem_id FROM problems WHERE user_id = ?
                )
            ");
            $result = $stmt->execute([$solution_id, $user_id]);
            
            if ($result) {
                $_SESSION['success_message'] = "Solution acceptée avec succès!";
                header('Location: ' . $_SERVER['PHP_SELF'] . '#received');
                exit;
            }
        } catch (Exception $e) {
            error_log("Erreur lors de l'acceptation: " . $e->getMessage());
            $_SESSION['error_message'] = "Erreur lors de l'acceptation de la solution.";
        }
    }
    
    if (isset($_POST['reject_solution'])) {
        $solution_id = (int)$_POST['solution_id'];
        $feedback = trim($_POST['feedback'] ?? '');
        
        try {
            $stmt = $pdo->prepare("
                UPDATE solutions 
                SET status = 'rejected', feedback = ?, evaluated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND problem_id IN (
                    SELECT problem_id FROM problems WHERE user_id = ?
                )
            ");
            $result = $stmt->execute([$feedback, $solution_id, $user_id]);
            
            if ($result) {
                $_SESSION['success_message'] = "Solution rejetée.";
                header('Location: ' . $_SERVER['PHP_SELF'] . '#received');
                exit;
            }
        } catch (Exception $e) {
            error_log("Erreur lors du rejet: " . $e->getMessage());
            $_SESSION['error_message'] = "Erreur lors du rejet de la solution.";
        }
    }
}
?>

<div class="feedback-container">
    <h1><i class="fas fa-chart-line"></i> Tableau de Bord des Solutions</h1>
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($_SESSION['success_message']); ?>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($_SESSION['error_message']); ?>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>
    
    <!-- Statistiques -->
    <div class="stats-summary">
        <div class="stat-card received">
            <span class="stat-number"><?php echo $stats['received']; ?></span>
            <div class="stat-label">Solutions Reçues</div>
        </div>
        
        <div class="stat-card purchased">
            <span class="stat-number"><?php echo $stats['purchased']; ?></span>
            <div class="stat-label">Solutions Achetées</div>
        </div>
        
        <div class="stat-card earnings">
            <span class="stat-number"><?php echo number_format($stats['earnings'], 2); ?> €</span>
            <div class="stat-label">Gains Totaux</div>
        </div>
        
        <div class="stat-card pending">
            <span class="stat-number"><?php echo $stats['pending']; ?></span>
            <div class="stat-label">En Attente</div>
        </div>
    </div>
    
    <!-- Onglets -->
    <div class="tabs-container">
        <div class="tabs-header">
            <button class="tab-button active" onclick="switchTab('received')">
                <i class="fas fa-inbox"></i> Solutions Reçues (<?php echo count($received_solutions); ?>)
            </button>
            <button class="tab-button" onclick="switchTab('purchased')">
                <i class="fas fa-shopping-cart"></i> Solutions Achetées (<?php echo count($purchased_solutions); ?>)
            </button>
        </div>
        
        <!-- Onglet Solutions Reçues -->
        <div id="received-tab" class="tab-content active">
            <div class="filter-bar">
                <div class="filter-buttons">
                    <button class="filter-btn active" onclick="filterSolutions('all')">Toutes</button>
                    <button class="filter-btn" onclick="filterSolutions('pending')">En attente</button>
                    <button class="filter-btn" onclick="filterSolutions('accepted')">Acceptées</button>
                    <button class="filter-btn" onclick="filterSolutions('rejected')">Rejetées</button>
                </div>
                <input type="text" class="search-box" placeholder="Rechercher..." onkeyup="searchSolutions(this.value)">
            </div>
            
            <?php if (empty($received_solutions)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>Aucune solution reçue</h3>
                    <p>Vous n'avez pas encore reçu de solutions pour vos problèmes.</p>
                    <a href="expublier.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Publier un problème
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($received_solutions as $solution): ?>
                    <div class="solution-card" data-status="<?php echo $solution['status']; ?>" data-title="<?php echo strtolower($solution['problem_title']); ?>">
                        <div class="solution-header">
                            <h3 class="solution-title">
                                <i class="fas fa-code"></i> <?php echo htmlspecialchars($solution['problem_title']); ?>
                            </h3>
                            <div class="solution-price">
                                <i class="fas fa-tag"></i>
                                <?php echo number_format($solution['amount'] ?? 0, 2); ?> €
                            </div>
                        </div>
                        
                        <div class="solution-meta">
                            <span>
                                <i class="fas fa-user"></i>
                                Par <?php echo htmlspecialchars($solution['solver_name'] ?? $solution['username']); ?>
                            </span>
                            <span>
                                <i class="fas fa-calendar"></i>
                                <?php echo date('d/m/Y à H:i', strtotime($solution['created_at'])); ?>
                            </span>
                            <span class="solution-status <?php echo $solution['status']; ?>">
                                <?php
                                $status_labels = [
                                    'pending' => 'En attente',
                                    'accepted' => 'Acceptée',
                                    'rejected' => 'Rejetée',
                                    'paid' => 'Payée'
                                ];
                                echo $status_labels[$solution['status']] ?? $solution['status'];
                                ?>
                            </span>
                        </div>
                        
                        <div class="solution-content">
                            <div class="solution-code">
                                <?php echo htmlspecialchars($solution['solution_code']); ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($solution['explanation'])): ?>
                            <div class="solution-explanation">
                                <h4><i class="fas fa-comment-alt"></i> Explication</h4>
                                <?php echo nl2br(htmlspecialchars($solution['explanation'])); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="solution-footer">
                            <a href="problem.php?id=<?php echo $solution['problem_id']; ?>" class="btn btn-outline">
                                <i class="fas fa-eye"></i> Voir le problème
                            </a>
                            
                            <div class="solution-actions">
                                <?php if ($solution['status'] === 'pending'): ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Accepter cette solution?')">
                                        <input type="hidden" name="solution_id" value="<?php echo $solution['id']; ?>">
                                        <button type="submit" name="accept_solution" class="btn btn-success">
                                            <i class="fas fa-check"></i> Accepter
                                        </button>
                                    </form>
                                    
                                    <button class="btn btn-danger" onclick="showRejectModal(<?php echo $solution['id']; ?>)">
                                        <i class="fas fa-times"></i> Rejeter
                                    </button>
                                <?php elseif ($solution['status'] === 'accepted' && !$solution['payment_id']): ?>
                                    <a href="payment.php?solution_id=<?php echo $solution['id']; ?>" class="btn btn-success">
                                        <i class="fas fa-credit-card"></i> Payer
                                    </a>
                                <?php elseif ($solution['payment_status'] === 'completed'): ?>
                                    <span class="btn btn-success" style="cursor: default;">
                                        <i class="fas fa-check-circle"></i> Payée
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <!-- Onglet Solutions Achetées -->
        <div id="purchased-tab" class="tab-content">
            <?php if (empty($purchased_solutions)): ?>
                <div class="empty-state">
                    <i class="fas fa-shopping-cart"></i>
                    <h3>Aucune solution achetée</h3>
                    <p>Vous n'avez pas encore acheté de solutions.</p>
                    <a href="exacueil.php" class="btn btn-primary">
                        <i class="fas fa-search"></i> Parcourir les problèmes
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($purchased_solutions as $solution): ?>
                    <div class="solution-card purchased-solution-card">
                        <div class="purchased-badge">Acheté</div>
                        
                        <div class="solution-header">
                            <h3 class="solution-title">
                                <i class="fas fa-code"></i> <?php echo htmlspecialchars($solution['problem_title']); ?>
                            </h3>
                            <div class="solution-price">
                                <i class="fas fa-tag"></i>
                                <?php echo number_format($solution['amount'], 2); ?> €
                            </div>
                        </div>
                        
                        <div class="solution-meta">
                            <span>
                                <i class="fas fa-user"></i>
                                Propriétaire: <?php echo htmlspecialchars($solution['problem_owner']); ?>
                            </span>
                            <span>
                                <i class="fas fa-calendar"></i>
                                Acheté le: <?php echo date('d/m/Y à H:i', strtotime($solution['payment_date'])); ?>
                            </span>
                            <span>
                                <i class="fas fa-receipt"></i>
                                Transaction: <?php echo htmlspecialchars($solution['transaction_id']); ?>
                            </span>
                        </div>
                        
                        <div class="solution-content">
                            <div class="solution-code">
                                <?php echo htmlspecialchars($solution['solution_code']); ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($solution['explanation'])): ?>
                            <div class="solution-explanation">
                                <h4><i class="fas fa-comment-alt"></i> Explication</h4>
                                <?php echo nl2br(htmlspecialchars($solution['explanation'])); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="solution-footer">
                            <a href="problem.php?id=<?php echo $solution['problem_id']; ?>" class="btn btn-outline">
                                <i class="fas fa-eye"></i> Voir le problème
                            </a>
                            
                            <div class="solution-actions">
                                <button class="btn btn-download" onclick="downloadSolution(<?php echo $solution['id']; ?>)">
                                    <i class="fas fa-download"></i> Télécharger
                                </button>
                                <button class="btn btn-primary" onclick="copySolution(<?php echo $solution['id']; ?>)">
                                    <i class="fas fa-copy"></i> Copier
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de rejet -->
<div id="reject-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Rejeter la solution</h3>
            <button class="modal-close" onclick="closeRejectModal()">&times;</button>
        </div>
        <form method="POST" id="reject-form">
            <div class="modal-body">
                <input type="hidden" name="solution_id" id="reject-solution-id">
                <div class="form-group">
                    <label for="feedback">Raison du rejet (optionnel):</label>

                    <textarea id="feedback" name="feedback" rows="4" placeholder="Expliquez pourquoi vous rejetez cette solution..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeRejectModal()">Annuler</button>
                <button type="submit" name="reject_solution" class="btn btn-danger">
                    <i class="fas fa-times"></i> Rejeter
                </button>
            </div>
        </form>
    </div>
</div>

<?php
$additional_scripts = "
    // Gestion des onglets
    function switchTab(tabName) {
        // Masquer tous les contenus d'onglets
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        
        // Désactiver tous les boutons d'onglets
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('active');
        });
        
        // Activer l'onglet sélectionné
        document.getElementById(tabName + '-tab').classList.add('active');
        event.target.classList.add('active');
        
        // Mettre à jour l'URL avec l'ancre
        window.location.hash = tabName;
    }
    
    // Filtrage des solutions
    function filterSolutions(status) {
        const cards = document.querySelectorAll('#received-tab .solution-card');
        const buttons = document.querySelectorAll('.filter-btn');
        
        // Mettre à jour les boutons
        buttons.forEach(btn => btn.classList.remove('active'));
        event.target.classList.add('active');
        
        // Filtrer les cartes
        cards.forEach(card => {
            if (status === 'all' || card.dataset.status === status) {
                card.style.display = 'block';
                card.style.animation = 'fadeIn 0.3s ease';
            } else {
                card.style.display = 'none';
            }
        });
    }
    
    // Recherche dans les solutions
    function searchSolutions(query) {
        const cards = document.querySelectorAll('#received-tab .solution-card');
        const searchTerm = query.toLowerCase();
        
        cards.forEach(card => {
            const title = card.dataset.title;
            const content = card.textContent.toLowerCase();
            
            if (title.includes(searchTerm) || content.includes(searchTerm)) {
                card.style.display = 'block';
                card.style.animation = 'fadeIn 0.3s ease';
            } else {
                card.style.display = 'none';
            }
        });
    }
    
    // Modal de rejet
    function showRejectModal(solutionId) {
        document.getElementById('reject-solution-id').value = solutionId;
        document.getElementById('reject-modal').style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }
    
    function closeRejectModal() {
        document.getElementById('reject-modal').style.display = 'none';
        document.body.style.overflow = 'auto';
        document.getElementById('feedback').value = '';
    }
    
    // Téléchargement de solution
    function downloadSolution(solutionId) {
        // Créer un élément de téléchargement
        const element = document.createElement('a');
        const solutionCard = document.querySelector('[data-solution-id=\"' + solutionId + '\"]');
        
        if (!solutionCard) {
            // Fallback: récupérer le contenu depuis la carte visible
            const cards = document.querySelectorAll('.purchased-solution-card');
            let solutionContent = '';
            let filename = 'solution.txt';
            
            cards.forEach(card => {
                const codeElement = card.querySelector('.solution-code');
                const titleElement = card.querySelector('.solution-title');
                if (codeElement && titleElement) {
                    solutionContent = codeElement.textContent;
                    filename = titleElement.textContent.replace(/[^a-z0-9]/gi, '_').toLowerCase() + '.txt';
                    return false; // Break
                }
            });
            
            const file = new Blob([solutionContent], {type: 'text/plain'});
            element.href = URL.createObjectURL(file);
            element.download = filename;
            document.body.appendChild(element);
            element.click();
            document.body.removeChild(element);
            
            showMessage('Solution téléchargée!', 'success');
        }
    }
    
    // Copier la solution
    function copySolution(solutionId) {
        const cards = document.querySelectorAll('.purchased-solution-card');
        let solutionContent = '';
        
        cards.forEach(card => {
            const codeElement = card.querySelector('.solution-code');
            if (codeElement) {
                solutionContent = codeElement.textContent;
                return false; // Break
            }
        });
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(solutionContent).then(() => {
                showMessage('Solution copiée dans le presse-papiers!', 'success');
            }).catch(() => {
                fallbackCopyTextToClipboard(solutionContent);
            });
        } else {
            fallbackCopyTextToClipboard(solutionContent);
        }
    }
    
    // Fallback pour la copie
    function fallbackCopyTextToClipboard(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.top = '0';
        textArea.style.left = '0';
        textArea.style.position = 'fixed';
        
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            showMessage('Solution copiée!', 'success');
        } catch (err) {
            showMessage('Impossible de copier la solution', 'error');
        }
        
        document.body.removeChild(textArea);
    }
    
    // Affichage des messages
    function showMessage(message, type = 'info') {
        const messageDiv = document.createElement('div');
        messageDiv.className = 'alert alert-' + (type === 'error' ? 'danger' : type);
        messageDiv.innerHTML = '<i class=\"fas fa-' + (type === 'success' ? 'check-circle' : 'info-circle') + '\"></i> ' + message;
        messageDiv.style.position = 'fixed';
        messageDiv.style.top = '20px';
        messageDiv.style.right = '20px';
        messageDiv.style.zIndex = '9999';
        messageDiv.style.minWidth = '300px';
        messageDiv.style.animation = 'slideInRight 0.3s ease';
        
        document.body.appendChild(messageDiv);
        
        setTimeout(() => {
            messageDiv.style.animation = 'slideOutRight 0.3s ease';
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.parentNode.removeChild(messageDiv);
                }
            }, 300);
        }, 3000);
    }
    
    // Animations CSS
    const style = document.createElement('style');
    style.textContent = \`
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        
        .modal-content {
            background: white;
            border-radius: 12px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid #eee;
        }
        
        .modal-header h3 {
            margin: 0;
            color: #2c3e50;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-close:hover {
            color: #333;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            padding: 20px;
            border-top: 1px solid #eee;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
            resize: vertical;
        }
        
        .form-group textarea:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
    \`;
    document.head.appendChild(style);
    
    // Initialisation
    document.addEventListener('DOMContentLoaded', function() {
        // Vérifier l'ancre dans l'URL
        const hash = window.location.hash.substring(1);
        if (hash === 'purchased') {
            switchTab('purchased');
        }
        
        // Animation d'apparition des cartes
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
        
        // Animation des statistiques
        const statNumbers = document.querySelectorAll('.stat-number');
        statNumbers.forEach(stat => {
            const finalValue = parseInt(stat.textContent) || parseFloat(stat.textContent) || 0;
            let currentValue = 0;
            const increment = finalValue / 50;
            const isFloat = stat.textContent.includes('.');
            
            const timer = setInterval(() => {
                currentValue += increment;
                if (currentValue >= finalValue) {
                    currentValue = finalValue;
                    clearInterval(timer);
                }
                
                if (isFloat) {
                    stat.textContent = currentValue.toFixed(2) + ' €';
                } else {
                    stat.textContent = Math.floor(currentValue);
                }
            }, 20);
        });
        
        // Fermer le modal en cliquant à l'extérieur
        document.getElementById('reject-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeRejectModal();
            }
        });
        
        // Fermer le modal avec Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeRejectModal();
            }
        });
    });
    
    // Mise à jour automatique des statistiques
    setInterval(function() {
        // Ici vous pourriez ajouter une requête AJAX pour mettre à jour les stats
        // sans recharger la page
    }, 30000); // Toutes les 30 secondes
";

include 'footer.php';
?>
