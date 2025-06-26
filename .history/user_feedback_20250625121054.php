<?php
// Set page title
$page_title = "Solutions Reçues - Mes Problèmes";

// Additional CSS specific to this page
$additional_css = "
    .page-header {
        background: linear-gradient(135deg, #3498db 0%, #2c3e50 100%);
        color: white;
        padding: 30px;
        border-radius: 12px;
        margin-bottom: 30px;
        text-align: center;
    }

    .page-header h1 {
        margin: 0;
        font-size: 2.5em;
        font-weight: 300;
    }

    .page-header p {
        margin: 10px 0 0 0;
        opacity: 0.9;
        font-size: 1.1em;
    }

    .stats-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        text-align: center;
        border-left: 5px solid #3498db;
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }

    .stat-card.earnings { border-left-color: #27ae60; }
    .stat-card.pending { border-left-color: #f39c12; }
    .stat-card.accepted { border-left-color: #27ae60; }
    .stat-card.rejected { border-left-color: #e74c3c; }

    .stat-value {
        font-size: 2.5em;
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 8px;
        display: block;
    }

    .stat-label {
        color: #7f8c8d;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.9em;
        letter-spacing: 1px;
    }

    .solution-card {
        border: 1px solid #e1e8ed;
        border-radius: 15px;
        padding: 25px;
        background-color: white;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        transition: all 0.3s ease;
        margin-bottom: 25px;
        position: relative;
        overflow: hidden;
    }

    .solution-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #3498db, #2ecc71);
    }

    .solution-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f8f9fa;
    }

    .solution-title {
        font-weight: 700;
        font-size: 1.3em;
        color: #2c3e50;
        margin: 0;
        line-height: 1.3;
    }

    .solution-title a {
        color: #2c3e50;
        text-decoration: none;
    }

    .solution-title a:hover {
        color: #3498db;
    }

    .solution-price {
        background: linear-gradient(135deg, #2ecc71, #27ae60);
        color: white;
        padding: 8px 15px;
        border-radius: 20px;
        font-weight: bold;
        font-size: 1.1em;
        box-shadow: 0 2px 10px rgba(46, 204, 113, 0.3);
    }

    .solution-status {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 15px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.85em;
        margin-bottom: 15px;
    }

    .solution-status.pending {
        background: #fef9e7;
        color: #7b1fa2;
        border: 1px solid #ce93d8;
    }

    .solution-status.accepted {
        background: #e8f5e8;
        color: #2e7d32;
        border: 1px solid #a5d6a7;
    }

    .solution-status.rejected {
        background: #ffebee;
        color: #c62828;
        border: 1px solid #ef9a9a;
    }

    .developer-info {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 20px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 10px;
    }

    .developer-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid #3498db;
    }

    .developer-details {
        flex: 1;
    }

    .developer-name {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 5px;
    }

    .developer-username {
        color: #7f8c8d;
        font-size: 0.9em;
        margin-bottom: 5px;
    }

    .solution-date {
        color: #7f8c8d;
        font-size: 0.85em;
    }

    .solution-code {
        background: #2c3e50;
        color: #ecf0f1;
        padding: 20px;
        border-radius: 10px;
        font-family: 'Courier New', monospace;
        overflow-x: auto;
        margin-bottom: 20px;
        position: relative;
    }

    .copy-code-btn {
        position: absolute;
        top: 10px;
        right: 10px;
        background: #34495e;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 5px;
        cursor: pointer;
        font-size: 0.8em;
    }

    .copy-code-btn:hover {
        background: #4a6741;
    }

    .solution-explanation {
        background: #f0f7fb;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 20px;
        border-left: 4px solid #3498db;
    }

    .solution-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .btn {
        padding: 12px 20px;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s ease;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 0.9em;
    }

    .btn-success {
        background: linear-gradient(135deg, #2ecc71, #27ae60);
        color: white;
        box-shadow: 0 4px 15px rgba(46, 204, 113, 0.3);
    }

    .btn-danger {
        background: linear-gradient(135deg, #e74c3c, #c0392b);
        color: white;
        box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
    }

    .btn-outline {
        background: transparent;
        color: #6c757d;
        border: 2px solid #6c757d;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
    }

    .btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    }

    .empty-state i {
        font-size: 4em;
        color: #ddd;
        margin-bottom: 20px;
    }

    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        z-index: 10000;
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .modal-content {
        background: white;
        border-radius: 15px;
        max-width: 600px;
        width: 90%;
        max-height: 80vh;
        overflow-y: auto;
        padding: 25px;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f8f9fa;
    }

    .close-modal {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #7f8c8d;
    }

    .close-modal:hover {
        color: #e74c3c;
    }

    .feedback-textarea {
        width: 100%;
        min-height: 120px;
        padding: 15px;
        border: 2px solid #e1e8ed;
        border-radius: 8px;
        font-family: inherit;
        resize: vertical;
        margin-bottom: 20px;
    }

    .feedback-textarea:focus {
        border-color: #3498db;
        outline: none;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
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
$stats = [
    'earnings' => 0,
    'pending' => 0,
    'accepted' => 0,
    'rejected' => 0
];

try {
    $pdo = connect();
    
    if (!$pdo) {
        throw new Exception("Erreur de connexion à la base de données");
    }
    
    // Récupérer les statistiques
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_solutions,
            SUM(CASE WHEN s.status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN s.status = 'accepted' THEN 1 ELSE 0 END) as accepted_count,
            SUM(CASE WHEN s.status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
            COALESCE(SUM(CASE WHEN s.status = 'accepted' THEN pr.amount ELSE 0 END), 0) as total_earnings
        FROM solutions s
        JOIN problems p ON s.problem_id = p.problem_id
        LEFT JOIN prices pr ON s.price_id = pr.price_id
        WHERE p.user_id = ? AND s.user_id != ?
    ");
    
    $stmt->execute([$user_id, $user_id]);
    $stats_result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($stats_result) {
        $stats = [
            'earnings' => floatval($stats_result['total_earnings'] ?? 0),
            'pending' => intval($stats_result['pending_count'] ?? 0),
            'accepted' => intval($stats_result['accepted_count'] ?? 0),
            'rejected' => intval($stats_result['rejected_count'] ?? 0)
        ];
    }
    
    // Récupérer les solutions reçues
    $stmt = $pdo->prepare("
        SELECT s.*, p.title as problem_title, p.problem_id, p.description as problem_description,
               u.username, u.name as solver_name, u.avatar_url,
               COALESCE(pr.amount, 0) as price, pr.currency
        FROM solutions s
        JOIN problems p ON s.problem_id = p.problem_id
        JOIN users u ON s.user_id = u.id
        LEFT JOIN prices pr ON s.price_id = pr.price_id
        WHERE p.user_id = ? AND s.user_id != ?
        ORDER BY s.created_at DESC
    ");
    
    $stmt->execute([$user_id, $user_id]);
    $received_solutions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des solutions: " . $e->getMessage());
    $error_message = "Une erreur est survenue lors de la récupération des données.";
}

// Traitement de l'acceptation d'une solution
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_solution'])) {
    $solution_id = isset($_POST['solution_id']) ? (int)$_POST['solution_id'] : 0;
    
    if ($solution_id > 0) {
        try {
            $stmt = $pdo->prepare("
                SELECT s.*, p.user_id as problem_owner
                FROM solutions s
                JOIN problems p ON s.problem_id = p.problem_id
                WHERE s.id = ? AND p.user_id = ? AND s.status = 'pending'
            ");
            
            $stmt->execute([$solution_id, $user_id]);
            $solution = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($solution) {
                $stmt = $pdo->prepare("
                    UPDATE solutions
                    SET status = 'accepted', evaluated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                
                $result = $stmt->execute([$solution_id]);
                
                if ($result) {
                    $_SESSION['success_message'] = "Solution acceptée avec succès ! Redirection vers le paiement...";
                    header('Location: payment.php?solution_id=' . $solution_id);
                    exit;
                } else {
                    $_SESSION['error_message'] = "Erreur lors de l'acceptation de la solution.";
                }
            } else {
                $_SESSION['error_message'] = "Solution introuvable ou déjà traitée.";
            }
        } catch (Exception $e) {
            error_log("Erreur lors de l'acceptation: " . $e->getMessage());
            $_SESSION['error_message'] = "Erreur technique lors de l'acceptation.";
        }
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Traitement du rejet d'une solution
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_solution'])) {
    $solution_id = isset($_POST['solution_id']) ? (int)$_POST['solution_id'] : 0;
    $feedback = isset($_POST['feedback']) ? trim($_POST['feedback']) : '';
    
    if ($solution_id > 0) {
        try {
            $stmt = $pdo->prepare("
                SELECT s.*, p.user_id as problem_owner
                FROM solutions s
                JOIN problems p ON s.problem_id = p.problem_id
                WHERE s.id = ? AND p.user_id = ? AND s.status = 'pending'
            ");
            
            $stmt->execute([$solution_id, $user_id]);
            $solution = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($solution) {
                $stmt = $pdo->prepare("
                    UPDATE solutions
                    SET status = 'rejected', feedback = ?, evaluated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                
                $result = $stmt->execute([$feedback, $solution_id]);
                
                if ($result) {
                    $_SESSION['success_message'] = "Solution rejetée avec succès.";
                } else {
                    $_SESSION['error_message'] = "Erreur lors du rejet de la solution.";
                }
            } else {
                $_SESSION['error_message'] = "Solution introuvable ou déjà traitée.";
            }
        } catch (Exception $e) {
            error_log("Erreur lors du rejet: " . $e->getMessage());
            $_SESSION['error_message'] = "Erreur technique lors du rejet.";
        }
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
?>

<div class="page-header">
    <h1><i class="fas fa-inbox"></i> Solutions Reçues</h1>
    <p>Gérez les solutions soumises pour vos problèmes</p>
</div>

<!-- Statistiques -->
<div class="stats-container">
    <div class="stat-card earnings">
        <span class="stat-value"><?php echo number_format($stats['earnings'], 2); ?> €</span>
        <div class="stat-label"><i class="fas fa-euro-sign"></i> Gains Totaux</div>
    </div>
    <div class="stat-card pending">
        <span class="stat-value"><?php echo $stats['pending']; ?></span>
        <div class="stat-label"><i class="fas fa-clock"></i> En Attente</div>
    </div>
    <div class="stat-card accepted">
        <span class="stat-value"><?php echo $stats['accepted']; ?></span>
        <div class="stat-label"><i class="fas fa-check-circle"></i> Acceptées</div>
    </div>
    <div class="stat-card rejected">
        <span class="stat-value"><?php echo $stats['rejected']; ?></span>
        <div class="stat-label"><i class="fas fa-times-circle"></i> Rejetées</div>
    </div>
</div>

<h2><i class="fas fa-list"></i> Solutions Soumises</h2>

<?php if (empty($received_solutions)): ?>
    <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <h3>Aucune solution reçue</h3>
        <p>Vous n'avez pas encore reçu de solutions pour vos problèmes publiés.<br>
        Publiez des problèmes intéressants pour attirer les développeurs !</p>
        <a href="expublier.php" class="btn btn-success">
            <i class="fas fa-plus-circle"></i> Publier un nouveau problème
        </a>
    </div>
<?php else: ?>
    <?php foreach ($received_solutions as $solution): ?>
        <div class="solution-card">
            <div class="solution-header">
                <div>
                    <h3 class="solution-title">
                        <a href="problem.php?id=<?php echo $solution['problem_id']; ?>">
                            <i class="fas fa-puzzle-piece"></i> 
                            <?php echo htmlspecialchars($solution['problem_title']); ?>
                        </a>
                    </h3>
                    
                    <!-- Statut de la solution -->
                    <?php
                    $status_class = '';
                    $status_text = '';
                    $status_icon = '';
                    
                    switch($solution['status']) {
                        case 'pending':
                            $status_class = 'pending';
                            $status_text = 'En attente de votre évaluation';
                            $status_icon = 'fas fa-clock';
                            break;
                        case 'accepted':
                            $status_class = 'accepted';
                            $status_text = 'Solution acceptée';
                            $status_icon = 'fas fa-check-circle';
                            break;
                        case 'rejected':
                            $status_class = 'rejected';
                            $status_text = 'Solution rejetée';
                            $status_icon = 'fas fa-times-circle';
                            break;
                        default:
                            $status_class = 'pending';
                            $status_text = 'Statut: ' . $solution['status'];
                            $status_icon = 'fas fa-question-circle';
                    }
                    ?>
                    
                    <div class="solution-status <?php echo $status_class; ?>">
                        <i class="<?php echo $status_icon; ?>"></i>
                        <?php echo $status_text; ?>
                    </div>
                </div>
                <div class="solution-price">
                    <i class="fas fa-tag"></i>
                    <?php echo number_format($solution['price'], 2); ?> €
                </div>
            </div>
            
            <!-- Informations du développeur -->
            <div class="developer-info">
                <img src="<?php echo htmlspecialchars($solution['avatar_url'] ?? 'default-avatar.png'); ?>" 
                     alt="Avatar" class="developer-avatar">
                <div class="developer-details">
                    <div class="developer-name">
                        <?php echo htmlspecialchars($solution['solver_name'] ?? $solution['username']); ?>
                    </div>
                    <div class="developer-username">
                        @<?php echo htmlspecialchars($solution['username']); ?>
                    </div>
                    <div class="solution-date">
                        <i class="fas fa-calendar-alt"></i> 
                        Soumis le <?php 
                        $date = new DateTime($solution['created_at'] ?? date('Y-m-d H:i:s'));
                        echo $date->format('d/m/Y à H:i'); 
                        ?>
                    </div>
                </div>
            </div>
            
            <!-- Code de la solution -->
            <div class="solution-code">
                <button class="copy-code-btn" onclick="copyCode(this)">
                    <i class="fas fa-copy"></i> Copier
                </button>
                <pre><?php echo htmlspecialchars($solution['solution_code']); ?></pre>
            </div>
            
            <!-- Explication du développeur -->
            <?php if (!empty($solution['explanation'])): ?>
                <div class="solution-explanation">
                    <h4><i class="fas fa-lightbulb"></i> Explication du développeur</h4>
                    <p><?php echo nl2br(htmlspecialchars($solution['explanation'])); ?></p>
                </div>
            <?php endif; ?>
            
            <!-- Feedback si rejetée -->
            <?php if ($solution['status'] === 'rejected' && !empty($solution['feedback'])): ?>
                <div style="background: #ffebee; border-radius: 10px; padding: 15px; margin-bottom: 20px; border-left: 4px solid #e74c3c;">
                    <h4><i class="fas fa-comment-alt"></i> Votre feedback</h4>
                    <p><?php echo nl2br(htmlspecialchars($solution['feedback'])); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="solution-footer" style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding-top: 15px; border-top: 2px solid #f8f9fa;">
                <a href="problem.php?id=<?php echo $solution['problem_id']; ?>" class="btn btn-outline">
                    <i class="fas fa-eye"></i> Voir le problème
                </a>
                
                <div class="solution-actions">
                    <?php if ($solution['status'] === 'pending'): ?>
                        <!-- Solution en attente - Boutons d'action -->
                        <button onclick="showAcceptModal(<?php echo $solution['id']; ?>, '<?php echo htmlspecialchars($solution['problem_title']); ?>', <?php echo $solution['price']; ?>)" 
                                class="btn btn-success">
                            <i class="fas fa-check"></i> Accepter (<?php echo number_format($solution['price'], 2); ?> €)
                        </button>
                        
                        <button onclick="showRejectModal(<?php echo $solution['id']; ?>, '<?php echo htmlspecialchars($solution['problem_title']); ?>')" 
                                class="btn btn-danger">
                            <i class="fas fa-times"></i> Rejeter
                        </button>
                        
                    <?php elseif ($solution['status'] === 'accepted'): ?>
                        <!-- Solution acceptée -->
                        <a href="payment.php?solution_id=<?php echo $solution['id']; ?>" class="btn btn-success">
                            <i class="fas fa-credit-card"></i> Procéder au paiement
                        </a>
                        
                    <?php else: ?>
                        <!-- Solution rejetée ou autre statut -->
                        <span class="btn" style="background: #95a5a6; color: white; opacity: 0.7; cursor: default;">
                            <i class="fas fa-info-circle"></i> <?php echo ucfirst($solution['status']); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Modal d'acceptation -->
<div id="acceptModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-check-circle"></i> Accepter la solution</h3>
            <button class="close-modal" onclick="closeModal('acceptModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p>Êtes-vous sûr de vouloir accepter cette solution pour le problème :</p>
            <p><strong id="acceptProblemTitle"></strong></p>
            <p>Montant à payer : <strong id="acceptPrice"></strong></p>
            <p style="color: #e74c3c; font-size: 0.9em;">
                <i class="fas fa-exclamation-triangle"></i> 
                Une fois acceptée, vous serez redirigé vers la page de paiement.
            </p>
        </div>
        <div class="modal-footer" style="text-align: right; margin-top: 20px;">
            <button onclick="closeModal('acceptModal')" class="btn btn-outline">Annuler</button>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="solution_id" id="acceptSolutionId">
                <button type="submit" name="accept_solution" class="btn btn-success">
                    <i class="fas fa-check"></i> Confirmer l'acceptation
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Modal de rejet -->
<div id="rejectModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-times-circle"></i> Rejeter la solution</h3>
            <button class="close-modal" onclick="closeModal('rejectModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p>Vous êtes sur le point de rejeter la solution pour :</p>
            <p><strong id="rejectProblemTitle"></strong></p>
            <label for="rejectFeedback">Expliquez pourquoi vous rejetez cette solution :</label>
            <textarea id="rejectFeedback" name="feedback" class="feedback-textarea" 
                      placeholder="Donnez un feedback constructif au développeur..."></textarea>
        </div>
        <div class="modal-footer" style="text-align: right; margin-top: 20px;">
            <button onclick="closeModal('rejectModal')" class="btn btn-outline">Annuler</button>
            <form method="POST" style="display: inline;">
                <input type="hidden" name="solution_id" id="rejectSolutionId">
                <input type="hidden" name="feedback" id="rejectFeedbackHidden">
                <button type="submit" name="reject_solution" class="btn btn-danger" onclick="setFeedback()">
                    <i class="fas fa-times"></i> Confirmer le rejet
                </button>
            </form>
        </div>
    </div>
</div>

<?php
// Additional scripts
$additional_scripts = "
    // Fonction pour afficher le modal d'acceptation
    function showAcceptModal(solutionId, problemTitle, price) {
        document.getElementById('acceptSolutionId').value = solutionId;
        document.getElementById('acceptProblemTitle').textContent = problemTitle;
        document.getElementById('acceptPrice').textContent = price.toFixed(2) + ' €';
        document.getElementById('acceptModal').style.display = 'flex';
    }

       // Fonction pour afficher le modal de rejet
    function showRejectModal(solutionId, problemTitle) {
        document.getElementById('rejectSolutionId').value = solutionId;
        document.getElementById('rejectProblemTitle').textContent = problemTitle;
        document.getElementById('rejectFeedback').value = '';
        document.getElementById('rejectModal').style.display = 'flex';
    }

    // Fonction pour fermer les modals
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }

    // Fonction pour transférer le feedback
    function setFeedback() {
        const feedback = document.getElementById('rejectFeedback').value;
        document.getElementById('rejectFeedbackHidden').value = feedback;
    }

    // Fermer les modals en cliquant à l'extérieur
    window.onclick = function(event) {
        const acceptModal = document.getElementById('acceptModal');
        const rejectModal = document.getElementById('rejectModal');
        
        if (event.target === acceptModal) {
            acceptModal.style.display = 'none';
        }
        if (event.target === rejectModal) {
            rejectModal.style.display = 'none';
        }
    }

    // Fonction pour copier le code
    function copyCode(button) {
        const codeBlock = button.nextElementSibling;
        const code = codeBlock.textContent;
        
        navigator.clipboard.writeText(code).then(function() {
            const originalText = button.innerHTML;
            button.innerHTML = '<i class=\"fas fa-check\"></i> Copié !';
            button.style.background = '#27ae60';
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.style.background = '#34495e';
            }, 2000);
        }).catch(function() {
            showToast('Erreur lors de la copie', 'danger');
        });
    }

    // Fonction pour afficher des notifications toast
    function showToast(message, type = 'info', duration = 3000) {
        const toast = document.createElement('div');
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10001;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            min-width: 300px;
            max-width: 500px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            animation: slideInRight 0.3s ease;
        `;
        
        const colors = {
            success: '#27ae60',
            danger: '#e74c3c',
            warning: '#f39c12',
            info: '#3498db'
        };
        
        const icons = {
            success: 'fas fa-check-circle',
            danger: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };
        
        toast.style.backgroundColor = colors[type] || colors.info;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideOutRight 0.3s ease forwards';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, duration);
    }

    // Animations CSS
    const animationStyles = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .solution-card {
            animation: fadeIn 0.5s ease forwards;
        }
        
        .modal {
            animation: fadeIn 0.3s ease;
        }
    `;
    
    const styleSheet = document.createElement('style');
    styleSheet.textContent = animationStyles;
    document.head.appendChild(styleSheet);

    // Animation d'apparition des éléments au chargement
    document.addEventListener('DOMContentLoaded', function() {
        // Animation des cartes de statistiques
        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            setTimeout(() => {
                card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
        
        // Animation des cartes de solutions
        const solutionCards = document.querySelectorAll('.solution-card');
        solutionCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            setTimeout(() => {
                card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, (index * 150) + 500); // Délai après les stats
        });
        
        // Effet hover sur les cartes
        solutionCards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-8px)';
                card.style.boxShadow = '0 12px 30px rgba(0,0,0,0.15)';
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
                card.style.boxShadow = '0 4px 15px rgba(0,0,0,0.08)';
            });
        });
    });

    // Fonction pour filtrer les solutions par statut
    function filterSolutions(status) {
        const cards = document.querySelectorAll('.solution-card');
        let visibleCount = 0;
        
        cards.forEach(card => {
            const statusElement = card.querySelector('.solution-status');
            const cardStatus = statusElement.classList.contains('pending') ? 'pending' :
                              statusElement.classList.contains('accepted') ? 'accepted' :
                              statusElement.classList.contains('rejected') ? 'rejected' : 'unknown';
            
            if (status === 'all' || cardStatus === status) {
                card.style.display = 'block';
                card.style.animation = 'fadeIn 0.3s ease';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        showToast(`Affichage de ${visibleCount} solution(s)`, 'info', 1500);
    }

    // Ajouter les boutons de filtre si il y a plusieurs solutions
    document.addEventListener('DOMContentLoaded', function() {
        const solutionCards = document.querySelectorAll('.solution-card');
        
        if (solutionCards.length > 1) {
            const filterContainer = document.createElement('div');
            filterContainer.style.cssText = 'margin: 20px 0; text-align: center; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);';
            
            const filterTitle = document.createElement('h4');
            filterTitle.innerHTML = '<i class=\"fas fa-filter\"></i> Filtrer par statut';
            filterTitle.style.cssText = 'margin: 0 0 15px 0; color: #2c3e50;';
            filterContainer.appendChild(filterTitle);
            
            const filters = [
                { key: 'all', label: 'Toutes', icon: 'fas fa-list', color: '#3498db' },
                { key: 'pending', label: 'En attente', icon: 'fas fa-clock', color: '#9b59b6' },
                { key: 'accepted', label: 'Acceptées', icon: 'fas fa-check-circle', color: '#27ae60' },
                { key: 'rejected', label: 'Rejetées', icon: 'fas fa-times-circle', color: '#e74c3c' }
            ];
            
            const buttonContainer = document.createElement('div');
            buttonContainer.style.cssText = 'display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;';
            
            filters.forEach(filter => {
                const button = document.createElement('button');
                button.className = 'btn';
                button.style.cssText = `background: ${filter.color}; color: white; opacity: 0.7; transition: all 0.3s;`;
                button.innerHTML = `<i class=\"${filter.icon}\"></i> ${filter.label}`;
                button.onclick = () => {
                    // Réinitialiser tous les boutons
                    buttonContainer.querySelectorAll('button').forEach(btn => {
                        btn.style.opacity = '0.7';
                        btn.style.transform = 'scale(1)';
                    });
                    
                    // Activer le bouton cliqué
                    button.style.opacity = '1';
                    button.style.transform = 'scale(1.05)';
                    
                    filterSolutions(filter.key);
                };
                
                // Activer 'Toutes' par défaut
                if (filter.key === 'all') {
                    button.style.opacity = '1';
                    button.style.transform = 'scale(1.05)';
                }
                
                buttonContainer.appendChild(button);
            });
            
            filterContainer.appendChild(buttonContainer);
            
            // Insérer avant la liste des solutions
            const solutionsHeader = document.querySelector('h2');
            if (solutionsHeader) {
                solutionsHeader.parentNode.insertBefore(filterContainer, solutionsHeader.nextSibling);
            }
        }
    });

    // Fonction pour exporter les données
    function exportSolutions() {
        const solutions = [];
        document.querySelectorAll('.solution-card').forEach(card => {
            const title = card.querySelector('.solution-title a').textContent.trim();
            const developer = card.querySelector('.developer-name').textContent.trim();
            const price = card.querySelector('.solution-price').textContent.replace(/[^0-9.,]/g, '');
            const status = card.querySelector('.solution-status').textContent.trim();
            const date = card.querySelector('.solution-date').textContent.replace('Soumis le ', '').trim();
            
            solutions.push({
                'Problème': title.replace('🧩 ', ''),
                'Développeur': developer,
                'Prix': price + ' €',
                'Statut': status,
                'Date': date
            });
        });
        
        if (solutions.length === 0) {
            showToast('Aucune donnée à exporter', 'warning');
            return;
        }
        
        const csvContent = [
            Object.keys(solutions[0]).join(','),
            ...solutions.map(row => 
                Object.values(row).map(val => 
                    '\"' + String(val).replace(/\"/g, '\"\"') + '\"'
                ).join(',')
            )
        ].join('\\n');
        
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', 'solutions_recues_' + new Date().toISOString().split('T')[0] + '.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showToast('Export terminé avec succès !', 'success');
    }

    // Ajouter le bouton d'export
    document.addEventListener('DOMContentLoaded', function() {
        const solutionCards = document.querySelectorAll('.solution-card');
        
        if (solutionCards.length > 0) {
            const exportButton = document.createElement('button');
            exportButton.className = 'btn btn-success';
            exportButton.innerHTML = '<i class=\"fas fa-download\"></i> Exporter en CSV';
            exportButton.onclick = exportSolutions;
            exportButton.style.cssText = 'margin: 20px 0; float: right;';
            
            const solutionsHeader = document.querySelector('h2');
            if (solutionsHeader) {
                const headerContainer = document.createElement('div');
                headerContainer.style.cssText = 'display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;';
                
                const newHeader = solutionsHeader.cloneNode(true);
                solutionsHeader.parentNode.replaceChild(headerContainer, solutionsHeader);
                
                headerContainer.appendChild(newHeader);
                headerContainer.appendChild(exportButton);
            }
        }
    });

    // Validation des formulaires
    document.addEventListener('DOMContentLoaded', function() {
        // Validation du feedback de rejet
        const rejectFeedback = document.getElementById('rejectFeedback');
        const rejectButton = document.querySelector('button[name=\"reject_solution\"]');
        
        if (rejectFeedback && rejectButton) {
            rejectFeedback.addEventListener('input', function() {
                const feedback = this.value.trim();
                if (feedback.length < 10) {
                    this.style.borderColor = '#e74c3c';
                    rejectButton.disabled = true;
                    rejectButton.style.opacity = '0.5';
                } else {
                    this.style.borderColor = '#27ae60';
                    rejectButton.disabled = false;
                    rejectButton.style.opacity = '1';
                }
            });
        }
    });

    console.log('✅ User Feedback - Interface initialisée avec succès !');
    console.log('📊 Statistiques chargées');
    console.log('🔄 Système de gestion des solutions activé');
";

// Include footer
include 'footer.php';
?>
