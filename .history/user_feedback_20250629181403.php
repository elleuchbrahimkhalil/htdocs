<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user = getCurrentUser();
$page_title = "Mes Notifications et Achats";

// CSS pour la nouvelle section premium
$additional_css = "
    .feedback-container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 20px;
    }

    .tabs-container {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }

    .tabs-header {
        display: flex;
        background: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
    }

    .tab-button {
        flex: 1;
        padding: 15px 20px;
        border: none;
        background: transparent;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .tab-button.active {
        background: #667eea;
        color: white;
    }

    .tab-button:hover:not(.active) {
        background: #e9ecef;
    }

    .tab-content {
        padding: 25px;
        display: none;
    }

    .tab-content.active {
        display: block;
    }

    .premium-section {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        padding: 30px;
        border-radius: 12px;
        margin-bottom: 30px;
        text-align: center;
    }

    .premium-section h2 {
        margin: 0 0 15px 0;
        font-size: 2em;
    }

    .premium-section p {
        margin: 0 0 20px 0;
        opacity: 0.9;
        font-size: 1.1em;
    }

    .premium-btn {
        background: rgba(255,255,255,0.2);
        color: white;
        padding: 12px 25px;
        border: 2px solid rgba(255,255,255,0.3);
        border-radius: 25px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .premium-btn:hover {
        background: rgba(255,255,255,0.3);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }

    .purchase-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        border-left: 4px solid #27ae60;
    }

    .purchase-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 15px;
    }

    .purchase-title {
        color: #2c3e50;
        margin: 0 0 5px 0;
        font-size: 1.3em;
    }

    .purchase-date {
        color: #7f8c8d;
        font-size: 0.9em;
    }

    .purchase-price {
        background: #27ae60;
        color: white;
        padding: 8px 15px;
        border-radius: 20px;
        font-weight: bold;
    }

    .purchase-actions {
        display: flex;
        gap: 10px;
        margin-top: 15px;
        flex-wrap: wrap;
    }

    .action-btn {
        padding: 8px 16px;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.3s;
        font-size: 0.9em;
    }

    .btn-view {
        background: #3498db;
        color: white;
    }

    .btn-download {
        background: #27ae60;
        color: white;
    }

    .btn-support {
        background: #f39c12;
        color: white;
    }

    .action-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 3px 8px rgba(0,0,0,0.2);
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 12px;
        text-align: center;
        box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    }

    .stat-value {
        font-size: 2.5em;
        font-weight: bold;
        color: #3498db;
        margin-bottom: 5px;
    }

    .stat-label {
        color: #7f8c8d;
        font-size: 0.9em;
        text-transform: uppercase;
        letter-spacing: 0.5px;
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

    @media (max-width: 768px) {
        .tabs-header {
            flex-direction: column;
        }
        
        .purchase-header {
            flex-direction: column;
            gap: 10px;
        }
        
        .purchase-actions {
            flex-direction: column;
        }
        
        .action-btn {
            width: 100%;
            justify-content: center;
        }
    }
";

include 'header.php';
?>

<div class="feedback-container">
    <!-- Section Premium en haut -->
    <div class="premium-section">
        <h2><i class="fas fa-gem"></i> Solutions Premium</h2>
        <p>Découvrez des solutions de qualité professionnelle développées par notre communauté d'experts</p>
        <a href="premium_solutions.php" class="premium-btn">
            <i class="fas fa-shopping-cart"></i> Explorer les solutions premium
        </a>
    </div>

    <!-- Statistiques rapides -->
    <div class="stats-grid">
        <?php
        try {
            $conn = connect();
            
            // Statistiques des achats
            $stmt = $conn->prepare("
                SELECT 
                    COUNT(*) as total_purchases,
                    COALESCE(SUM(amount), 0) as total_spent
                FROM payments 
                WHERE payer_id = ? AND status = 'completed'
            ");
            $stmt->execute([$user['id']]);
            $purchase_stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Statistiques des ventes
            $stmt = $conn->prepare("
                SELECT 
                    COUNT(*) as total_sales,
                    COALESCE(SUM(amount), 0) as total_earned
                FROM payments p
                JOIN solutions s ON p.solution_id = s.id
                WHERE s.user_id = ? AND p.status = 'completed'
            ");
            $stmt->execute([$user['id']]);
            $sales_stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $purchase_stats = ['total_purchases' => 0, 'total_spent' => 0];
            $sales_stats = ['total_sales' => 0, 'total_earned' => 0];
        }
        ?>
        
        <div class="stat-card">
            <div class="stat-value"><?= $purchase_stats['total_purchases'] ?></div>
            <div class="stat-label">Solutions achetées</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value"><?= number_format($purchase_stats['total_spent'], 2) ?>€</div>
            <div class="stat-label">Total dépensé</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value"><?= $sales_stats['total_sales'] ?></div>
            <div class="stat-label">Solutions vendues</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-value"><?= number_format($sales_stats['total_earned'], 2) ?>€</div>
            <div class="stat-label">Total gagné</div>
        </div>
    </div>

    <!-- Onglets -->
    <div class="tabs-container">
        <div class="tabs-header">
            <button class="tab-button active" onclick="showTab('purchases')">
                <i class="fas fa-shopping-bag"></i> Mes Achats
            </button>
            <button class="tab-button" onclick="showTab('sales')">
                <i class="fas fa-chart-line"></i> Mes Ventes
            </button>
            <button class="tab-button" onclick="showTab('notifications')">
                <i class="fas fa-bell"></i> Notifications
            </button>
        </div>

        <!-- Onglet Mes Achats -->
        <div id="purchases" class="tab-content active">
            <h3><i class="fas fa-shopping-bag"></i> Mes Solutions Achetées</h3>
            
            <?php
            try {
                // Récupérer les achats de l'utilisateur
                $stmt = $conn->prepare("
                    SELECT p.*, s.solution_code, s.explanation,
                           pr.title as problem_title, pr.description as problem_description,
                           pr.difficulty, pr.language,
                           u.username as seller_username, u.name as seller_name
                    FROM payments p
                    JOIN solutions s ON p.solution_id = s.id
                    JOIN problems pr ON s.problem_id = pr.problem_id
                    JOIN users u ON s.user_id = u.id
                    WHERE p.payer_id = ? AND p.status = 'completed'
                    ORDER BY p.completed_at DESC
                ");
                $stmt->execute([$user['id']]);
                $purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($purchases)) {
                    echo '<div class="empty-state">
                            <i class="fas fa-shopping-cart"></i>
                            <h4>Aucun achat effectué</h4>
                            <p>Vous n\'avez pas encore acheté de solutions premium.</p>
                            <a href="premium_solutions.php" class="premium-btn" style="color: #667eea; border-color: #667eea;">
                                <i class="fas fa-gem"></i> Découvrir les solutions
                            </a>
                          </div>';
                } else {
                    foreach ($purchases as $purchase) {
                        ?>
                        <div class="purchase-card">
                            <div class="purchase-header">
                                <div>
                                    <h4 class="purchase-title"><?= htmlspecialchars($purchase['problem_title']) ?></h4>
                                    <div class="purchase-date">
                                        Acheté le <?= date('d/m/Y à H:i', strtotime($purchase['completed_at'])) ?>
                                        • Vendeur: <?= htmlspecialchars($purchase['seller_name']) ?>
                                    </div>
                                </div>
                                <div class="purchase-price">
                                    <?= number_format($purchase['amount'], 2) ?> €
                                </div>
                            </div>
                            
                            <div style="margin: 15px 0;">
                                <span class="difficulty <?= $purchase['difficulty'] ?>" style="padding: 4px 12px; border-radius: 15px; font-size: 12px; font-weight: 600;">
                                    <?php
                                    $difficulty_labels = ['easy' => 'Facile', 'medium' => 'Moyen', 'hard' => 'Difficile'];
                                    echo $difficulty_labels[$purchase['difficulty']] ?? ucfirst($purchase['difficulty']);
                                    ?>
                                </span>
                                <span style="margin-left: 10px; color: #7f8c8d;">
                                    <i class="fas fa-code"></i> <?= htmlspecialchars(ucfirst($purchase['language'])) ?>
                                </span>
                            </div>
                            
                            <div class="purchase-actions">
                                <button onclick="viewSolution(<?= $purchase['solution_id'] ?>)" class="action-btn btn-view">
                                    <i class="fas fa-eye"></i> Voir la solution
                                </button>
                                
                                <button onclick="downloadSolution(<?= $purchase['solution_id'] ?>, '<?= addslashes($purchase['problem_title']) ?>')" class="action-btn btn-download">
                                    <i class="fas fa-download"></i> Télécharger
                                </button>
                                
                                <a href="problem.php?id=<?= $purchase['problem_id'] ?>" class="action-btn btn-support">
                                    <i class="fas fa-link"></i> Voir le problème
                                </a>
                                <button onclick="copySolution(<?= $purchase['solution_id'] ?>)" class="action-btn" style="background: #9b59b6; color: white;">
                                    <i class="fas fa-copy"></i> Copier
                                </button>
                            </div>
                        </div>
                        <?php
                    }
                }
            } catch (Exception $e) {
                echo '<div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Erreur lors du chargement des achats : ' . htmlspecialchars($e->getMessage()) . '
                      </div>';
            }
            ?>
        </div>

        <!-- Onglet Mes Ventes -->
        <div id="sales" class="tab-content">
            <h3><i class="fas fa-chart-line"></i> Mes Solutions Vendues</h3>
            
            <?php
            try {
                // Récupérer les ventes de l'utilisateur
                $stmt = $conn->prepare("
                    SELECT p.*, pr.title as problem_title, pr.difficulty, pr.language,
                           buyer.username as buyer_username, buyer.name as buyer_name,
                           s.price as solution_price
                    FROM payments p
                    JOIN solutions s ON p.solution_id = s.id
                    JOIN problems pr ON s.problem_id = pr.problem_id
                    JOIN users buyer ON p.payer_id = buyer.id
                    WHERE s.user_id = ? AND p.status = 'completed'
                    ORDER BY p.completed_at DESC
                ");
                $stmt->execute([$user['id']]);
                $sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (empty($sales)) {
                    echo '<div class="empty-state">
                            <i class="fas fa-chart-line"></i>
                            <h4>Aucune vente effectuée</h4>
                            <p>Vous n\'avez pas encore vendu de solutions. Créez des solutions de qualité pour commencer à gagner de l\'argent !</p>
                            <a href="expublier.php" class="premium-btn" style="color: #667eea; border-color: #667eea;">
                                <i class="fas fa-plus"></i> Publier une solution
                            </a>
                          </div>';
                } else {
                    foreach ($sales as $sale) {
                        ?>
                        <div class="purchase-card" style="border-left-color: #f39c12;">
                            <div class="purchase-header">
                                <div>
                                    <h4 class="purchase-title"><?= htmlspecialchars($sale['problem_title']) ?></h4>
                                    <div class="purchase-date">
                                        Vendu le <?= date('d/m/Y à H:i', strtotime($sale['completed_at'])) ?>
                                        • Acheteur: <?= htmlspecialchars($sale['buyer_name']) ?>
                                    </div>
                                </div>
                                <div class="purchase-price" style="background: #f39c12;">
                                    +<?= number_format($sale['amount'], 2) ?> €
                                </div>
                            </div>
                            
                            <div style="margin: 15px 0;">
                                <span class="difficulty <?= $sale['difficulty'] ?>" style="padding: 4px 12px; border-radius: 15px; font-size: 12px; font-weight: 600;">
                                    <?php
                                    $difficulty_labels = ['easy' => 'Facile', 'medium' => 'Moyen', 'hard' => 'Difficile'];
                                    echo $difficulty_labels[$sale['difficulty']] ?? ucfirst($sale['difficulty']);
                                    ?>
                                </span>
                                <span style="margin-left: 10px; color: #7f8c8d;">
                                    <i class="fas fa-code"></i> <?= htmlspecialchars(ucfirst($sale['language'])) ?>
                                </span>
                                <span style="margin-left: 10px; color: #27ae60;">
                                    <i class="fas fa-check-circle"></i> Transaction ID: <?= htmlspecialchars($sale['transaction_id']) ?>
                                </span>
                            </div>
                        </div>
                        <?php
                    }
                }
            } catch (Exception $e) {
                echo '<div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> 
                        Erreur lors du chargement des ventes : ' . htmlspecialchars($e->getMessage()) . '
                      </div>';
            }
            ?>
        </div>

        <!-- Onglet Notifications (contenu existant) -->
        <div id="notifications" class="tab-content">
            <h3><i class="fas fa-bell"></i> Notifications des Problèmes</h3>
            
            <?php
            // Votre code existant pour les notifications des problèmes
            // ... (garder le code existant de user_feedback.php)
            ?>
            
            <div class="empty-state">
                <i class="fas fa-bell"></i>
                <h4>Aucune notification</h4>
                <p>Vos notifications de problèmes apparaîtront ici.</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal pour afficher les solutions -->
<div id="solutionModal" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 9999;">
    <div class="modal-content" style="background: white; margin: 50px auto; padding: 0; max-width: 800px; border-radius: 12px; max-height: 80vh; overflow: hidden;">
        <div class="modal-header" style="padding: 20px; background: #667eea; color: white; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0;"><i class="fas fa-code"></i> Solution Complète</h3>
            <button onclick="closeSolutionModal()" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <div class="modal-body" style="padding: 20px; overflow-y: auto; max-height: 60vh;">
            <div id="solutionContent"></div>
        </div>
    </div>
</div>

<?php
$additional_scripts = "
    // Gestion des onglets
    function showTab(tabName) {
        // Masquer tous les contenus d'onglets
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
        });
        
        // Désactiver tous les boutons d'onglets
        document.querySelectorAll('.tab-button').forEach(button => {
            button.classList.remove('active');
        });
        
        // Afficher le contenu de l'onglet sélectionné
        document.getElementById(tabName).classList.add('active');
        
        // Activer le bouton de l'onglet sélectionné
        event.target.classList.add('active');
    }

    // Fonction pour afficher une solution dans une modal
    function viewSolution(solutionId) {
        fetch('get_solution.php?solution_id=' + solutionId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let content = '<div class=\"solution-display\">';
                    content += '<div class=\"solution-code\" style=\"background: #2c3e50; color: #ecf0f1; padding: 20px; border-radius: 8px; margin: 15px 0;\">';
                    content += '<h4 style=\"color: #ecf0f1; margin-top: 0;\"><i class=\"fas fa-code\"></i> Code de la solution</h4>';
                    content += '<pre style=\"white-space: pre-wrap; font-family: monospace; line-height: 1.5;\">' + escapeHtml(data.solution.code) + '</pre>';
                    content += '</div>';
                    
                    if (data.solution.explanation) {
                        content += '<div class=\"solution-explanation\" style=\"background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #3498db; margin: 15px 0;\">';
                        content += '<h4 style=\"margin-top: 0;\"><i class=\"fas fa-lightbulb\"></i> Explication</h4>';
                        content += '<p style=\"line-height: 1.6;\">' + escapeHtml(data.solution.explanation).replace(/\\n/g, '<br>') + '</p>';
                        content += '</div>';
                    }
                    
                    content += '<div style=\"text-align: center; margin-top: 20px;\">';
                    content += '<button onclick=\"copyToClipboard(\\'' + escapeHtml(data.solution.code) + '\\')\" class=\"action-btn btn-download\" style=\"margin-right: 10px;\">';
                    content += '<i class=\"fas fa-copy\"></i> Copier le code';
                    content += '</button>';
                    content += '<button onclick=\"downloadSolutionContent(\\'' + escapeHtml(data.solution.code) + '\\', \\'' + escapeHtml(data.solution.title) + '\\')\" class=\"action-btn btn-view\">';
                    content += '<i class=\"fas fa-download\"></i> Télécharger';
                    content += '</button>';
                    content += '</div>';
                    content += '</div>';
                    
                    document.getElementById('solutionContent').innerHTML = content;
                    document.getElementById('solutionModal').style.display = 'block';
                } else {
                    showMessage('Erreur: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showMessage('Erreur lors du chargement de la solution', 'error');
            });
    }

    // Fonction pour fermer la modal
    function closeSolutionModal() {
        document.getElementById('solutionModal').style.display = 'none';
    }

    // Fonction pour télécharger une solution
    function downloadSolution(solutionId, problemTitle) {
        fetch('download_solution.php?solution_id=' + solutionId)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur de téléchargement');
                }
                return response.blob();
            })
            .then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'solution_' + problemTitle.replace(/[^a-zA-Z0-9]/g, '_') + '.txt';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
                showMessage('Solution téléchargée avec succès!', 'success');
            })
            .catch(error => {
                console.error('Erreur:', error);
                showMessage('Erreur lors du téléchargement', 'error');
            });
    }

    // Fonction pour copier une solution
    function copySolution(solutionId) {
        fetch('get_solution.php?solution_id=' + solutionId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    copyToClipboard(data.solution.code);
                } else {
                    showMessage('Erreur: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showMessage('Erreur lors de la copie', 'error');
            });
    }

    // Fonction pour copier du texte dans le presse-papiers
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(function() {
            showMessage('Code copié dans le presse-papiers!', 'success');
        }).catch(function(err) {
            console.error('Erreur lors de la copie:', err);
            showMessage('Erreur lors de la copie', 'error');
        });
    }

    // Fonction pour télécharger le contenu d'une solution
    function downloadSolutionContent(code, title) {
        const blob = new Blob([code], { type: 'text/plain' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'solution_' + title.replace(/[^a-zA-Z0-9]/g, '_') + '.txt';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        showMessage('Solution téléchargée!', 'success');
    }

    // Fonction pour échapper le HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
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
        messageDiv.style.maxWidth = '300px';
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
            default:
                messageDiv.style.backgroundColor = '#d1ecf1';
                messageDiv.style.color = '#0c5460';
                messageDiv.style.border = '1px solid #bee5eb';
                messageDiv.innerHTML = 'ℹ️ ' + message;
        }
        
        document.body.appendChild(messageDiv);
        
                   setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.parentNode.removeChild(messageDiv);
                }
            }, 300);
        }, 4000);
    }

    // Fermer la modal en cliquant à l'extérieur
    window.onclick = function(event) {
        const modal = document.getElementById('solutionModal');
        if (event.target === modal) {
            closeSolutionModal();
        }
    }

    // Animation d'entrée pour les cartes
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.purchase-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => {
                card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });

        // Animation pour les statistiques
        const statCards = document.querySelectorAll('.stat-card');
        statCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => {
                card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 50);
        });
    });

    // Gestion du clavier pour la modal
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeSolutionModal();
        }
    });
";

include 'footer.php';
?>
