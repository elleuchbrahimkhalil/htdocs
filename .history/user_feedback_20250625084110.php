<?php
// Set page title
$page_title = "Solutions Reçues - Mes Problèmes";

// Additional CSS specific to this page
$additional_css = "
    .page-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
    .stat-card.ai-testing { border-left-color: #9b59b6; }

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

    .solution-card.ai-testing::before {
        background: linear-gradient(90deg, #9b59b6, #8e44ad);
    }

    .solution-card.ai-passed::before {
        background: linear-gradient(90deg, #2ecc71, #27ae60);
    }

    .solution-card.ai-failed::before {
        background: linear-gradient(90deg, #e74c3c, #c0392b);
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

    .solution-price {
        background: linear-gradient(135deg, #2ecc71, #27ae60);
        color: white;
        padding: 8px 15px;
        border-radius: 20px;
        font-weight: bold;
        font-size: 1.1em;
        box-shadow: 0 2px 10px rgba(46, 204, 113, 0.3);
    }

    .ai-status {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 15px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.85em;
        margin-bottom: 15px;
    }

    .ai-status.testing {
        background: #f3e5f5;
        color: #7b1fa2;
        border: 1px solid #ce93d8;
    }

    .ai-status.passed {
        background: #e8f5e8;
        color: #2e7d32;
        border: 1px solid #a5d6a7;
    }

    .ai-status.failed {
        background: #ffebee;
        color: #c62828;
        border: 1px solid #ef9a9a;
    }

    .ai-results {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 20px;
        border-left: 4px solid #9b59b6;
    }

    .ai-results h4 {
        margin-top: 0;
        color: #7b1fa2;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .test-results {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 10px;
        margin-top: 10px;
    }

    .test-case {
        background: white;
        padding: 10px;
        border-radius: 6px;
        border: 1px solid #e0e0e0;
        font-family: monospace;
        font-size: 0.9em;
    }

    .test-case.passed {
        border-left: 4px solid #4caf50;
        background: #f1f8e9;
    }

    .test-case.failed {
        border-left: 4px solid #f44336;
        background: #ffebee;
    }

    .solution-actions {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
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

    .btn-primary {
        background: linear-gradient(135deg, #3498db, #2980b9);
        color: white;
        box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
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

    .auto-accept-notice {
        background: #e8f5e8;
        border: 1px solid #a5d6a7;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 20px;
        color: #2e7d32;
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
    'rejected' => 0,
    'ai_testing' => 0
];

try {
    $pdo = connect();
    
    if (!$pdo) {
        throw new Exception("Erreur de connexion à la base de données");
    }
    
    // Récupérer les statistiques avec le statut AI
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_solutions,
            SUM(CASE WHEN s.status = 'pending' THEN 1 ELSE 0 END) as pending_count,
            SUM(CASE WHEN s.status = 'accepted' THEN 1 ELSE 0 END) as accepted_count,
            SUM(CASE WHEN s.status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
            SUM(CASE WHEN s.status = 'ai_testing' THEN 1 ELSE 0 END) as ai_testing_count,
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
            'rejected' => intval($stats_result['rejected_count'] ?? 0),
            'ai_testing' => intval($stats_result['ai_testing_count'] ?? 0)
        ];
    }
    
    // Récupérer les solutions avec les résultats AI
    $stmt = $pdo->prepare("
        SELECT s.*, p.title as problem_title, p.problem_id, u.username, u.name as solver_name,
               COALESCE(pr.amount, 0) as price, pr.currency,
               p.description as problem_description,
               s.ai_test_results, s.ai_score, s.ai_feedback
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

// Traitement automatique basé sur les résultats AI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accept_ai_solution'])) {
    $solution_id = isset($_POST['solution_id']) ? (int)$_POST['solution_id'] : 0;
    
    if ($solution_id > 0) {
        try {
            $stmt = $pdo->prepare("
                UPDATE solutions
                SET status = 'accepted', evaluated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND status = 'ai_passed'
            ");
            
            $result = $stmt->execute([$solution_id]);
            
            if ($result) {
                $_SESSION['success_message'] = "Solution acceptée automatiquement suite aux tests IA réussis.";
                header('Location: payment.php?solution_id=' . $solution_id);
                exit;
            }
        } catch (Exception $e) {
            error_log("Erreur lors de l'acceptation automatique: " . $e->getMessage());
            $_SESSION['error_message'] = "Erreur lors de l'acceptation automatique.";
        }
    }
    
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}
?>

<div class="page-header">
    <h1><i class="fas fa-robot"></i> Solutions IA - Gestion Automatisée</h1>
    <p>Les solutions sont automatiquement testées par notre IA avant validation</p>
</div>

<!-- Statistiques avec IA -->
<div class="stats-container">
    <div class="stat-card earnings">
        <span class="stat-value"><?php echo number_format($stats['earnings'], 2); ?> €</span>
        <div class="stat-label"><i class="fas fa-euro-sign"></i> Gains Potentiels</div>
    </div>
    <div class="stat-card ai-testing">
        <span class="stat-value"><?php echo $stats['ai_testing']; ?></span>
        <div class="stat-label"><i class="fas fa-robot"></i> Tests IA en cours</div>
    </div>
    <div class="stat-card accepted">
        <span class="stat-value"><?php echo $stats['accepted']; ?></span>
        <div class="stat-label"><i class="fas fa-check-circle"></i> Validées par IA</div>
    </div>
    <div class="stat-card rejected">
        <span class="stat-value"><?php echo $stats['rejected']; ?></span>
        <div class="stat-label"><i class="fas fa-times-circle"></i> Rejetées par IA</div>
    </div>
</div>

<!-- Notice sur l'automatisation -->
<div class="auto-accept-notice">
    <h4><i class="fas fa-magic"></i> Validation Automatique par IA</h4>
    <p>Notre système IA teste automatiquement chaque solution soumise. Les solutions qui passent tous les tests sont pré-approuvées et prêtes pour le paiement.</p>
</div>

<h2><i class="fas fa-flask"></i> Solutions Testées par IA</h2>

<?php if (empty($received_solutions)): ?>
    <div class="empty-state">
        <i class="fas fa-robot"></i>
        <h3>Aucune solution reçue</h3>
        <p>Vous n'avez pas encore reçu de solutions pour vos problèmes publiés.<br>
        Publiez des problèmes intéressants pour attirer les développeurs !</p>
        <a href="expublier.php" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> Publier un nouveau problème
        </a>
    </div>
<?php else: ?>
    <?php foreach ($received_solutions as $solution): ?>
        <div class="solution-card <?php echo $solution['status'] === 'ai_testing' ? 'ai-testing' : ($solution['status'] === 'ai_passed' ? 'ai-passed' : ($solution['status'] === 'ai_failed' ? 'ai-failed' : '')); ?>">
            <div class="solution-header">
                <div>
                    <h3 class="solution-title">
                        <a href="problem.php?id=<?php echo $solution['problem_id']; ?>">
                            <i class="fas fa-puzzle-piece"></i> 
                            <?php echo htmlspecialchars($solution['problem_title']); ?>
                        </a>
                    </h3>
                    
                    <!-- Statut IA -->
                    <?php
                    $ai_status_class = '';
                    $ai_status_text = '';
                    $ai_status_icon = '';
                    
                    switch($solution['status']) {
                        case 'ai_testing':
                            $ai_status_class = 'testing';
                            $ai_status_text = 'Tests IA en cours...';
                            $ai_status_icon = 'fas fa-spinner fa-spin';
                            break;
                        case 'ai_passed':
                            $ai_status_class = 'passed';
                            $ai_status_text = 'Tests IA réussis - Prêt pour paiement';
                            $ai_status_icon = 'fas fa-check-circle';
                            break;
                        case 'ai_failed':
                            $ai_status_class = 'failed';
                            $ai_status_text = 'Tests IA échoués';
                            $ai_status_icon = 'fas fa-times-circle';
                            break;
                        case 'accepted':
                            $ai_status_class = 'passed';
                            $ai_status_text = 'Solution acceptée et payée';
                            $ai_status_icon = 'fas fa-credit-card';
                            break;
                        default:
                            $ai_status_class = 'testing';
                            $ai_status_text = 'En attente de tests';
                            $ai_status_icon = 'fas fa-clock';
                    }
                    ?>
                    
                    <div class="ai-status <?php echo $ai_status_class; ?>">
                        <i class="<?php echo $ai_status_icon; ?>"></i>
                        <?php echo $ai_status_text; ?>
                    </div>
                </div>
                <div class="solution-price">
                    <i class="fas fa-tag"></i>
                    <?php echo number_format($solution['price'], 2); ?> €
                </div>
            </div>
            
            <!-- Informations du développeur -->
            <div class="developer-info" style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 10px;">
                <img src="<?php echo htmlspecialchars($solution['avatar_url'] ?? 'default-avatar.png'); ?>" 
                     alt="Avatar" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                <div>
                    <div style="font-weight: 600; color: #2c3e50;">
                        <?php echo htmlspecialchars($solution['solver_name'] ?? $solution['username']); ?>
                    </div>
                    <div style="color: #7f8c8d; font-size: 0.9em;">
                        @<?php echo htmlspecialchars($solution['username']); ?>
                    </div>
                    <div style="color: #7f8c8d; font-size: 0.85em;">
                        <i class="fas fa-calendar-alt"></i> 
                        Soumis le <?php 
                        $date = new DateTime($solution['created_at'] ?? date('Y-m-d H:i:s'));
                        echo $date->format('d/m/Y à H:i'); 
                        ?>
                    </div>
                </div>
            </div>
            
            <!-- Résultats des tests IA -->
            <?php if (!empty($solution['ai_test_results']) || !empty($solution['ai_feedback'])): ?>
                <div class="ai-results">
                    <h4><i class="fas fa-brain"></i> Résultats des Tests IA</h4>
                    
                    <?php if (!empty($solution['ai_score'])): ?>
                        <div style="margin-bottom: 15px;">
                            <strong>Score IA:</strong> 
                            <span style="font-size: 1.2em; color: <?php echo $solution['ai_score'] >= 80 ? '#27ae60' : ($solution['ai_score'] >= 60 ? '#f39c12' : '#e74c3c'); ?>;">
                                <?php echo $solution['ai_score']; ?>/100
                            </span>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($solution['ai_test_results'])): ?>
                        <?php 
                        $test_results = json_decode($solution['ai_test_results'], true);
                        if ($test_results && is_array($test_results)): 
                        ?>
                            <div class="test-results">
                                <?php foreach ($test_results as $index => $test): ?>
                                    <div class="test-case <?php echo $test['passed'] ? 'passed' : 'failed'; ?>">
                                        <strong>Test <?php echo $index + 1; ?>:</strong><br>
                                        Input: <?php echo htmlspecialchars($test['input'] ?? 'N/A'); ?><br>
                                        Attendu: <?php echo htmlspecialchars($test['expected'] ?? 'N/A'); ?><br>
                                        Obtenu: <?php echo htmlspecialchars($test['actual'] ?? 'N/A'); ?><br>
                                        <span style="color: <?php echo $test['passed'] ? '#27ae60' : '#e74c3c'; ?>;">
                                            <?php echo $test['passed'] ? '✓ Réussi' : '✗ Échoué'; ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php if (!empty($solution['ai_feedback'])): ?>
                        <div style="margin-top: 15px; padding: 10px; background: white; border-radius: 6px;">
                            <strong>Feedback IA:</strong><br>
                            <?php echo nl2br(htmlspecialchars($solution['ai_feedback'])); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <!-- Explication du développeur -->
            <?php if (!empty($solution['explanation'])): ?>
                <div style="background: #f0f7fb; border-radius: 10px; padding: 15px; margin-bottom: 20px; border-left: 4px solid #3498db;">
                    <h4><i class="fas fa-lightbulb"></i> Explication du développeur</h4>
                    <p><?php echo nl2br(htmlspecialchars($solution['explanation'])); ?></p>
                </div>
            <?php endif; ?>
            
            <div class="solution-footer" style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding-top: 15px; border-top: 2px solid #f8f9fa;">
                <a href="problem.php?id=<?php echo $solution['problem_id']; ?>" class="btn" style="background: #6c757d; color: white;">
                    <i class="fas fa-eye"></i> Voir le problème
                </a>
                
                <div class="solution-actions">
                    <?php if ($solution['status'] === 'ai_passed'): ?>
                        <!-- Solution validée par IA - Prête pour paiement -->
                        <form method="POST" action="" style="display: inline;">
                            <input type="hidden" name="solution_id" value="<?php echo $solution['id']; ?>">
                            <button type="submit" name="accept_ai_solution" class="btn btn-success">
                                <i class="fas fa-credit-card"></i> Procéder au paiement (<?php echo number_format($solution['price'], 2); ?> €)
                            </button>
                        </form>
                        
                    <?php elseif ($solution['status'] === 'accepted'): ?>
                        <!-- Solution déjà acceptée et payée -->
                        <span class="btn" style="background: #27ae60; color: white; opacity: 0.7; cursor: default;">
                            <i class="fas fa-check-double"></i> Solution payée
                        </span>
                        
                    <?php elseif ($solution['status'] === 'ai_testing'): ?>
                        <!-- Tests IA en cours -->
                        <span class="btn" style="background: #9b59b6; color: white; opacity: 0.7; cursor: default;">
                            <i class="fas fa-spinner fa-spin"></i> Tests IA en cours...
                        </span>
                        <button class="btn" style="background: #3498db; color: white;" onclick="refreshSolutionStatus(<?php echo $solution['id']; ?>)">
                            <i class="fas fa-sync-alt"></i> Actualiser
                        </button>
                        
                    <?php elseif ($solution['status'] === 'ai_failed'): ?>
                        <!-- Tests IA échoués -->
                        <span class="btn" style="background: #e74c3c; color: white; opacity: 0.6; cursor: not-allowed;">
                            <i class="fas fa-robot"></i> Rejetée par IA
                        </span>
                        
                    <?php else: ?>
                        <!-- Statut inconnu -->
                        <span class="btn" style="background: #95a5a6; color: white; opacity: 0.7; cursor: default;">
                            <i class="fas fa-question"></i> Statut: <?php echo htmlspecialchars($solution['status']); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<!-- Informations sur le processus IA -->
<div style="background: #f8f9fa; border-radius: 12px; padding: 25px; margin-top: 30px; border-left: 5px solid #3498db;">
    <h3><i class="fas fa-info-circle"></i> Comment fonctionne notre IA ?</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 15px;">
        <div>
            <h4><i class="fas fa-upload"></i> 1. Soumission</h4>
            <p>Le développeur soumet sa solution via Flask API</p>
        </div>
        <div>
            <h4><i class="fas fa-cogs"></i> 2. Tests automatiques</h4>
            <p>Notre IA teste la solution avec différents cas de test</p>
        </div>
        <div>
            <h4><i class="fas fa-chart-line"></i> 3. Évaluation</h4>
            <p>Attribution d'un score basé sur la performance et la qualité</p>
        </div>
        <div>
            <h4><i class="fas fa-check"></i> 4. Validation</h4>
            <p>Auto-approbation si score ≥ 80/100</p>
        </div>
    </div>
</div>

<?php
// Additional scripts
$additional_scripts = "
    // Fonction pour actualiser le statut d'une solution
    function refreshSolutionStatus(solutionId) {
        const button = event.target;
        const originalText = button.innerHTML;
        
        button.disabled = true;
        button.innerHTML = '<i class=\"fas fa-spinner fa-spin\"></i> Vérification...';
        
        fetch('api/check_solution_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ solution_id: solutionId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.status_changed) {
                    showToast('Statut mis à jour ! Rechargement de la page...', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showToast('Tests toujours en cours...', 'info');
                    button.disabled = false;
                    button.innerHTML = originalText;
                }
            } else {
                showToast('Erreur lors de la vérification', 'danger');
                button.disabled = false;
                button.innerHTML = originalText;
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showToast('Erreur de connexion', 'danger');
            button.disabled = false;
            button.innerHTML = originalText;
        });
    }

    // Fonction pour afficher des notifications
    function showToast(message, type = 'info', duration = 3000) {
        const toast = document.createElement('div');
        toast.className = 'toast toast-' + type;
        toast.style.cssText = `
            position: fixed;
            top: 20px;
                        right: 20px;
            z-index: 9999;
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

    // Styles pour les animations
    const animationStyles = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .ai-testing {
            animation: pulse 2s infinite;
        }
        
        .solution-card {
            transition: all 0.3s ease;
        }
        
        .solution-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
    `;
    
    const styleSheet = document.createElement('style');
    styleSheet.textContent = animationStyles;
    document.head.appendChild(styleSheet);

    // Auto-actualisation des solutions en test IA
    function autoRefreshAITests() {
        const testingSolutions = document.querySelectorAll('.solution-card.ai-testing');
        
        if (testingSolutions.length > 0) {
            console.log(`${testingSolutions.length} solution(s) en cours de test IA`);
            
            // Vérifier le statut toutes les 30 secondes
            setTimeout(() => {
                fetch('api/check_all_ai_tests.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.updates_available) {
                        showToast('Nouveaux résultats IA disponibles !', 'success');
                        setTimeout(() => {
                            window.location.reload();
                        }, 2000);
                    } else {
                        // Continuer à vérifier
                        autoRefreshAITests();
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de la vérification automatique:', error);
                    // Réessayer dans 60 secondes en cas d'erreur
                    setTimeout(autoRefreshAITests, 60000);
                });
            }, 30000);
        }
    }

    // Démarrer l'auto-actualisation au chargement de la page
    document.addEventListener('DOMContentLoaded', function() {
        autoRefreshAITests();
        
        // Animation d'apparition des cartes
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
        
        // Animation des statistiques
        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => {
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });

    // Fonction pour exporter les résultats IA
    function exportAIResults() {
        const solutions = [];
        document.querySelectorAll('.solution-card').forEach(card => {
            const title = card.querySelector('.solution-title a').textContent.trim();
            const developer = card.querySelector('.developer-info div div').textContent.trim();
            const price = card.querySelector('.solution-price').textContent.replace(/[^0-9.,]/g, '');
            const status = card.querySelector('.ai-status').textContent.trim();
            const aiScore = card.querySelector('.ai-results') ? 
                (card.querySelector('.ai-results').textContent.match(/Score IA: (\\d+)/) || ['', 'N/A'])[1] : 'N/A';
            
            solutions.push({
                'Problème': title.replace('🧩 ', ''),
                'Développeur': developer,
                'Prix': price + ' €',
                'Statut IA': status,
                'Score IA': aiScore + '/100'
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
        link.setAttribute('download', 'resultats_ia_' + new Date().toISOString().split('T')[0] + '.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        showToast('Export des résultats IA terminé !', 'success');
    }

    // Ajouter le bouton d'export
    document.addEventListener('DOMContentLoaded', function() {
        if (document.querySelectorAll('.solution-card').length > 0) {
            const exportButton = document.createElement('button');
            exportButton.className = 'btn btn-primary';
            exportButton.innerHTML = '<i class=\"fas fa-download\"></i> Exporter les résultats IA';
            exportButton.onclick = exportAIResults;
            exportButton.style.cssText = 'margin: 20px 0; float: right;';
            
            const header = document.querySelector('h2');
            if (header) {
                header.parentNode.insertBefore(exportButton, header.nextSibling);
            }
        }
    });

    // Fonction pour filtrer les solutions par statut IA
    function filterByAIStatus(status) {
        const cards = document.querySelectorAll('.solution-card');
        
        cards.forEach(card => {
            const cardStatus = card.classList.contains('ai-testing') ? 'testing' :
                              card.classList.contains('ai-passed') ? 'passed' :
                              card.classList.contains('ai-failed') ? 'failed' : 'unknown';
            
            if (status === 'all' || cardStatus === status) {
                card.style.display = 'block';
                card.style.animation = 'fadeIn 0.3s ease';
            } else {
                card.style.display = 'none';
            }
        });
        
        // Mettre à jour le compteur
        const visibleCount = document.querySelectorAll('.solution-card[style*=\"display: block\"], .solution-card:not([style*=\"display: none\"])').length;
        showToast(`Affichage de ${visibleCount} solution(s)`, 'info', 1500);
    }

    // Ajouter les boutons de filtre
    document.addEventListener('DOMContentLoaded', function() {
        if (document.querySelectorAll('.solution-card').length > 1) {
            const filterContainer = document.createElement('div');
            filterContainer.style.cssText = 'margin: 20px 0; text-align: center;';
            
            const filters = [
                { key: 'all', label: 'Toutes', icon: 'fas fa-list' },
                { key: 'testing', label: 'En test', icon: 'fas fa-spinner' },
                { key: 'passed', label: 'Validées', icon: 'fas fa-check-circle' },
                { key: 'failed', label: 'Rejetées', icon: 'fas fa-times-circle' }
            ];
            
            filters.forEach(filter => {
                const button = document.createElement('button');
                button.className = 'btn';
                button.style.cssText = 'margin: 0 5px; background: #ecf0f1; color: #2c3e50; border: 1px solid #bdc3c7;';
                button.innerHTML = `<i class=\"${filter.icon}\"></i> ${filter.label}`;
                button.onclick = () => {
                    // Réinitialiser tous les boutons
                    filterContainer.querySelectorAll('button').forEach(btn => {
                        btn.style.background = '#ecf0f1';
                        btn.style.color = '#2c3e50';
                    });
                    
                    // Activer le bouton cliqué
                    button.style.background = '#3498db';
                    button.style.color = 'white';
                    
                    filterByAIStatus(filter.key);
                };
                
                // Activer 'Toutes' par défaut
                if (filter.key === 'all') {
                    button.style.background = '#3498db';
                    button.style.color = 'white';
                }
                
                filterContainer.appendChild(button);
            });
            
            const header = document.querySelector('h2');
            if (header) {
                header.parentNode.insertBefore(filterContainer, header.nextSibling);
            }
        }
    });

    // Fonction pour afficher les détails techniques d'une solution
    function showTechnicalDetails(solutionId) {
        fetch('api/get_solution_details.php?id=' + solutionId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Créer un modal avec les détails techniques
                const modal = document.createElement('div');
                modal.style.cssText = `
                    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                    background: rgba(0,0,0,0.5); z-index: 10000;
                    display: flex; justify-content: center; align-items: center;
                `;
                
                modal.innerHTML = `
                    <div style=\"background: white; border-radius: 15px; max-width: 800px; width: 90%; max-height: 80vh; overflow-y: auto; padding: 25px;\">
                        <div style=\"display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;\">
                            <h3><i class=\"fas fa-code\"></i> Détails Techniques</h3>
                            <button onclick=\"this.closest('.modal').remove()\" style=\"background: none; border: none; font-size: 24px; cursor: pointer;\">&times;</button>
                        </div>
                        
                        <div style=\"background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;\">
                            <h4>Code de la solution:</h4>
                            <pre style=\"background: #2c3e50; color: #ecf0f1; padding: 15px; border-radius: 6px; overflow-x: auto;\">${data.solution.code}</pre>
                        </div>
                        
                        <div style=\"background: #e8f5e8; padding: 15px; border-radius: 8px; margin-bottom: 15px;\">
                            <h4>Métriques de performance:</h4>
                            <ul>
                                <li>Temps d'exécution: ${data.metrics.execution_time || 'N/A'}</li>
                                <li>Utilisation mémoire: ${data.metrics.memory_usage || 'N/A'}</li>
                                <li>Complexité estimée: ${data.metrics.complexity || 'N/A'}</li>
                            </ul>
                        </div>
                        
                        <div style=\"text-align: center; margin-top: 20px;\">
                            <button onclick=\"this.closest('.modal').remove()\" class=\"btn btn-primary\">Fermer</button>
                        </div>
                    </div>
                `;
                
                document.body.appendChild(modal);
                modal.classList.add('modal');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showToast('Erreur lors du chargement des détails', 'danger');
        });
    }

    console.log('✅ User Feedback IA - Interface initialisée avec succès !');
    console.log('🤖 Système de validation automatique par IA activé');
";

// Include footer
include 'footer.php';
?>

