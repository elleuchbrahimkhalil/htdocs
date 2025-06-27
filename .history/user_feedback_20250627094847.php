<?php
// Démarrer la session si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure les fichiers nécessaires
require_once 'verification.php';
require_once 'db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    $_SESSION['error_message'] = 'Vous devez être connecté pour accéder à cette page.';
    header('Location: exlogin.php');
    exit;
}

// Récupérer l'ID de l'utilisateur connecté
$user_id = $_SESSION['user_id'];

// Mode debug
$debug_mode = isset($_GET['debug']) && $_GET['debug'] == '1';

// TRAITEMENT POST AVANT TOUT OUTPUT HTML
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo = connect();
        
        if (!$pdo) {
            throw new Exception("Erreur de connexion à la base de données");
        }
        
        // Traitement de l'acceptation d'une solution
        if (isset($_POST['accept_solution'])) {
            $solution_id = isset($_POST['solution_id']) ? (int)$_POST['solution_id'] : 0;
            
            if ($solution_id <= 0) {
                throw new Exception("ID de solution invalide.");
            }
            
            // Vérifier que la solution existe et appartient à un problème de l'utilisateur
            $stmt = $pdo->prepare("
                SELECT s.*, p.user_id as problem_owner, p.title as problem_title,
                       pr.amount as price
                FROM solutions s
                INNER JOIN problems p ON s.problem_id = p.problem_id
                LEFT JOIN prices pr ON s.price_id = pr.price_id
                WHERE s.id = ? AND p.user_id = ?
            ");
            
            $stmt->execute([$solution_id, $user_id]);
            $solution = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$solution) {
                throw new Exception("Solution introuvable ou vous n'êtes pas autorisé à l'accepter.");
            }
            
            if ($solution['status'] !== 'pending') {
                throw new Exception("Cette solution a déjà été évaluée.");
            }
            
            // Mettre à jour le statut de la solution
            $stmt = $pdo->prepare("
                UPDATE solutions
                SET status = 'accepted', evaluated_at = GETDATE()
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$solution_id]);
            
            if ($result) {
                $_SESSION['success_message'] = "Solution acceptée avec succès. Redirection vers le paiement...";
                
                // REDIRECTION IMMÉDIATE VERS PAYMENT.PHP
                header("Location: payment.php?solution_id=" . $solution_id);
                exit;
            } else {
                throw new Exception("Erreur lors de l'acceptation de la solution.");
            }
        }
        
        // Traitement du rejet d'une solution
        if (isset($_POST['reject_solution'])) {
            $solution_id = isset($_POST['solution_id']) ? (int)$_POST['solution_id'] : 0;
            
            if ($solution_id <= 0) {
                throw new Exception("ID de solution invalide.");
            }
            
            // Vérifier que la solution existe et appartient à un problème de l'utilisateur
            $stmt = $pdo->prepare("
                SELECT s.*, p.user_id as problem_owner
                FROM solutions s
                INNER JOIN problems p ON s.problem_id = p.problem_id
                WHERE s.id = ? AND p.user_id = ?
            ");
            
            $stmt->execute([$solution_id, $user_id]);
            $solution = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$solution) {
                throw new Exception("Solution introuvable ou vous n'êtes pas autorisé à la rejeter.");
            }
            
            if ($solution['status'] !== 'pending') {
                throw new Exception("Cette solution a déjà été évaluée.");
            }
            
            // Mettre à jour le statut de la solution
            $stmt = $pdo->prepare("
                UPDATE solutions
                SET status = 'rejected', evaluated_at = GETDATE()
                WHERE id = ?
            ");
            
            $result = $stmt->execute([$solution_id]);
            
            if ($result) {
                $_SESSION['success_message'] = "Solution rejetée avec succès.";
                header("Location: user_feedback.php?rejected=1");
                exit;
            } else {
                throw new Exception("Erreur lors du rejet de la solution.");
            }
        }
        
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        error_log("Erreur dans user_feedback.php: " . $e->getMessage());
        header("Location: user_feedback.php?error=1");
        exit;
    }
}

// Récupération des données APRÈS le traitement POST
$received_solutions = [];
$error_message = '';
$success_message = '';

// Récupérer les messages de session
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

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
        INNER JOIN problems p ON s.problem_id = p.problem_id
        INNER JOIN users u ON s.user_id = u.id
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

// Configuration de la page
$page_title = "Notifications des Problèmes";

// CSS spécifique à cette page
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
        max-height: 200px;
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

    .solution-status.paid {
        background: #e8f5e8;
        color: #155724;
    }

    .solution-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .btn {
        padding: 8px 16px;
        border-radius: 6px;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 14px;
    }

    .btn-primary {
        background-color: #007bff;
        color: white;
    }

    .btn-primary:hover {
        background-color: #0056b3;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .btn-success {
        background-color: #28a745;
        color: white;
    }

    .btn-success:hover {
        background-color: #218838;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .btn-danger {
        background-color: #dc3545;
        color: white;
    }

    .btn-danger:hover {
        background-color: #c82333;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .btn-outline {
        background-color: transparent;
        color: #6c757d;
        border: 1px solid #6c757d;
    }

    .btn-outline:hover {
        background-color: #6c757d;
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
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
        font-family: monospace;
        font-size: 12px;
    }

    @media (max-width: 768px) {
        .solution-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
        
        .solution-footer {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .solution-actions {
            width: 100%;
            justify-content: flex-start;
        }
    }
";

// Include header
include 'header.php';
?>

<h1><i class="fas fa-bell"></i> Notifications des Solutions</h1>

<?php if ($debug_mode): ?>
    <div class="debug-info">
        <h4>🔍 Mode Debug Activé</h4>
        <div><strong>User ID:</strong> <?php echo $user_id; ?></div>
        <div><strong>Solutions trouvées:</strong> <?php echo count($received_solutions); ?></div>
        <div><strong>URL actuelle:</strong> <?php echo $_SERVER['REQUEST_URI']; ?></div>
        <div><strong>Méthode:</strong> <?php echo $_SERVER['REQUEST_METHOD']; ?></div>
        <?php if (!empty($_POST)): ?>
            <div><strong>POST Data:</strong> <?php echo htmlspecialchars(json_encode($_POST)); ?></div>
        <?php endif; ?>
        <div style="margin-top: 10px;">
            <a href="?">Désactiver le debug</a>
        </div>
    </div>
<?php endif; ?>

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

<h2><i class="fas fa-inbox"></i> Solutions reçues pour vos problèmes</h2>

<?php if (empty($received_solutions)): ?>
    <div class="empty-state">
        <i class="fas fa-inbox"></i>
        <h3>Aucune solution reçue</h3>
        <p>Vous n'avez pas encore reçu de solutions pour vos problèmes publiés.</p>
        <a href="exacueil.php" class="btn btn-primary">
            <i class="fas fa-home"></i> Retour à l'accueil
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
                        'rejected' => 'Rejetée',
                        'paid' => 'Payée'
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
                
                <div class="solution-actions">
                    <?php if ($solution['status'] === 'pending'): ?>
                        <!-- Boutons pour accepter/rejeter -->
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir accepter cette solution? Vous serez redirigé vers la page de paiement.');">
                            <input type="hidden" name="solution_id" value="<?php echo $solution['id']; ?>">
                            <button type="submit" name="accept_solution" class="btn btn-success">
                                <i class="fas fa-check"></i> Accepter (<?php echo number_format($solution['price'] ?? 0, 2); ?> €)
                            </button>
                        </form>
                        
                        <form method="POST" style="display: inline;" onsubmit="return confirm('Êtes-vous sûr de vouloir rejeter cette solution?');">
                            <input type="hidden" name="solution_id" value="<?php echo $solution['id']; ?>">
                            <button type="submit" name="reject_solution" class="btn btn-danger">
                                <i class="fas fa-times"></i> Rejeter
                            </button>
                        </form>
                        
                    <?php elseif ($solution['status'] === 'accepted'): ?>
                        <!-- Bouton pour aller au paiement -->
                        <a href="payment.php?solution_id=<?php echo $solution['id']; ?>" class="btn btn-primary">
                            <i class="fas fa-credit-card"></i> Procéder au paiement (<?php echo number_format($solution['price'] ?? 0, 2); ?> €)
                        </a>
                        
                    <?php elseif ($solution['status'] === 'paid'): ?>
                        <!-- Solution déjà payée -->
                        <span class="btn btn-success" style="cursor: default;">
                            <i class="fas fa-check-double"></i> Solution payée
                        </span>
                        
                    <?php elseif ($solution['status'] === 'rejected'): ?>
                        <!-- Solution rejetée -->
                        <span class="btn btn-danger" style="cursor: default; opacity: 0.7;">
                            <i class="fas fa-times-circle"></i> Solution rejetée
                        </span>
                    <?php endif; ?>
                    
                    <?php if ($debug_mode): ?>
                        <div style="margin-top: 10px; font-size: 11px; color: #666;">
                            <strong>Debug:</strong> ID: <?php echo $solution['id']; ?> | 
                            Status: <?php echo $solution['status']; ?> | 
                            Price: <?php echo $solution['price'] ?? 'NULL'; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php if (!$debug_mode): ?>
    <div style="margin-top: 30px; text-align: center;">
        <a href="?debug=1" class="btn btn-outline" style="font-size: 12px;">
            <i class="fas fa-bug"></i> Activer le mode debug
        </a>
    </div>
<?php endif; ?>

<?php
// Scripts additionnels
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
    
    // Gestion des formulaires avec animation
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            const button = form.querySelector('button[type=\"submit\"]');
            if (button) {
                // Animation de chargement
                const originalText = button.innerHTML;
                button.innerHTML = '<i class=\"fas fa-spinner fa-spin\"></i> Traitement...';
                button.disabled = true;
                
                // Si l'utilisateur annule la confirmation, restaurer le bouton
                setTimeout(() => {
                    if (!confirm) {
                        button.innerHTML = originalText;
                        button.disabled = false;
                    }
                }, 100);
            }
        });
    });
    
    // Fonction pour afficher des notifications
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: 600;
            z-index: 9999;
            max-width: 350px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateX(100%);
            transition: transform 0.3s ease;
        `;
        
        switch(type) {
            case 'success':
                notification.style.backgroundColor = '#28a745';
                notification.innerHTML = '<i class=\"fas fa-check-circle\"></i> ' + message;
                break;
            case 'error':
                notification.style.backgroundColor = '#dc3545';
                notification.innerHTML = '<i class=\"fas fa-exclamation-circle\"></i> ' + message;
                break;
            case 'warning':
                notification.style.backgroundColor = '#ffc107';
                notification.style.color = '#212529';
                notification.innerHTML = '<i class=\"fas fa-exclamation-triangle\"></i> ' + message;
                break;
            default:
                notification.style.backgroundColor = '#17a2b8';
                notification.innerHTML = '<i class=\"fas fa-info-circle\"></i> ' + message;
        }
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 5000);
    }
    
    // Vérifier les paramètres URL pour afficher des messages
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        
        if (urlParams.get('payment_success') === '1') {
            showNotification('Paiement effectué avec succès!', 'success');
        }
        
        if (urlParams.get('rejected') === '1') {
            showNotification('Solution rejetée avec succès.', 'info');
        }
        
        if (urlParams.get('error') === '1') {
            showNotification('Une erreur est survenue.', 'error');
        }
    });
    
    // Animation d'apparition des cartes
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.solution-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            setTimeout(() => {
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });
    
    // Fonction de debug (disponible dans la console)
    window.debugUserFeedback = function() {
        console.log('🔧 Informations de débogage:');
        console.log('👤 User ID:', '" . $user_id . "');
        console.log('📊 Solutions trouvées:', " . count($received_solutions) . ");
        console.log('🔍 Mode debug:', " . ($debug_mode ? 'true' : 'false') . ");
        console.log('📋 Solutions:', " . json_encode($received_solutions) . ");
        
        return {
            userId: '" . $user_id . "',
            solutionsCount: " . count($received_solutions) . ",
            debugMode: " . ($debug_mode ? 'true' : 'false') . ",
            solutions: " . json_encode($received_solutions) . "
        };
    };
    
    // Logs de débogage
    console.log('📋 Page user_feedback.php chargée');
    console.log('👤 User ID:', '" . $user_id . "');
    console.log('📊 Solutions trouvées:', " . count($received_solutions) . ");
    console.log('💡 Tapez debugUserFeedback() pour plus d\'informations');
    
    // Gestion des liens de paiement
    document.querySelectorAll('a[href*=\"payment.php\"]').forEach(link => {
        link.addEventListener('click', function(e) {
            const solutionId = this.href.match(/solution_id=(\d+)/);
            if (solutionId) {
                console.log('🔗 Redirection vers payment.php avec solution_id:', solutionId[1]);
                showNotification('Redirection vers la page de paiement...', 'info');
            }
        });
    });
    
    // Vérification de l'existence de payment.php
    fetch('payment.php', { method: 'HEAD' })
        .then(response => {
            if (!response.ok) {
                console.warn('⚠️ Le fichier payment.php semble inaccessible');
                showNotification('Attention: La page de paiement pourrait ne pas être disponible', 'warning');
            } else {
                console.log('✅ payment.php est accessible');
            }
        })
        .catch(error => {
            console.warn('⚠️ Impossible de vérifier payment.php:', error);
        });
";

// Include footer
include 'footer.php';
?>
