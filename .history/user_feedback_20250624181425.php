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

    .stats-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        text-align: center;
        border-left: 4px solid #3498db;
    }

    .stat-value {
        font-size: 2em;
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 5px;
    }

    .stat-label {
        color: #7f8c8d;
        font-size: 0.9em;
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
$user_stats = [
    'total_earnings' => 0,
    'pending_solutions' => 0,
    'accepted_solutions' => 0,
    'rejected_solutions' => 0
];

try {
    $pdo = connect();
    
    if (!$pdo) {
        throw new Exception("Erreur de connexion à la base de données");
    }
    
    // Récupérer les statistiques de l'utilisateur
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(CASE WHEN s.status = 'pending' THEN 1 END) as pending_solutions,
            COUNT(CASE WHEN s.status = 'accepted' THEN 1 END) as accepted_solutions,
            COUNT(CASE WHEN s.status = 'rejected' THEN 1 END) as rejected_solutions,
            COALESCE(SUM(CASE WHEN s.status = 'accepted' THEN pr.amount ELSE 0 END), 0) as total_earnings
        FROM solutions s
        JOIN problems p ON s.problem_id = p.problem_id
        LEFT JOIN prices pr ON s.price_id = pr.price_id
        WHERE p.user_id = ? AND s.user_id != ?
    ");
    
    $stmt->execute([$user_id, $user_id]);
    $stats_result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($stats_result) {
        $user_stats = [
            'total_earnings' => floatval($stats_result['total_earnings'] ?? 0),
            'pending_solutions' => intval($stats_result['pending_solutions'] ?? 0),
            'accepted_solutions' => intval($stats_result['accepted_solutions'] ?? 0),
            'rejected_solutions' => intval($stats_result['rejected_solutions'] ?? 0)
        ];
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
                    $_SESSION['success_message'] = "La solution a été acceptée avec succès. Vous serez redirigé vers la page de paiement.";
                    
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
                    $_SESSION['error_message'] = "Une erreur est survenue lors de l'acceptation de la solution.";
                }
            } else {
                $_SESSION['error_message'] = "Solution introuvable ou vous n'êtes pas autorisé à l'accepter.";
            }
        } catch (Exception $e) {
            error_log("Erreur lors de l'acceptation de la solution: " . $e->getMessage());
            $_SESSION['error_message'] = "Une erreur est survenue lors de l'acceptation de la solution.";
        }
    } else {
        $_SESSION['error_message'] = "ID de solution invalide.";
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
                    $_SESSION['success_message'] = "La solution a été rejetée avec succès.";
                    
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
                    $_SESSION['error_message'] = "Une erreur est survenue lors du rejet de la solution.";
                }
            } else {
                $_SESSION['error_message'] = "Solution introuvable ou vous n'êtes pas autorisé à la rejeter.";
            }
        } catch (Exception $e) {
            error_log("Erreur lors du rejet de la solution: " . $e->getMessage());
            $_SESSION['error_message'] = "Une erreur est survenue lors du rejet de la solution.";
        }
    } else {
        $_SESSION['error_message'] = "ID de solution invalide.";
    }
}
?>

<h1><i class="fas fa-bell"></i> Notifications des Solutions</h1>

<!-- Statistiques -->
<div class="stats-container">
    <div class="stat-card">
        <div class="stat-value"><?php echo number_format($user_stats['total_earnings'], 2); ?> €</div>
        <div class="stat-label">Gains Totaux</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $user_stats['pending_solutions']; ?></div>
        <div class="stat-label">Solutions en Attente</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $user_stats['accepted_solutions']; ?></div>
        <div class="stat-label">Solutions Acceptées</div>
    </div>
    <div class="stat-card">
        <div class="stat-value"><?php echo $user_stats['rejected_solutions']; ?></div>
        <div class="stat-label">Solutions Rejetées</div>
    </div>
</div>

<h2><i class="fas fa-inbox"></i> Solutions reçues pour vos problèmes</h2>

<?php if (empty($received_solutions)): ?>
    <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <h3>Aucune solution reçue</h3>
        <p>Vous n'avez pas encore reçu de solutions pour vos problèmes publiés.</p>
        <a href="expublier.php" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> Publier un problème
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
                <h4><i class="fas fa-code"></i> Code de la solution</h4>
                <pre><?php echo htmlspecialchars($solution['solution_code']); ?></pre>
            </div>
            
            <?php if (!empty($solution['explanation'])): ?>
                <div class="solution-explanation">
                    <h4><i class="fas fa-comment-alt"></i> Explication du développeur</h4>
                    <?php echo nl2br(htmlspecialchars($solution['explanation'])); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($solution['feedback']) && $solution['status'] !== 'pending'): ?>
                <div class="solution-feedback">
                    <h4><i class="fas fa-reply"></i> Votre feedback</h4>
                    <?php echo nl2br(htmlspecialchars($solution['feedback'])); ?>
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
                            <button type="submit" name="reject_solution" value="1" class="btn btn-danger">
                                <i class="fas fa-times"></i> Rejeter
                            </button>
                        </form>
                    </div>
                <?php elseif ($solution['status'] === 'accepted'): ?>
                    <a href="payment.php?solution_id=<?php echo $solution['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-credit-card"></i> Procéder au paiement
                    </a>
                <?php elseif ($solution['status'] === 'rejected'): ?>
                    <span class="btn btn-outline" style="opacity: 0.6; cursor: not-allowed;">
                        <i class="fas fa-times-circle"></i> Solution rejetée
                    </span>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php
// Additional scripts
$additional_scripts = "
    // Animation pour les boutons
    document.querySelectorAll('.btn').forEach(button => {
        button.addEventListener('mouseenter', () => {
            if (!button.disabled && !button.style.cursor.includes('not-allowed')) {
                button.style.transform = 'translateY(-2px)';
                button.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
            }
        });
        
        button.addEventListener('mouseleave', () => {
            if (!button.disabled && !button.style.cursor.includes('not-allowed')) {
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
    
    // Animation d'apparition des cartes
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.solution-card, .stat-card');
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
    
    // Fonction pour copier le code d'une solution
    function copySolutionCode(button) {
        const codeElement = button.closest('.solution-card').querySelector('.solution-content pre');
        const code = codeElement.textContent;
        
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(code).then(() => {
                showToast('Code copié dans le presse-papiers!', 'success');
                
                // Animation du bouton
                const originalText = button.innerHTML;
                button.innerHTML = '<i class=\"fas fa-check\"></i> Copié!';
                button.style.backgroundColor = '#27ae60';
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.backgroundColor = '';
                }, 2000);
            }).catch(err => {
                console.error('Erreur lors de la copie:', err);
                showToast('Erreur lors de la copie', 'danger');
            });
        } else {
            // Fallback pour les navigateurs plus anciens
            const textArea = document.createElement('textarea');
            textArea.value = code;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            textArea.style.top = '-999999px';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            
            try {
                document.execCommand('copy');
                showToast('Code copié dans le presse-papiers!', 'success');
                
                // Animation du bouton
                const originalText = button.innerHTML;
                button.innerHTML = '<i class=\"fas fa-check\"></i> Copié!';
                button.style.backgroundColor = '#27ae60';
                
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.style.backgroundColor = '';
                }, 2000);
            } catch (err) {
                console.error('Erreur lors de la copie:', err);
                showToast('Erreur lors de la copie', 'danger');
            }
            
            document.body.removeChild(textArea);
        }
    }
    
    // Ajouter des boutons de copie aux codes
    document.addEventListener('DOMContentLoaded', function() {
        const codeBlocks = document.querySelectorAll('.solution-content');
        codeBlocks.forEach(block => {
            const copyButton = document.createElement('button');
            copyButton.className = 'btn btn-outline';
            copyButton.style.cssText = `
                position: absolute;
                top: 10px;
                right: 10px;
                padding: 5px 10px;
                font-size: 12px;
                z-index: 10;
            `;
            copyButton.innerHTML = '<i class=\"fas fa-copy\"></i> Copier';
            copyButton.onclick = () => copySolutionCode(copyButton);
            
            block.style.position = 'relative';
            block.appendChild(copyButton);
        });
    });
    
    // Fonction pour filtrer les solutions par statut
    function filterSolutions(status) {
        const cards = document.querySelectorAll('.solution-card');
        const filterButtons = document.querySelectorAll('.filter-btn');
        
        // Mettre à jour les boutons de filtre
        filterButtons.forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.filter === status) {
                btn.classList.add('active');
            }
        });
        
        // Filtrer les cartes
        cards.forEach(card => {
            const cardStatus = card.querySelector('.solution-status').textContent.toLowerCase().trim();
            
            if (status === 'all' || cardStatus.includes(status)) {
                card.style.display = 'block';
                card.style.animation = 'fadeIn 0.3s ease';
            } else {
                card.style.display = 'none';
            }
        });
        
        // Mettre à jour le compteur
        const visibleCards = document.querySelectorAll('.solution-card[style*=\"display: block\"], .solution-card:not([style*=\"display: none\"])').length;
        const counter = document.querySelector('.solutions-counter');
        if (counter) {
            counter.textContent = `${visibleCards} solution(s) affichée(s)`;
        }
    }
    
    // Fonction pour trier les solutions
    function sortSolutions(criteria) {
        const container = document.querySelector('.solutions-container') || document.body;
        const cards = Array.from(document.querySelectorAll('.solution-card'));
        
        cards.sort((a, b) => {
            switch (criteria) {
                case 'date':
                    const dateA = new Date(a.querySelector('.solution-meta span:nth-child(2)').textContent.replace('Le ', ''));
                    const dateB = new Date(b.querySelector('.solution-meta span:nth-child(2)').textContent.replace('Le ', ''));
                    return dateB - dateA;
                    
                case 'price':
                    const priceA = parseFloat(a.querySelector('.solution-price').textContent.replace(/[^0-9.]/g, ''));
                    const priceB = parseFloat(b.querySelector('.solution-price').textContent.replace(/[^0-9.]/g, ''));
                    return priceB - priceA;
                    
                case 'status':
                    const statusA = a.querySelector('.solution-status').textContent.toLowerCase();
                    const statusB = b.querySelector('.solution-status').textContent.toLowerCase();
                    const statusOrder = { 'en attente': 0, 'acceptée': 1, 'rejetée': 2 };
                    return (statusOrder[statusA] || 3) - (statusOrder[statusB] || 3);
                    
                default:
                    return 0;
            }
        });
        
        // Réorganiser les cartes dans le DOM
        cards.forEach(card => {
            container.appendChild(card);
        });
        
        // Animation de réorganisation
        cards.forEach((card, index) => {
            card.style.animation = `slideInRight 0.3s ease ${index * 0.05}s both`;
        });
    }
    
    // Fonction pour exporter les données
    function exportSolutions(format) {
        const solutions = Array.from(document.querySelectorAll('.solution-card')).map(card => {
            return {
                title: card.querySelector('.solution-title').textContent.trim(),
                author: card.querySelector('.solution-meta span:first-child').textContent.replace('Par ', '').trim(),
                date: card.querySelector('.solution-meta span:nth-child(2)').textContent.replace('Le ', '').trim(),
                status: card.querySelector('.solution-status').textContent.trim(),
                price: card.querySelector('.solution-price').textContent.trim(),
                code: card.querySelector('.solution-content pre').textContent.trim(),
                explanation: card.querySelector('.solution-explanation') ? 
                    card.querySelector('.solution-explanation').textContent.replace('Explication du développeur', '').trim() : ''
            };
        });
        
        if (format === 'json') {
            const dataStr = JSON.stringify(solutions, null, 2);
            const dataBlob = new Blob([dataStr], {type: 'application/json'});
            const url = URL.createObjectURL(dataBlob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'solutions_' + new Date().toISOString().split('T')[0] + '.json';
            link.click();
            URL.revokeObjectURL(url);
        } else if (format === 'csv') {
            const headers = ['Titre', 'Auteur', 'Date', 'Statut', 'Prix', 'Code', 'Explication'];
            const csvContent = [
                headers.join(','),
                ...solutions.map(sol => [
                    `"${sol.title.replace(/"/g, '""')}"`,
                    `"${sol.author.replace(/"/g, '""')}"`,
                    `"${sol.date.replace(/"/g, '""')}"`,
                    `"${sol.status.replace(/"/g, '""')}"`,
                    `"${sol.price.replace(/"/g, '""')}"`,
                    `"${sol.code.replace(/"/g, '""')}"`,
                    `"${sol.explanation.replace(/"/g, '""')}"""
                ].join(','))
            ].join('\n'); // Correction ici : '\n' au lieu de '\\n'
            
            const dataBlob = new Blob([csvContent], {type: 'text/csv;charset=utf-8;'});
            const url = URL.createObjectURL(dataBlob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'solutions_' + new Date().toISOString().split('T')[0] + '.csv';
            link.click();
            URL.revokeObjectURL(url);
        }
        
        showToast(`Solutions exportées en ${format.toUpperCase()}!`, 'success');
    }
    
    // Fonction pour afficher les statistiques détaillées
    function showDetailedStats() {
        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 10000;
        `;
        
        const modalContent = document.createElement('div');
        modalContent.className = 'modal-content';
        modalContent.style.cssText = `
            background: white;
            padding: 30px;
            border-radius: 12px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        `;
        
        modalContent.innerHTML = `
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h2 style="margin: 0; color: #2c3e50;">
                    <i class="fas fa-chart-bar"></i> Statistiques Détaillées
                </h2>
                <button onclick="this.closest('.modal-overlay').remove()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                    <div style="font-size: 1.5em; font-weight: bold; color: #28a745;"><?php echo $user_stats['total_earnings']; ?> €</div>
                    <div style="color: #666; font-size: 0.9em;">Gains Totaux</div>
                </div>
                <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                    <div style="font-size: 1.5em; font-weight: bold; color: #ffc107;"><?php echo $user_stats['pending_solutions']; ?></div>
                    <div style="color: #666; font-size: 0.9em;">En Attente</div>
                </div>
                <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                    <div style="font-size: 1.5em; font-weight: bold; color: #28a745;"><?php echo $user_stats['accepted_solutions']; ?></div>
                    <div style="color: #666; font-size: 0.9em;">Acceptées</div>
                </div>
                <div style="text-align: center; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                    <div style="font-size: 1.5em; font-weight: bold; color: #dc3545;"><?php echo $user_stats['rejected_solutions']; ?></div>
                    <div style="color: #666; font-size: 0.9em;">Rejetées</div>
                </div>
            </div>
            
            <div style="margin-bottom: 20px;">
                <h3 style="color: #2c3e50; margin-bottom: 10px;">
                    <i class="fas fa-percentage"></i> Taux de Réussite
                </h3>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                    <?php 
                    $total_evaluated = $user_stats['accepted_solutions'] + $user_stats['rejected_solutions'];
                    $success_rate = $total_evaluated > 0 ? round(($user_stats['accepted_solutions'] / $total_evaluated) * 100, 1) : 0;
                    ?>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>Taux d'acceptation:</span>
                        <strong style="color: #28a745;"><?php echo $success_rate; ?>%</strong>
                    </div>
                    <div style="background: #e9ecef; height: 10px; border-radius: 5px; overflow: hidden;">
                        <div style="background: #28a745; height: 100%; width: <?php echo $success_rate; ?>%; transition: width 0.3s ease;"></div>
                    </div>
                </div>
            </div>
            
            <div style="margin-bottom: 20px;">
                <h3 style="color: #2c3e50; margin-bottom: 10px;">
                    <i class="fas fa-euro-sign"></i> Revenus par Mois
                </h3>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px;">
                    <p style="margin: 0; color: #666;">Gain moyen par solution acceptée: 
                        <strong style="color: #28a745;">
                            <?php echo $user_stats['accepted_solutions'] > 0 ? number_format($user_stats['total_earnings'] / $user_stats['accepted_solutions'], 2) : '0.00'; ?> €
                        </strong>
                    </p>
                </div>
            </div>
            
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button onclick="exportSolutions('json')" class="btn btn-outline">
                    <i class="fas fa-download"></i> Exporter JSON
                </button>
                <button onclick="exportSolutions('csv')" class="btn btn-outline">
                    <i class="fas fa-file-csv"></i> Exporter CSV
                </button>
                <button onclick="this.closest('.modal-overlay').remove()" class="btn btn-primary">
                    <i class="fas fa-times"></i> Fermer
                </button>
            </div>
        `;
        
        modal.appendChild(modalContent);
        document.body.appendChild(modal);
        
        // Animation d'apparition
        modal.style.opacity = '0';
        setTimeout(() => {
            modal.style.transition = 'opacity 0.3s ease';
            modal.style.opacity = '1';
        }, 10);
        
        // Fermer en cliquant sur l'overlay
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.remove();
            }
        });
    }
    
    // Ajouter des boutons d'action dans l'en-tête
    document.addEventListener('DOMContentLoaded', function() {
        const header = document.querySelector('h2');
        if (header && header.textContent.includes('Solutions reçues')) {
            const actionBar = document.createElement('div');
            actionBar.style.cssText = `
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin: 20px 0;
                padding: 15px;
                background: white;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            `;
            
            actionBar.innerHTML = `
                <div style="display: flex; gap: 10px; align-items: center;">
                    <span style="color: #666; font-weight: 500;">Filtrer par:</span>
                    <button class="btn btn-outline filter-btn active" data-filter="all" onclick="filterSolutions('all')">
                        <i class="fas fa-list"></i> Toutes
                    </button>
                    <button class="btn btn-outline filter-btn" data-filter="attente" onclick="filterSolutions('attente')">
                        <i class="fas fa-clock"></i> En attente
                    </button>
                    <button class="btn btn-outline filter-btn" data-filter="acceptée" onclick="filterSolutions('acceptée')">
                        <i class="fas fa-check"></i> Acceptées
                    </button>
                    <button class="btn btn-outline filter-btn" data-filter="rejetée" onclick="filterSolutions('rejetée')">
                        <i class="fas fa-times"></i> Rejetées
                    </button>
                </div>
                
                <div style="display: flex; gap: 10px; align-items: center;">
                    <span style="color: #666; font-weight: 500;">Trier par:</span>
                    <select onchange="sortSolutions(this.value)" style="padding: 5px 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value="date">Date</option>
                        <option value="price">Prix</option>
                        <option value="status">Statut</option>
                    </select>
                    
                    <button class="btn btn-primary" onclick="showDetailedStats()">
                        <i class="fas fa-chart-bar"></i> Statistiques
                    </button>
                </div>
            `;
            
            header.parentNode.insertBefore(actionBar, header.nextSibling);
            
            // Ajouter un compteur de solutions
            const counter = document.createElement('div');
            counter.className = 'solutions-counter';
            counter.style.cssText = `
                text-align: center;
                margin: 10px 0;
                color: #666;
                font-style: italic;
            `;
            counter.textContent = `${document.querySelectorAll('.solution-card').length} solution(s) au total`;
            
            actionBar.parentNode.insertBefore(counter, actionBar.nextSibling);
        }
    });
    
    // Fonction pour actualiser les données
    function refreshData() {
        const refreshBtn = document.querySelector('.refresh-btn');
        if (refreshBtn) {
            refreshBtn.innerHTML = '<i class=\"fas fa-spinner fa-spin\"></i> Actualisation...';
            refreshBtn.disabled = true;
        }
        
        // Simuler un délai d'actualisation
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    }
    
    // Ajouter un bouton d'actualisation
    document.addEventListener('DOMContentLoaded', function() {
        const statsContainer = document.querySelector('.stats-container');
        if (statsContainer) {
            const refreshBtn = document.createElement('button');
            refreshBtn.className = 'btn btn-outline refresh-btn';
            refreshBtn.style.cssText = `
                position: absolute;
                top: 20px;
                right: 20px;
                z-index: 100;
            `;
            refreshBtn.innerHTML = '<i class=\"fas fa-sync-alt\"></i> Actualiser';
            refreshBtn.onclick = refreshData;
            
            document.body.style.position = 'relative';
            document.body.appendChild(refreshBtn);
        }
    });
    
    // Gestion des notifications en temps réel (simulation)
    function checkForNewSolutions() {
        // Cette fonction pourrait être connectée à un WebSocket ou faire des requêtes AJAX périodiques
        // Pour l'instant, c'est juste une simulation
        
        const currentCount = <?php echo count($received_solutions); ?>;
        
        // Simuler une vérification toutes les 30 secondes
        setInterval(() => {
            // Ici, vous pourriez faire une requête AJAX pour vérifier les nouvelles solutions
            console.log('Vérification des nouvelles solutions...');
            
            // Exemple de notification (à remplacer par une vraie vérification)
            if (Math.random() < 0.1) { // 10% de chance de nouvelle notification
                showToast('Nouvelle solution reçue!', 'info');
                
                // Mettre à jour l'icône de notification
                const notificationBell = document.querySelector('.notification-bell');
                if (notificationBell) {
                    notificationBell.style.animation = 'shake 0.5s ease-in-out';
                    setTimeout(() => {
                        notificationBell.style.animation = '';
                    }, 500);
                }
            }
        }, 30000); // 30 secondes
    }
    
    // Démarrer la vérification des nouvelles solutions
    checkForNewSolutions();
    
    // Animation de secousse pour les notifications
    const shakeKeyframes = `
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-2px); }
            20%, 40%, 60%, 80% { transform: translateX(2px); }
        }
    `;
    
    const style = document.createElement('style');
    style.textContent = shakeKeyframes;
    document.head.appendChild(style);
";

// Include footer
include 'footer.php';
?>
