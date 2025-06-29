<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Récupérer les informations de l'utilisateur
$user = getCurrentUser();

// Configuration de la page
$page_title = "Notifications des Problèmes";
$additional_css = "
    .feedback-container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 20px;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        text-align: center;
        border-left: 4px solid #3498db;
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 25px rgba(0,0,0,0.15);
    }

    .stat-card.problems { border-left-color: #e74c3c; }
    .stat-card.solutions { border-left-color: #2ecc71; }
    .stat-card.pending { border-left-color: #f39c12; }
    .stat-card.approved { border-left-color: #9b59b6; }

    .stat-value {
        font-size: 2.5em;
        font-weight: bold;
        margin-bottom: 10px;
    }

    .stat-card.problems .stat-value { color: #e74c3c; }
    .stat-card.solutions .stat-value { color: #2ecc71; }
    .stat-card.pending .stat-value { color: #f39c12; }
    .stat-card.approved .stat-value { color: #9b59b6; }

    .stat-label {
        color: #7f8c8d;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.9em;
        letter-spacing: 0.5px;
    }

    .section {
        background: white;
        border-radius: 12px;
        box-shadow: 0 3px 15px rgba(0,0,0,0.1);
        margin-bottom: 30px;
        overflow: hidden;
    }

    .section-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 20px 25px;
        font-size: 1.3em;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .section-content {
        padding: 25px;
    }

    .problem-card {
        border: 1px solid #e0e6ed;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        background: #fafbfc;
        transition: all 0.3s;
        position: relative;
    }

    .problem-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        border-color: #3498db;
    }

    .problem-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 15px;
    }

    .problem-title {
        font-size: 1.2em;
        font-weight: 600;
        color: #2c3e50;
        margin: 0;
        flex: 1;
        margin-right: 15px;
    }

    .problem-date {
        color: #7f8c8d;
        font-size: 0.9em;
        white-space: nowrap;
    }

    .problem-description {
        color: #4a5568;
        line-height: 1.6;
        margin-bottom: 15px;
    }

    .problem-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: center;
        margin-bottom: 15px;
    }

    .difficulty {
        padding: 4px 12px;
        border-radius: 15px;
        font-size: 0.8em;
        font-weight: 600;
        text-transform: uppercase;
    }

    .difficulty.easy { background: #d5f5e3; color: #27ae60; }
    .difficulty.medium { background: #fef9e7; color: #f39c12; }
    .difficulty.hard { background: #fdedec; color: #e74c3c; }

    .language-tag {
        background: #ecf0f1;
        color: #2c3e50;
        padding: 4px 12px;
        border-radius: 15px;
        font-size: 0.8em;
        font-weight: 600;
    }

    .solutions-section {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 2px solid #ecf0f1;
    }

    .solutions-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 15px;
    }

    .solutions-count {
        background: #3498db;
        color: white;
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.9em;
        font-weight: 600;
    }

    .solution-item {
        background: white;
        border: 1px solid #e0e6ed;
        border-radius: 6px;
        padding: 15px;
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.3s;
    }

    .solution-item:hover {
        border-color: #3498db;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .solution-info {
        flex: 1;
    }

    .solution-author {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 5px;
    }

    .solution-date {
        color: #7f8c8d;
        font-size: 0.9em;
    }

    .solution-status {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.8em;
        font-weight: 600;
        text-transform: uppercase;
    }

    .solution-status.pending {
        background: #fef9e7;
        color: #f39c12;
    }

    .solution-status.approved {
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
        margin-left: 15px;
    }

    .btn {
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 600;
        text-decoration: none;
        font-size: 0.9em;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .btn-primary {
        background: #3498db;
        color: white;
    }

    .btn-primary:hover {
        background: #2980b9;
        transform: translateY(-1px);
    }

    .btn-success {
        background: #2ecc71;
        color: white;
    }

    .btn-success:hover {
        background: #27ae60;
        transform: translateY(-1px);
    }

    .btn-danger {
        background: #e74c3c;
        color: white;
    }

    .btn-danger:hover {
        background: #c0392b;
        transform: translateY(-1px);
    }

    .empty-state {
        text-align: center;
        padding: 50px 20px;
        color: #7f8c8d;
    }

    .empty-state i {
        font-size: 4em;
        margin-bottom: 20px;
        opacity: 0.5;
    }

    .empty-state h3 {
        margin-bottom: 10px;
        color: #2c3e50;
    }

    .alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .alert-success {
        background: #d5f5e3;
        color: #27ae60;
        border: 1px solid #c3e6cb;
    }

    .alert-danger {
        background: #fdedec;
        color: #e74c3c;
        border: 1px solid #f5c6cb;
    }

    .alert-info {
        background: #e8f4fd;
        color: #3498db;
        border: 1px solid #bee5eb;
    }

    @media (max-width: 768px) {
        .feedback-container {
            padding: 10px;
        }
        
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .problem-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
        
        .problem-meta {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }
        
        .solution-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 15px;
        }
        
        .solution-actions {
            margin-left: 0;
            width: 100%;
        }
        
        .btn {
            flex: 1;
            justify-content: center;
        }
    }
";

// Inclure l'en-tête
include 'header.php';

// Récupérer les statistiques de l'utilisateur
$conn = connect();

try {
    // Statistiques des problèmes publiés par l'utilisateur
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM problems WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $problems_count = $stmt->fetchColumn();

    // Statistiques des solutions soumises par l'utilisateur
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM solutions WHERE user_id = ?");
    $stmt->execute([$user['id']]);
    $solutions_count = $stmt->fetchColumn();

    // Solutions en attente
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM solutions WHERE user_id = ? AND status = 'pending'");
    $stmt->execute([$user['id']]);
    $pending_solutions = $stmt->fetchColumn();

    // Solutions approuvées
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM solutions WHERE user_id = ? AND status = 'approved'");
    $stmt->execute([$user['id']]);
    $approved_solutions = $stmt->fetchColumn();

} catch (Exception $e) {
    error_log("Erreur lors de la récupération des statistiques: " . $e->getMessage());
    $problems_count = $solutions_count = $pending_solutions = $approved_solutions = 0;
}
?>

<div class="feedback-container">
    <h1><i class="fas fa-chart-bar"></i> Tableau de Bord - Notifications</h1>
    
    <!-- Statistiques -->
    <div class="stats-grid">
        <div class="stat-card problems">
            <div class="stat-value"><?= $problems_count ?></div>
            <div class="stat-label">Problèmes Publiés</div>
        </div>
        <div class="stat-card solutions">
            <div class="stat-value"><?= $solutions_count ?></div>
            <div class="stat-label">Solutions Soumises</div>
        </div>
        <div class="stat-card pending">
            <div class="stat-value"><?= $pending_solutions ?></div>
            <div class="stat-label">En Attente</div>
        </div>
        <div class="stat-card approved">
            <div class="stat-value"><?= $approved_solutions ?></div>
            <div class="stat-label">Approuvées</div>
        </div>
    </div>

    <!-- Mes Problèmes et leurs Solutions -->
    <div class="section">
        <div class="section-header">
            <i class="fas fa-code-branch"></i>
            Mes Problèmes et Solutions Reçues
        </div>
        <div class="section-content">
            <?php
            try {
                // Récupérer les problèmes de l'utilisateur avec les solutions reçues
                $stmt = $conn->prepare("
                    SELECT p.*, 
                           COUNT(s.id) as solutions_count,
                           COUNT(CASE WHEN s.status = 'pending' THEN 1 END) as pending_count,
                           COUNT(CASE WHEN s.status = 'approved' THEN 1 END) as approved_count
                    FROM problems p
                    LEFT JOIN solutions s ON p.problem_id = s.problem_id
                    WHERE p.user_id = ?
                    GROUP BY p.problem_id, p.title, p.description, p.difficulty, p.language, p.created_at
                    ORDER BY p.created_at DESC
                ");
                $stmt->execute([$user['id']]);
                $user_problems = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($user_problems)):
            ?>
                <div class="empty-state">
                    <i class="fas fa-code"></i>
                    <h3>Aucun problème publié</h3>
                    <p>Vous n'avez pas encore publié de problèmes.</p>
                    <a href="expublier.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Publier un problème
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($user_problems as $problem): ?>
                    <div class="problem-card">
                        <div class="problem-header">
                            <h3 class="problem-title"><?= htmlspecialchars($problem['title']) ?></h3>
                            <div class="problem-date">
                                <i class="fas fa-calendar"></i>
                                <?= date('d/m/Y à H:i', strtotime($problem['created_at'])) ?>
                            </div>
                        </div>

                        <div class="problem-description">
                            <?= nl2br(htmlspecialchars(substr($problem['description'], 0, 200))) ?>
                            <?php if (strlen($problem['description']) > 200): ?>
                                <span style="color: #3498db;">... <a href="problem.php?id=<?= $problem['problem_id'] ?>">Lire la suite</a></span>
                            <?php endif; ?>
                        </div>

                        <div class="problem-meta">
                            <span class="difficulty <?= htmlspecialchars($problem['difficulty']) ?>">
                                <?= ucfirst(htmlspecialchars($problem['difficulty'])) ?>
                            </span>
                            <span class="language-tag">
                                <i class="fas fa-code"></i> <?= htmlspecialchars(ucfirst($problem['language'])) ?>
                            </span>
                            <span style="color: #7f8c8d;">
                                <i class="fas fa-award"></i> <?= htmlspecialchars($problem['points']) ?> points
                            </span>
                        </div>

                        <?php if ($problem['solutions_count'] > 0): ?>
                            <div class="solutions-section">
                                <div class="solutions-header">
                                    <h4><i class="fas fa-lightbulb"></i> Solutions reçues</h4>
                                    <div class="solutions-count">
                                        <?= $problem['solutions_count'] ?> solution<?= $problem['solutions_count'] > 1 ? 's' : '' ?>
                                    </div>
                                </div>

                                <?php
                                // Récupérer les solutions pour ce problème
                                $solutions_stmt = $conn->prepare("
                                    SELECT s.*, u.username, u.name as author_name
                                    FROM solutions s
                                    JOIN users u ON s.user_id = u.id
                                    WHERE s.problem_id = ?
                                    ORDER BY s.created_at DESC
                                ");
                                $solutions_stmt->execute([$problem['problem_id']]);
                                $solutions = $solutions_stmt->fetchAll(PDO::FETCH_ASSOC);
                                ?>

                                <?php foreach ($solutions as $solution): ?>
                                    <div class="solution-item">
                                        <div class="solution-info">
                                            <div class="solution-author">
                                                <i class="fas fa-user"></i>
                                                <?= htmlspecialchars($solution['author_name'] ?: $solution['username']) ?>
                                            </div>
                                            <div class="solution-date">
                                                <i class="fas fa-clock"></i>
                                                Soumise le <?= date('d/m/Y à H:i', strtotime($solution['created_at'])) ?>
                                            </div>
                                        </div>

                                        <div class="solution-status <?= htmlspecialchars($solution['status']) ?>">
                                            <?php
                                            switch($solution['status']) {
                                                case 'pending': echo 'En attente'; break;
                                                case 'approved': echo 'Approuvée'; break;
                                                case 'rejected': echo 'Rejetée'; break;
                                                default: echo ucfirst($solution['status']);
                                            }
                                            ?>
                                        </div>

                                        <div class="solution-actions">
                                            <button class="btn btn-primary" onclick="viewSolution(<?= $solution['id'] ?>)">
                                                <i class="fas fa-eye"></i> Voir
                                            </button>
                                            
                                            <?php if ($solution['status'] === 'pending'): ?>
                                                <button class="btn btn-success" onclick="approveSolution(<?= $solution['id'] ?>)">
                                                    <i class="fas fa-check"></i> Approuver
                                                </button>
                                                <button class="btn btn-danger" onclick="rejectSolution(<?= $solution['id'] ?>)">
                                                    <i class="fas fa-times"></i> Rejeter
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="solutions-section">
                                <div style="text-align: center; padding: 20px; color: #7f8c8d;">
                                    <i class="fas fa-inbox" style="font-size: 2em; margin-bottom: 10px; opacity: 0.5;"></i>
                                    <p>Aucune solution reçue pour ce problème</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Mes Solutions Soumises -->
    <div class="section">
        <div class="section-header">
            <i class="fas fa-paper-plane"></i>
            Mes Solutions Soumises
        </div>
        <div class="section-content">
            <?php
            try {
                // Récupérer les solutions soumises par l'utilisateur
                $stmt = $conn->prepare("
                    SELECT s.*, p.title as problem_title, p.user_id as problem_owner_id,
                           owner.username as problem_owner_username, owner.name as problem_owner_name
                    FROM solutions s
                    JOIN problems p ON s.problem_id = p.problem_id
                    JOIN users owner ON p.user_id = owner.id
                    WHERE s.user_id = ?
                    ORDER BY s.created_at DESC
                ");
                $stmt->execute([$user['id']]);
                $user_solutions = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (empty($user_solutions)):
            ?>
                <div class="empty-state">
                    <i class="fas fa-lightbulb"></i>
                    <h3>Aucune solution soumise</h3>
                    <p>Vous n'avez pas encore soumis de solutions.</p>
                    <a href="exacueil.php" class="btn btn-primary">
                        <i class="fas fa-search"></i> Parcourir les problèmes
                    </a>
                </div>
            <?php else: ?>
                <?php foreach ($user_solutions as $solution): ?>
                    <div class="solution-item">
                        <div class="solution-info">
                            <div class="solution-author">
                                <i class="fas fa-code"></i>
                                Solution pour: <strong><?= htmlspecialchars($solution['problem_title']) ?></strong>
                            </div>
                            <div class="solution-date">
                                <i class="fas fa-user"></i>
                                Problème de <?= htmlspecialchars($solution['problem_owner_name'] ?: $solution['problem_owner_username']) ?>
                                <br>
                                <i class="fas fa-clock"></i>
                                Soumise le <?= date('d/m/Y à H:i', strtotime($solution['created_at'])) ?>
                                <?php if ($solution['evaluated_at']): ?>
                                    - Évaluée le <?= date('d/m/Y à H:i', strtotime($solution['evaluated_at'])) ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="solution-status <?= htmlspecialchars($solution['status']) ?>">
                            <?php
                            switch($solution['status']) {
                                case 'pending': echo 'En attente'; break;
                                case 'approved': echo 'Approuvée'; break;
                                case 'rejected': echo 'Rejetée'; break;
                                default: echo ucfirst($solution['status']);
                            }
                            ?>
                        </div>

                        <div class="solution-actions">
                            <button class="btn btn-primary" onclick="viewMySolution(<?= $solution['id'] ?>)">
                                <i class="fas fa-eye"></i> Voir ma solution
                            </button>
                            <a href="problem.php?id=<?= $solution['problem_id'] ?>" class="btn btn-primary">
                                <i class="fas fa-external-link-alt"></i> Voir le problème
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal pour afficher les solutions -->
<div id="solutionModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modalTitle">Solution</h3>
            <span class="close" onclick="closeSolutionModal()">&times;</span>
        </div>
        <div class="modal-body" id="modalBody">
            <!-- Le contenu sera chargé dynamiquement -->
        </div>
        <div class="modal-footer">
            <button class="btn btn-primary" onclick="closeSolutionModal()">Fermer</button>
        </div>
    </div>
</div>

<style>
/* Styles pour la modal */
.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    backdrop-filter: blur(5px);
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 0;
    border-radius: 12px;
    width: 90%;
    max-width: 800px;
    max-height: 80vh;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    animation: modalSlideIn 0.3s ease-out;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: translateY(-50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.3em;
}

.close {
    color: white;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s;
}

.close:hover {
    transform: scale(1.1);
}

.modal-body {
    padding: 25px;
    max-height: 60vh;
    overflow-y: auto;
}

.modal-footer {
    padding: 20px 25px;
    border-top: 1px solid #e0e6ed;
    text-align: right;
    background: #f8f9fa;
}

.solution-code {
    background: #2c3e50;
    color: #ecf0f1;
    padding: 20px;
    border-radius: 8px;
    font-family: 'Courier New', monospace;
    overflow-x: auto;
    margin: 15px 0;
    line-height: 1.6;
}

.solution-explanation {
    background: #f0f7fb;
    padding: 20px;
    border-radius: 8px;
    border-left: 4px solid #3498db;
    margin: 15px 0;
    line-height: 1.6;
}
</style>

<?php
$additional_scripts = "
    // Fonction pour voir une solution
    function viewSolution(solutionId) {
        fetch('get_solution_details.php?id=' + solutionId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('modalTitle').textContent = 'Solution de ' + data.solution.author_name;
                    
                    let content = '<div class=\"solution-details\">';
                    
                    if (data.solution.explanation) {
                        content += '<div class=\"solution-explanation\">';
                        content += '<h4><i class=\"fas fa-comment-alt\"></i> Explication</h4>';
                        content += '<p>' + data.solution.explanation.replace(/\\n/g, '<br>') + '</p>';
                        content += '</div>';
                    }
                    
                    content += '<div class=\"solution-code\">';
                    content += '<h4><i class=\"fas fa-code\"></i> Code de la solution</h4>';
                    content += '<pre>' + data.solution.solution_code + '</pre>';
                    content += '</div>';
                    
                    content += '</div>';
                    
                    document.getElementById('modalBody').innerHTML = content;
                    document.getElementById('solutionModal').style.display = 'block';
                } else {
                    showMessage('Erreur lors du chargement de la solution: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showMessage('Erreur lors du chargement de la solution', 'error');
            });
    }
    
    // Fonction pour voir ma propre solution
    function viewMySolution(solutionId) {
        fetch('get_solution_details.php?id=' + solutionId + '&own=1')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('modalTitle').textContent = 'Ma Solution';
                    
                    let content = '<div class=\"solution-details\">';
                    
                    // Statut de la solution
                    let statusClass = data.solution.status;
                    let statusText = '';
                    switch(data.solution.status) {
                        case 'pending': statusText = 'En attente d\\'évaluation'; break;
                        case 'approved': statusText = 'Approuvée'; break;
                        case 'rejected': statusText = 'Rejetée'; break;
                        default: statusText = data.solution.status;
                    }
                    
                    content += '<div class=\"alert alert-info\">';
                    content += '<i class=\"fas fa-info-circle\"></i> ';
                    content += '<strong>Statut:</strong> ' + statusText;
                    if (data.solution.evaluated_at) {
                        content += ' (évaluée le ' + new Date(data.solution.evaluated_at).toLocaleDateString('fr-FR') + ')';
                    }
                    content += '</div>';
                    
                    if (data.solution.explanation) {
                        content += '<div class=\"solution-explanation\">';
                        content += '<h4><i class=\"fas fa-comment-alt\"></i> Mon explication</h4>';
                        content += '<p>' + data.solution.explanation.replace(/\\n/g, '<br>') + '</p>';
                        content += '</div>';
                    }
                    
                    content += '<div class=\"solution-code\">';
                    content += '<h4><i class=\"fas fa-code\"></i> Mon code</h4>';
                    content += '<pre>' + data.solution.solution_code + '</pre>';
                    content += '</div>';
                    
                    // Feedback si rejeté
                    if (data.solution.status === 'rejected' && data.solution.feedback) {
                        content += '<div class=\"alert alert-danger\">';
                        content += '<h4><i class=\"fas fa-exclamation-triangle\"></i> Raison du rejet</h4>';
                        content += '<p>' + data.solution.feedback.replace(/\\n/g, '<br>') + '</p>';
                        content += '</div>';
                    }
                    
                    content += '</div>';
                    
                    document.getElementById('modalBody').innerHTML = content;
                    document.getElementById('solutionModal').style.display = 'block';
                } else {
                    showMessage('Erreur lors du chargement de la solution: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showMessage('Erreur lors du chargement de la solution', 'error');
            });
    }
    
    // Fonction pour approuver une solution
    function approveSolution(solutionId) {
        if (!confirm('Êtes-vous sûr de vouloir approuver cette solution ?')) {
            return;
        }
        
        fetch('manage_solution.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'approve',
                solution_id: solutionId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Solution approuvée avec succès!', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showMessage('Erreur: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showMessage('Erreur lors de l\\'approbation', 'error');
        });
    }
    
    // Fonction pour rejeter une solution
    function rejectSolution(solutionId) {
        const reason = prompt('Raison du rejet (optionnel):');
        if (reason === null) return; // Annulé
        
        if (!confirm('Êtes-vous sûr de vouloir rejeter cette solution ?')) {
            return;
        }
        
        fetch('manage_solution.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'reject',
                solution_id: solutionId,
                feedback: reason
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showMessage('Solution rejetée', 'info');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                showMessage('Erreur: ' + data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showMessage('Erreur lors du rejet', 'error');
        });
    }
    
    // Fonction pour fermer la modal
    function closeSolutionModal() {
        document.getElementById('solutionModal').style.display = 'none';
    }
    
    // Fermer la modal en cliquant à l'extérieur
    window.onclick = function(event) {
        const modal = document.getElementById('solutionModal');
        if (event.target === modal) {
            closeSolutionModal();
        }
    }
    
    // Fonction pour afficher des messages
    function showMessage(message, type = 'info') {
        const messageDiv = document.createElement('div');
        messageDiv.style.position = 'fixed';
        messageDiv.style.top = '20px';
        messageDiv.style.right = '20px';
        messageDiv.style.padding = '15px 20px';
        messageDiv.style.borderRadius = '8px';
        messageDiv.style.zIndex = '10000';
        messageDiv.style.maxWidth = '350px';
        messageDiv.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
        messageDiv.style.transition = 'opacity 0.3s ease';
        
        switch(type) {
            case 'success':
                messageDiv.style.backgroundColor = '#d4edda';
                messageDiv.style.color = '#155724';
                messageDiv.style.border = '1px solid #c3e6cb';
                messageDiv.innerHTML = '✅ ' + message;
                break;
            case 'error':
                messageDiv.style.backgroundColor = '#f8d7da';
                messageDiv.style.color = '#721c24';
                messageDiv.style.border = '1px solid #f5c6cb';
                messageDiv.innerHTML = '❌ ' + message;
                break;
            case 'info':
                messageDiv.style.backgroundColor = '#d1ecf1';
                messageDiv.style.color = '#0c5460';
                messageDiv.style.border = '1px solid #bee5eb';
                messageDiv.innerHTML = 'ℹ️ ' + message;
                break;
        }
        
        document.body.appendChild(messageDiv);
        
        setTimeout(() => {
            messageDiv.style.opacity = '0';
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.parentNode.removeChild(messageDiv);
                }
            }, 300);
        }, 4000);
    }
    
    // Animation d'entrée pour les cartes
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.problem-card, .solution-item');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => {
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
        
        // Animation pour les statistiques
        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(-20px)';
            setTimeout(() => {
                card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 150);
        });
    });
";

include 'footer.php';
            }
            ?>
