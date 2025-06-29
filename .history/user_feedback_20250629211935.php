<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';
require_once 'solution_blur_handler.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Récupérer les informations de l'utilisateur
$user = getCurrentUser();
$user_id = $user['id'];

// Configuration de la page
$page_title = "Notifications et Solutions";

// Récupérer les paramètres de filtrage
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$filter_type = isset($_GET['type']) ? $_GET['type'] : 'all';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

try {
    $conn = connect();
    if (!$conn) {
        throw new Exception("Erreur de connexion à la base de données");
    }

    // Construire la requête pour récupérer les problèmes de l'utilisateur avec leurs solutions
    $where_conditions = ["p.user_id = ?"];
    $params = [$user_id];

    if ($filter_status !== 'all') {
        $where_conditions[] = "s.status = ?";
        $params[] = $filter_status;
    }

    $where_clause = implode(' AND ', $where_conditions);

    // Requête principale pour récupérer les problèmes et leurs solutions
    $stmt = $conn->prepare("
        SELECT 
            p.problem_id,
            p.title as problem_title,
            p.description as problem_description,
            p.created_at as problem_created_at,
            s.id as solution_id,
            s.solution_code,
            s.explanation,
            s.status as solution_status,
            s.created_at as solution_created_at,
            s.evaluated_at,
            s.price,
            s.rating,
            s.downloads,
            u.id as solver_id,
            u.username as solver_username,
            u.name as solver_name,
            u.avatar_url as solver_avatar,
            CASE WHEN pay.id IS NOT NULL THEN 1 ELSE 0 END as has_payments
        FROM problems p
        LEFT JOIN solutions s ON p.problem_id = s.problem_id
        LEFT JOIN users u ON s.user_id = u.id
        LEFT JOIN payments pay ON s.id = pay.solution_id AND pay.status = 'completed'
        WHERE {$where_clause}
        ORDER BY 
            CASE WHEN s.created_at IS NOT NULL THEN s.created_at ELSE p.created_at END DESC
        LIMIT ? OFFSET ?
    ");

    $params[] = $per_page;
    $params[] = $offset;
    $stmt->execute($params);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Compter le total pour la pagination
    $count_stmt = $conn->prepare("
        SELECT COUNT(DISTINCT CONCAT(p.problem_id, '-', COALESCE(s.id, 0)))
        FROM problems p
        LEFT JOIN solutions s ON p.problem_id = s.problem_id
        LEFT JOIN users u ON s.user_id = u.id
        WHERE {$where_clause}
    ");
    $count_params = array_slice($params, 0, -2); // Enlever LIMIT et OFFSET
    $count_stmt->execute($count_params);
    $total_items = $count_stmt->fetchColumn();
    $total_pages = ceil($total_items / $per_page);

    // Organiser les résultats par problème
    $problems_with_solutions = [];
    foreach ($results as $row) {
        $problem_id = $row['problem_id'];
        
        if (!isset($problems_with_solutions[$problem_id])) {
            $problems_with_solutions[$problem_id] = [
                'problem' => [
                    'id' => $row['problem_id'],
                    'title' => $row['problem_title'],
                    'description' => $row['problem_description'],
                    'created_at' => $row['problem_created_at']
                ],
                'solutions' => []
            ];
        }
        
        if ($row['solution_id']) {
            $problems_with_solutions[$problem_id]['solutions'][] = [
                'id' => $row['solution_id'],
                'solution_code' => $row['solution_code'],
                'explanation' => $row['explanation'],
                'status' => $row['solution_status'],
                'created_at' => $row['solution_created_at'],
                'evaluated_at' => $row['evaluated_at'],
                'price' => $row['price'],
                'rating' => $row['rating'],
                'downloads' => $row['downloads'],
                'solver_id' => $row['solver_id'],
                'solver_username' => $row['solver_username'],
                'solver_name' => $row['solver_name'],
                'solver_avatar' => $row['solver_avatar'],
                'has_payments' => $row['has_payments'],
                'user_id' => $row['solver_id'] // Pour le système de flou
            ];
        }
    }

} catch (Exception $e) {
    error_log("Erreur user_feedback.php: " . $e->getMessage());
    $problems_with_solutions = [];
    $total_pages = 0;
}

// CSS spécifique à cette page
$additional_css = "
    .feedback-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }

    .filters-section {
        background: white;
        padding: 20px;
        border-radius: 12px;
        margin-bottom: 25px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .filters-row {
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
        color: #2c3e50;
        font-size: 14px;
    }

    .filter-select {
        padding: 8px 12px;
        border: 2px solid #e9ecef;
        border-radius: 6px;
        background: white;
        color: #2c3e50;
        font-size: 14px;
        min-width: 150px;
    }

    .filter-select:focus {
        outline: none;
        border-color: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
    }

    .stats-overview {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 25px;
    }

    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        text-align: center;
        border-left: 4px solid #3498db;
    }

    .stat-card.pending {
        border-left-color: #f39c12;
    }

    .stat-card.approved {
        border-left-color: #27ae60;
    }

    .stat-card.rejected {
        border-left-color: #e74c3c;
    }

    .stat-value {
        font-size: 28px;
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 5px;
    }

    .stat-label {
        color: #7f8c8d;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .problem-card {
        background: white;
        border-radius: 12px;
        margin-bottom: 30px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        overflow: hidden;
        border: 1px solid #f0f0f0;
    }

    .problem-header {
        padding: 25px;
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-bottom: 1px solid #e9ecef;
    }

    .problem-title {
        font-size: 22px;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .problem-meta {
        display: flex;
        gap: 20px;
        color: #7f8c8d;
        font-size: 14px;
        margin-bottom: 15px;
    }

    .problem-description {
        color: #4a5568;
        line-height: 1.6;
        margin-bottom: 15px;
    }

    .solutions-section {
        padding: 0;
    }

    .solutions-header {
        padding: 20px 25px;
        background: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .solutions-count {
        font-weight: 600;
        color: #2c3e50;
    }

    .solution-item {
        border-bottom: 1px solid #f0f0f0;
        position: relative;
    }

    .solution-item:last-child {
        border-bottom: none;
    }

    .solution-header {
        padding: 20px 25px 15px;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .solution-meta {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .developer-avatar {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #e9ecef;
    }

    .developer-info {
        display: flex;
        flex-direction: column;
    }

    .developer-name {
        font-weight: 600;
        color: #2c3e50;
        font-size: 16px;
    }

    .solution-date {
        color: #7f8c8d;
        font-size: 13px;
    }

    .solution-status-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .solution-status-badge.pending {
        background: #fef9e7;
        color: #f39c12;
        border: 1px solid #f7dc6f;
    }

    .solution-status-badge.approved {
        background: #d5f5e3;
        color: #27ae60;
        border: 1px solid #a9dfbf;
    }

    .solution-status-badge.rejected {
        background: #fdedec;
        color: #e74c3c;
        border: 1px solid #f1948a;
    }

    .solution-price {
        font-size: 18px;
        font-weight: bold;
        color: #27ae60;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .solution-content {
        padding: 0 25px 20px;
    }

    .solution-stats {
        display: flex;
        gap: 20px;
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #f0f0f0;
        color: #7f8c8d;
        font-size: 14px;
    }

    .no-solutions {
        padding: 40px;
        text-align: center;
        color: #7f8c8d;
    }

    .no-solutions i {
        font-size: 48px;
        margin-bottom: 15px;
        color: #bdc3c7;
    }

    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 30px;
        gap: 5px;
    }

    .pagination a,
    .pagination span {
        display: inline-block;
        padding: 10px 15px;
        text-decoration: none;
        border-radius: 6px;
        transition: all 0.3s;
        font-weight: 500;
    }

    .pagination a {
        background: white;
        color: #3498db;
        border: 2px solid #3498db;
    }

    .pagination a:hover {
        background: #3498db;
        color: white;
        transform: translateY(-2px);
    }

    .pagination span {
        background: #3498db;
        color: white;
        border: 2px solid #3498db;
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .empty-state i {
        font-size: 64px;
        color: #bdc3c7;
        margin-bottom: 20px;
    }

    .empty-state h3 {
        color: #2c3e50;
        margin-bottom: 10px;
    }

    .empty-state p {
        color: #7f8c8d;
        margin-bottom: 25px;
    }

    .btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 24px;
        border-radius: 6px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
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

    /* Responsive */
    @media (max-width: 768px) {
        .feedback-container {
            padding: 10px;
        }

        .filters-row {
            flex-direction: column;
            align-items: stretch
        }

        .filter-select {
            min-width: auto;
            width: 100%;
        }

        .stats-overview {
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
        }

        .problem-header,
        .solution-header,
        .solution-content {
            padding: 15px;
        }

        .solution-header {
            flex-direction: column;
            gap: 15px;
            align-items: flex-start;
        }

        .solution-meta {
            width: 100%;
        }

        .solution-stats {
            flex-direction: column;
            gap: 10px;
        }

        .pagination {
            flex-wrap: wrap;
        }
    }

    /* Animation d'entrée */
    .problem-card {
        animation: slideInUp 0.5s ease-out;
    }

    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
";

// Inclure l'en-tête
include 'header.php';
?>

<!-- Inclure les assets CSS et JS pour le système de flou -->
<?php includeSolutionBlurAssets(); ?>

<div class="feedback-container">
    <h1><i class="fas fa-bell"></i> Notifications et Solutions</h1>
    
    <!-- Section des filtres -->
    <div class="filters-section">
        <form method="GET" action="">
            <div class="filters-row">
                <div class="filter-group">
                    <label for="status">Statut des solutions</label>
                    <select name="status" id="status" class="filter-select" onchange="this.form.submit()">
                        <option value="all" <?= $filter_status === 'all' ? 'selected' : '' ?>>Tous les statuts</option>
                        <option value="pending" <?= $filter_status === 'pending' ? 'selected' : '' ?>>En attente</option>
                        <option value="approved" <?= $filter_status === 'approved' ? 'selected' : '' ?>>Approuvées</option>
                        <option value="rejected" <?= $filter_status === 'rejected' ? 'selected' : '' ?>>Rejetées</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="type">Type d'affichage</label>
                    <select name="type" id="type" class="filter-select" onchange="this.form.submit()">
                        <option value="all" <?= $filter_type === 'all' ? 'selected' : '' ?>>Tous</option>
                        <option value="with_solutions" <?= $filter_type === 'with_solutions' ? 'selected' : '' ?>>Avec solutions</option>
                        <option value="without_solutions" <?= $filter_type === 'without_solutions' ? 'selected' : '' ?>>Sans solutions</option>
                    </select>
                </div>
            </div>
        </form>
    </div>

    <?php
    // Calculer les statistiques
    $stats = [
        'total_problems' => 0,
        'total_solutions' => 0,
        'pending_solutions' => 0,
        'approved_solutions' => 0,
        'rejected_solutions' => 0,
        'total_earnings' => 0
    ];

    foreach ($problems_with_solutions as $item) {
        $stats['total_problems']++;
        foreach ($item['solutions'] as $solution) {
            $stats['total_solutions']++;
            switch ($solution['status']) {
                case 'pending':
                    $stats['pending_solutions']++;
                    break;
                case 'approved':
                    $stats['approved_solutions']++;
                    if ($solution['has_payments']) {
                        $stats['total_earnings'] += $solution['price'];
                    }
                    break;
                case 'rejected':
                    $stats['rejected_solutions']++;
                    break;
            }
        }
    }
    ?>

    <!-- Aperçu des statistiques -->
    <div class="stats-overview">
        <div class="stat-card">
            <div class="stat-value"><?= $stats['total_problems'] ?></div>
            <div class="stat-label">Problèmes publiés</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $stats['total_solutions'] ?></div>
            <div class="stat-label">Solutions reçues</div>
        </div>
        <div class="stat-card pending">
            <div class="stat-value"><?= $stats['pending_solutions'] ?></div>
            <div class="stat-label">En attente</div>
        </div>
        <div class="stat-card approved">
            <div class="stat-value"><?= $stats['approved_solutions'] ?></div>
            <div class="stat-label">Approuvées</div>
        </div>
        <div class="stat-card rejected">
            <div class="stat-value"><?= $stats['rejected_solutions'] ?></div>
            <div class="stat-label">Rejetées</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= number_format($stats['total_earnings'], 2) ?>€</div>
            <div class="stat-label">Gains estimés</div>
        </div>
    </div>

    <!-- Liste des problèmes et solutions -->
    <?php if (empty($problems_with_solutions)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h3>Aucun problème trouvé</h3>
            <p>Vous n'avez pas encore publié de problèmes ou aucun ne correspond aux filtres sélectionnés.</p>
            <a href="expublier.php" class="btn btn-primary">
                <i class="fas fa-plus"></i> Publier un problème
            </a>
        </div>
    <?php else: ?>
        <?php foreach ($problems_with_solutions as $item): ?>
            <div class="problem-card">
                <!-- En-tête du problème -->
                <div class="problem-header">
                    <h2 class="problem-title">
                        <i class="fas fa-code"></i>
                        <?= htmlspecialchars($item['problem']['title']) ?>
                    </h2>
                    
                    <div class="problem-meta">
                        <span><i class="fas fa-calendar"></i> Publié le <?= date('d/m/Y à H:i', strtotime($item['problem']['created_at'])) ?></span>
                        <span><i class="fas fa-eye"></i> <a href="problem.php?id=<?= $item['problem']['id'] ?>" target="_blank">Voir le problème</a></span>
                    </div>
                    
                    <div class="problem-description">
                        <?= nl2br(htmlspecialchars(substr($item['problem']['description'], 0, 200))) ?>
                        <?php if (strlen($item['problem']['description']) > 200): ?>
                            <span style="color: #3498db;">... <a href="problem.php?id=<?= $item['problem']['id'] ?>" target="_blank">Lire la suite</a></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Section des solutions -->
                <div class="solutions-section">
                    <?php if (empty($item['solutions'])): ?>
                        <div class="no-solutions">
                            <i class="fas fa-search"></i>
                            <h4>Aucune solution soumise</h4>
                            <p>Aucun développeur n'a encore proposé de solution pour ce problème.</p>
                        </div>
                    <?php else: ?>
                        <div class="solutions-header">
                            <span class="solutions-count">
                                <i class="fas fa-lightbulb"></i>
                                <?= count($item['solutions']) ?> solution<?= count($item['solutions']) > 1 ? 's' : '' ?> proposée<?= count($item['solutions']) > 1 ? 's' : '' ?>
                            </span>
                        </div>

                        <?php foreach ($item['solutions'] as $solution): ?>
                            <div class="solution-item">
                                <div class="solution-header">
                                    <div class="solution-meta">
                                        <img src="<?= htmlspecialchars($solution['solver_avatar'] ?? 'assets/default-avatar.png') ?>" 
                                             alt="Avatar" class="developer-avatar">
                                        <div class="developer-info">
                                            <div class="developer-name"><?= htmlspecialchars($solution['solver_name'] ?? $solution['solver_username']) ?></div>
                                            <div class="solution-date">
                                                Soumis le <?= date('d/m/Y à H:i', strtotime($solution['created_at'])) ?>
                                                <?php if ($solution['evaluated_at']): ?>
                                                    • Évalué le <?= date('d/m/Y à H:i', strtotime($solution['evaluated_at'])) ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 10px;">
                                        <span class="solution-status-badge <?= $solution['status'] ?>">
                                            <?php
                                            switch ($solution['status']) {
                                                case 'pending':
                                                    echo '<i class="fas fa-clock"></i> En attente';
                                                    break;
                                                case 'approved':
                                                    echo '<i class="fas fa-check"></i> Approuvée';
                                                    break;
                                                case 'rejected':
                                                    echo '<i class="fas fa-times"></i> Rejetée';
                                                    break;
                                                default:
                                                    echo '<i class="fas fa-question"></i> ' . ucfirst($solution['status']);
                                            }
                                            ?>
                                        </span>
                                        
                                        <?php if ($solution['price'] > 0): ?>
                                            <div class="solution-price">
                                                <i class="fas fa-euro-sign"></i>
                                                <?= number_format($solution['price'], 2) ?> €
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="solution-content">
                                    <?php if ($solution['status'] === 'approved'): ?>
                                        <!-- Utiliser le système de flou pour les solutions approuvées -->
                                        <?= renderSolutionWithBlur($solution) ?>
                                    <?php else: ?>
                                        <!-- Affichage normal pour les solutions non approuvées -->
                                        <div class="solution-content-preview">
                                            <?php if (!empty($solution['explanation'])): ?>
                                                <div class="solution-explanation-container">
                                                    <div class="solution-explanation-header">
                                                        <i class="fas fa-lightbulb"></i> Explication du développeur
                                                    </div>
                                                    <div class="solution-explanation">
                                                        <?= nl2br(htmlspecialchars($solution['explanation'])) ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($solution['solution_code'])): ?>
                                                <div class="solution-code-container">
                                                    <div class="solution-code-header">
                                                        <i class="fas fa-code"></i> Code proposé
                                                        <button class="copy-code-btn" onclick="copySolutionCode(this)" title="Copier le code">
                                                            <i class="fas fa-copy"></i>
                                                        </button>
                                                    </div>
                                                    <pre class="solution-code"><code><?= htmlspecialchars($solution['solution_code']) ?></code></pre>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($solution['status'] === 'pending'): ?>
                                                <div class="solution-actions">
                                                    <button class="btn btn-success" onclick="approveSolution(<?= $solution['id'] ?>)">
                                                        <i class="fas fa-check"></i> Approuver
                                                    </button>
                                                    <button class="btn btn-outline" onclick="rejectSolution(<?= $solution['id'] ?>)">
                                                        <i class="fas fa-times"></i> Rejeter
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Statistiques de la solution -->
                                    <div class="solution-stats">
                                        <?php if ($solution['rating']): ?>
                                            <span><i class="fas fa-star"></i> Note: <?= number_format($solution['rating'], 1) ?>/5</span>
                                        <?php endif; ?>
                                        <?php if ($solution['downloads']): ?>
                                            <span><i class="fas fa-download"></i> <?= $solution['downloads'] ?> téléchargements</span>
                                        <?php endif; ?>
                                        <?php if ($solution['has_payments']): ?>
                                            <span><i class="fas fa-shopping-cart"></i> Vendue</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=<?= $page - 1 ?>&status=<?= $filter_status ?>&type=<?= $filter_type ?>">
                        <i class="fas fa-chevron-left"></i> Précédent
                    </a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <?php if ($i == $page): ?>
                        <span><?= $i ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $i ?>&status=<?= $filter_status ?>&type=<?= $filter_type ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?= $page + 1 ?>&status=<?= $filter_status ?>&type=<?= $filter_type ?>">
                        Suivant <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php
// Scripts additionnels pour cette page
$additional_scripts = "
    // Fonction pour approuver une solution
    function approveSolution(solutionId) {
        if (!confirm('Êtes-vous sûr de vouloir approuver cette solution ?')) {
            return;
        }
        
        const btn = event.target;
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<span class=\"loading-spinner\"></span> Approbation...';
        
        fetch('manage_solution_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                solution_id: solutionId,
                action: 'approve'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Solution approuvée avec succès!', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                throw new Error(data.message || 'Erreur lors de l\\'approbation');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showNotification('Erreur: ' + error.message, 'error');
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }
    
    // Fonction pour rejeter une solution
    function rejectSolution(solutionId) {
        const reason = prompt('Raison du rejet (optionnel):');
        if (reason === null) return; // Utilisateur a annulé
        
        const btn = event.target;
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<span class=\"loading-spinner\"></span> Rejet...';
        
        fetch('manage_solution_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                solution_id: solutionId,
                action: 'reject',
                reason: reason
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Solution rejetée', 'info');
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                throw new Error(data.message || 'Erreur lors du rejet');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showNotification('Erreur: ' + error.message, 'error');
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    }
    
    // Fonction pour copier le code d'une solution
    function copySolutionCode(button) {
        const codeElement = button.closest('.solution-code-container').querySelector('code');
        const code = codeElement.textContent;
        
        navigator.clipboard.writeText(code).then(() => {
            const originalIcon = button.innerHTML;
            button.innerHTML = '<i class=\"fas fa-check\"></i>';
            button.style.background = '#27ae60';
            
            setTimeout(() => {
                button.innerHTML = originalIcon;
                button.style.background = '#3498db';
            }, 2000);
            
            showNotification('Code copié dans le presse-papiers!', 'success');
        }).catch(err => {
            console.error('Erreur lors de la copie:', err);
            showNotification('Erreur lors de la copie', 'error');
        });
    }
    
    // Fonction pour afficher des notifications
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = 'payment-success-notification ' + type;
        
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            info: 'fas fa-info-circle',
            warning: 'fas fa-exclamation-triangle'
        };
        
        const colors = {
            success: 'linear-gradient(135deg, #27ae60, #2ecc71)',
            error: 'linear-gradient(135deg, #e74c3c, #c0392b)',
            info: 'linear-gradient(135deg, #3498db, #2980b9)',
            warning: 'linear-gradient(135deg, #f39c12, #e67e22)'
        };
        
        notification.style.background = colors[type] || colors.info;
        notification.innerHTML = `
            <div style=\"display: flex; align-items: center; gap: 10px;\">
                <i class=\"\${icons[type] || icons.info}\"></i>
                <span>\${message}</span>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Animation de sortie
        setTimeout(() => {
            notification.style.opacity = '0';
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 4000);
    }
    
    // Animation d'entrée pour les cartes
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.problem-card');
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
            const value = card.querySelector('.stat-value');
            const finalValue = parseInt(value.textContent);
            
            if (!isNaN(finalValue)) {
                let currentValue = 0;
                const increment = finalValue / 30;
                
                const timer = setInterval(() => {
                    currentValue += increment;
                    if (currentValue >= finalValue) {
                        value.textContent = finalValue;
                        clearInterval(timer);
                    } else {
                        value.textContent = Math.floor(currentValue);
                    }
                }, 50);
            }
        });
    });
    
    // Gestion des erreurs de chargement d'images
    document.addEventListener('DOMContentLoaded', function() {
        const avatars = document.querySelectorAll('.developer-avatar');
        avatars.forEach(avatar => {
            avatar.addEventListener('error', function() {
                this.src = 'assets/default-avatar.png';
            });
        });
    });
    
    // Fonction pour exporter les données
    function exportData() {
        const data = {
            problems: [],
            solutions: [],
            stats: {
                total_problems: " . $stats['total_problems'] . ",
                total_solutions: " . $stats['total_solutions'] . ",
                pending_solutions: " . $stats['pending_solutions'] . ",
                approved_solutions: " . $stats['approved_solutions'] . ",
                rejected_solutions: " . $stats['rejected_solutions'] . ",
                total_earnings: " . $stats['total_earnings'] . "
            }
        };
        
        const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'user_feedback_data_' + new Date().toISOString().split('T')[0] + '.json';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
        showNotification('Données exportées avec succès!', 'success');
    }
    
    // Fonction pour actualiser les données
    function refreshData() {
        showNotification('Actualisation des données...', 'info');
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    }
    
    // Raccourcis clavier
    document.addEventListener('keydown', function(e) {
        if (e.ctrlKey || e.metaKey) {
            switch(e.key) {
                case 'r':
                    e.preventDefault();
                    refreshData();
                    break;
                case 'e':
                    e.preventDefault();
                    exportData();
                    break;
            }
        }
    });
    
    // Vérification périodique des nouvelles solutions
    let lastCheck = Date.now();
    setInterval(() => {
        fetch('check_new_solutions.php?since=' + lastCheck)
            .then(response => response.json())
            .then(data => {
                if (data.new_solutions > 0) {
                    showNotification(`\${data.new_solutions} nouvelle(s) solution(s) reçue(s)!`, 'info');
                    
                    // Ajouter un badge de notification
                    const title = document.title;
                    if (!title.startsWith('(')) {
                        document.title = `(\${data.new_solutions}) \${title}`;
                    }
                }
                lastCheck = Date.now();
            })
            .catch(error => {
                console.error('Erreur lors de la vérification:', error);
            });
    }, 60000); // Vérifier toutes les minutes
    
    // Nettoyer le titre quand la page reprend le focus
    window.addEventListener('focus', function() {
        const title = document.title;
        if (title.startsWith('(')) {
            document.title = title.replace(/^\(\d+\)\s/, '');
        }
    });
";

// Inclure le pied de page
include 'footer.php';
?>
