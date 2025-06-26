<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: exlogin.php');
    exit;
}

// Vérifier si l'ID du problème est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: exacueil.php');
    exit;
}

$problem_id = $_GET['id'];
$user = getCurrentUser();
$user_id = $user['id'];

// Configuration de la page
$page_title = "Évaluer les Solutions";
$additional_css = "
    .container {
        max-width: 900px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        flex-wrap: wrap;
        gap: 15px;
    }

    .message {
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
    }

    .message.success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .message.error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .problem-summary {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 30px;
    }

    .problem-title {
        color: #2c3e50;
        margin-top: 0;
        margin-bottom: 15px;
        font-size: 24px;
    }

    .solutions-list {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }

    .solution-card {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .solution-header {
        padding: 15px;
        background: #f8f9fa;
        border-bottom: 1px solid #e0e0e0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .solution-meta {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .solution-author {
        font-weight: 600;
        color: #2c3e50;
    }

    .solution-date {
        font-size: 14px;
        color: #7f8c8d;
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

    .solution-status.approved {
        background: #d5f5e3;
        color: #27ae60;
    }

    .solution-status.rejected {
        background: #fdedec;
        color: #e74c3c;
    }

    .solution-body {
        padding: 20px;
    }

    .solution-code {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        overflow-x: auto;
        font-family: 'Courier New', Courier, monospace;
        line-height: 1.5;
        border: 1px solid #e0e0e0;
        margin-bottom: 20px;
    }

    .solution-explanation {
        margin-bottom: 20px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #e0e0e0;
    }

    .solution-explanation h4 {
        margin-top: 0;
        color: #2c3e50;
    }

    .solution-feedback {
        margin-top: 20px;
        padding: 15px;
        background: #f0f7fb;
        border-radius: 8px;
        border: 1px solid #d1e7f5;
    }

    .solution-feedback h4 {
        margin-top: 0;
        color: #2980b9;
    }

    .solution-actions {
        padding: 20px;
        border-top: 1px solid #e0e0e0;
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .action-form {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .feedback-textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        font-size: 14px;
        resize: vertical;
        min-height: 80px;
        box-sizing: border-box;
    }

    .btn {
        padding: 10px 20px;
        border-radius: 6px;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        cursor: pointer;
        transition: all 0.3s;
        border: none;
        text-align: center;
    }

    .btn-primary {
        background: #3498db;
        color: white;
    }

    .btn-primary:hover {
        background: #2980b9;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .btn-approve {
        background: #27ae60;
        color: white;
    }

      .btn-approve:hover {
        background: #219653;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .btn-reject {
        background: #e74c3c;
        color: white;
    }

    .btn-reject:hover {
        background: #c0392b;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .no-solutions {
        background: white;
        padding: 30px;
        text-align: center;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .no-solutions h3 {
        color: #2c3e50;
        margin-top: 0;
    }

    .no-solutions p {
        color: #7f8c8d;
    }

    .price-info {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 6px;
        padding: 10px;
        margin-bottom: 15px;
        font-size: 14px;
        color: #856404;
    }

    @media (max-width: 768px) {
        .container {
            margin: 20px;
            padding: 0 10px;
        }
        
        .page-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .solution-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .solution-actions {
            padding: 15px;
        }
        
        .btn {
            width: 100%;
        }
    }
";

// Récupérer les détails du problème et vérifier si l'utilisateur en est l'auteur
try {
    $conn = connect();
    if (!$conn) {
        throw new Exception("Erreur de connexion à la base de données");
    }
    
    $stmt = $conn->prepare("
        SELECT p.*, u.username as author_username
        FROM problems p
        JOIN users u ON p.user_id = u.id
        WHERE p.problem_id = ?
    ");
    
    $stmt->execute([$problem_id]);
    $problem = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$problem) {
        $_SESSION['error_message'] = "Problème non trouvé";
        header('Location: exacueil.php');
        exit;
    }
    
    // Vérifier si l'utilisateur est l'auteur du problème
    if ($problem['user_id'] != $user_id) {
        $_SESSION['error_message'] = "Vous n'êtes pas autorisé à évaluer les solutions de ce problème";
        header('Location: problem.php?id=' . $problem_id);
        exit;
    }
    
    // Récupérer toutes les solutions soumises pour ce problème
    $stmt = $conn->prepare("
        SELECT s.*, u.username, u.name as solver_name, p.amount as price
        FROM solutions s
        JOIN users u ON s.user_id = u.id
        LEFT JOIN prices p ON s.price_id = p.price_id
        WHERE s.problem_id = ?
        ORDER BY s.created_at DESC
    ");
    
    $stmt->execute([$problem_id]);
    $solutions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Erreur lors de la récupération des solutions: " . $e->getMessage());
    $_SESSION['error_message'] = "Erreur lors de la récupération des données";
    header('Location: problem.php?id=' . $problem_id);
    exit;
}

// Traiter l'évaluation d'une solution
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $solution_id = $_POST['solution_id'] ?? 0;
    $action = $_POST['action'] ?? '';
    $feedback = trim($_POST['feedback'] ?? '');
    
    if (!in_array($action, ['approve', 'reject']) || !is_numeric($solution_id)) {
        $message = "Action invalide.";
        $message_type = 'error';
    } else {
        try {
            // Récupérer les informations de la solution
            $stmt = $conn->prepare("
                SELECT s.*, p.points
                FROM solutions s
                JOIN problems p ON s.problem_id = p.problem_id
                WHERE s.id = ? AND s.problem_id = ?
            ");
            
            $stmt->execute([$solution_id, $problem_id]);
            $solution = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$solution) {
                $message = "Solution introuvable.";
                $message_type = 'error';
            } else if ($solution['status'] !== 'pending') {
                $message = "Cette solution a déjà été évaluée.";
                $message_type = 'error';
            } else {
                $conn->beginTransaction();
                
                // Mettre à jour le statut de la solution
                $status = ($action === 'approve') ? 'approved' : 'rejected';
                
                $stmt = $conn->prepare("
                    UPDATE solutions
                    SET status = ?, feedback = ?, evaluated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                
                $result = $stmt->execute([$status, $feedback, $solution_id]);
                
                if ($result) {
                    // Si la solution est approuvée, mettre à jour les statistiques de l'utilisateur
                    if ($status === 'approved') {
                        // Mettre à jour le score et le nombre de problèmes résolus
                        $stmt = $conn->prepare("
                            UPDATE users
                            SET score = score + ?, problems_solved = problems_solved + 1
                            WHERE id = ?
                        ");
                        
                        $stmt->execute([$solution['points'], $solution['user_id']]);
                    }
                    
                    $conn->commit();
                    $message = "La solution a été " . ($status === 'approved' ? "approuvée" : "rejetée") . " avec succès.";
                    $message_type = 'success';
                    
                    // Rafraîchir la liste des solutions
                    $stmt = $conn->prepare("
                        SELECT s.*, u.username, u.name as solver_name, p.amount as price
                        FROM solutions s
                        JOIN users u ON s.user_id = u.id
                        LEFT JOIN prices p ON s.price_id = p.price_id
                        WHERE s.problem_id = ?
                        ORDER BY s.created_at DESC
                    ");
                    
                    $stmt->execute([$problem_id]);
                    $solutions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } else {
                    $conn->rollBack();
                    $message = "Une erreur est survenue lors de l'évaluation de la solution.";
                    $message_type = 'error';
                }
            }
        } catch (Exception $e) {
            if ($conn) {
                $conn->rollBack();
            }
            error_log("Erreur lors de l'évaluation de la solution: " . $e->getMessage());
            $message = "Une erreur est survenue lors de l'évaluation de la solution.";
            $message_type = 'error';
        }
    }
}

// Inclure l'en-tête
include 'header.php';
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-clipboard-check"></i> Évaluer les Solutions</h1>
        <a href="problem.php?id=<?php echo $problem_id; ?>" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Retour au Problème
        </a>
    </div>
    
    <?php if (!empty($message)): ?>
        <div class="message <?php echo $message_type; ?>">
            <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?>"></i>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    
    <div class="problem-summary">
        <h2 class="problem-title"><?php echo htmlspecialchars($problem['title']); ?></h2>
        <p><strong>Auteur:</strong> <?php echo htmlspecialchars($problem['author_username']); ?></p>
        <p>Vous évaluez les solutions soumises pour ce problème.</p>
    </div>
    
    <?php if (empty($solutions)): ?>
        <div class="no-solutions">
            <h3><i class="fas fa-inbox"></i> Aucune solution soumise</h3>
            <p>Personne n'a encore soumis de solution pour ce problème.</p>
            <p>Les développeurs peuvent soumettre leurs solutions depuis la page du problème.</p>
        </div>
    <?php else: ?>
        <div class="solutions-list">
            <?php foreach ($solutions as $solution): ?>
                <div class="solution-card">
                    <div class="solution-header">
                        <div class="solution-meta">
                            <span class="solution-author">
                                <i class="fas fa-user"></i>
                                <?php echo htmlspecialchars($solution['solver_name'] ?: $solution['username']); ?> 
                                (@<?php echo htmlspecialchars($solution['username']); ?>)
                            </span>
                            <span class="solution-date">
                                <i class="fas fa-calendar"></i>
                                Soumis le <?php echo date('d/m/Y à H:i', strtotime($solution['created_at'])); ?>
                            </span>
                            <?php if ($solution['price']): ?>
                                <span class="solution-date">
                                    <i class="fas fa-euro-sign"></i>
                                    Prix proposé: <?php echo number_format($solution['price'], 2); ?>€
                                </span>
                            <?php endif; ?>
                        </div>
                        <span class="solution-status <?php echo htmlspecialchars($solution['status']); ?>">
                            <i class="fas fa-<?php 
                                switch($solution['status']) {
                                    case 'pending': echo 'clock'; break;
                                    case 'approved': echo 'check'; break;
                                    case 'rejected': echo 'times'; break;
                                    default: echo 'question';
                                }
                            ?>"></i>
                            <?php 
                            switch($solution['status']) {
                                case 'pending': echo 'En attente'; break;
                                case 'approved': echo 'Approuvée'; break;
                                case 'rejected': echo 'Rejetée'; break;
                                default: echo $solution['status'];
                            }
                            ?>
                        </span>
                    </div>
                    
                    <div class="solution-body">
                        <h3><i class="fas fa-code"></i> Solution proposée</h3>
                        <pre class="solution-code"><?php echo htmlspecialchars($solution['solution_code']); ?></pre>
                        
                        <?php if (!empty($solution['explanation'])): ?>
                            <div class="solution-explanation">
                                <h4><i class="fas fa-lightbulb"></i> Explication du développeur</h4>
                                <?php echo nl2br(htmlspecialchars($solution['explanation'])); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($solution['feedback']) && $solution['status'] !== 'pending'): ?>
                            <div class="solution-feedback">
                                <h4><i class="fas fa-comment"></i> Votre feedback</h4>
                                <?php echo nl2br(htmlspecialchars($solution['feedback'])); ?>
                                <?php if ($solution['evaluated_at']): ?>
                                    <p><small><i class="fas fa-clock"></i> Évalué le <?php echo date('d/m/Y à H:i', strtotime($solution['evaluated_at'])); ?></small></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($solution['status'] === 'pending'): ?>
                        <div class="solution-actions">
                            <?php if ($solution['price']): ?>
                                <div class="price-info">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>Prix proposé:</strong> <?php echo number_format($solution['price'], 2); ?>€
                                    - En approuvant cette solution, vous acceptez de payer ce montant au développeur.
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" class="action-form" onsubmit="return confirm('Êtes-vous sûr de vouloir approuver cette solution?');">
                                <input type="hidden" name="solution_id" value="<?php echo $solution['id']; ?>">
                                <input type="hidden" name="action" value="approve">
                                <textarea name="feedback" class="feedback-textarea" placeholder="Donnez un feedback positif sur cette solution (optionnel)..."></textarea>
                                <button type="submit" class="btn btn-approve">
                                    <i class="fas fa-check"></i> Approuver cette solution
                                </button>
                            </form>
                            
                            <form method="POST" class="action-form" onsubmit="return confirm('Êtes-vous sûr de vouloir rejeter cette solution?');">
                                <input type="hidden" name="solution_id" value="<?php echo $solution['id']; ?>">
                                <input type="hidden" name="action" value="reject">
                                <textarea name="feedback" class="feedback-textarea" placeholder="Expliquez pourquoi vous rejetez cette solution..." required></textarea>
                                <button type="submit" class="btn btn-reject">
                                    <i class="fas fa-times"></i> Rejeter cette solution
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php
// Scripts additionnels
$additional_scripts = "
    // Animation des cartes de solutions
    document.addEventListener('DOMContentLoaded', function() {
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
    });

    // Confirmation personnalisée pour les actions
    document.addEventListener('DOMContentLoaded', function() {
        const approveForms = document.querySelectorAll('form[onsubmit*=\"approuver\"]');
        const rejectForms = document.querySelectorAll('form[onsubmit*=\"rejeter\"]');
        
        approveForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const feedback = this.querySelector('textarea[name=\"feedback\"]').value.trim();
                const priceInfo = this.querySelector('.price-info');
                let confirmMessage = 'Êtes-vous sûr de vouloir approuver cette solution?';
                
                if (priceInfo) {
                    const priceText = priceInfo.textContent.match(/([0-9,]+\.[0-9]{2})€/);
                    if (priceText) {
                        confirmMessage += '\\n\\nCela implique un paiement de ' + priceText[1] + '€ au développeur.';
                    }
                }
                
                if (!confirm(confirmMessage)) {
                    e.preventDefault();
                    return false;
                }
            });
        });
        
        rejectForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const feedback = this.querySelector('textarea[name=\"feedback\"]').value.trim();
                if (!feedback) {
                    alert('Veuillez expliquer pourquoi vous rejetez cette solution.');
                    e.preventDefault();
                    return false;
                }
                
                if (!confirm('Êtes-vous sûr de vouloir rejeter cette solution?\\n\\nAssurez-vous que votre feedback est constructif.')) {
                    e.preventDefault();
                    return false;
                }
            });
        });
    });

    // Auto-resize des textareas
    document.addEventListener('DOMContentLoaded', function() {
        const textareas = document.querySelectorAll('.feedback-textarea');
        textareas.forEach(textarea => {
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        });
    });

    // Highlight du code
    document.addEventListener('DOMContentLoaded', function() {
        const codeBlocks = document.querySelectorAll('.solution-code');
        codeBlocks.forEach(block => {
            block.addEventListener('click', function() {
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(this.textContent).then(() => {
                        const originalBg = this.style.backgroundColor;
                        this.style.backgroundColor = '#e8f5e8';
                        this.style.transition = 'background-color 0.3s';
                        
                        setTimeout(() => {
                            this.style.backgroundColor = originalBg;
                        }, 1000);
                        
                        showMessage('Code copié dans le presse-papiers!', 'success');
                    });
                }
            });
            
            block.title = 'Cliquer pour copier le code';
            block.style.cursor = 'pointer';
        });
    });

    // Fonction pour afficher des messages
    function showMessage(message, type = 'info') {
        const messageDiv = document.createElement('div');
        messageDiv.style.position = 'fixed';
        messageDiv.style.top = '20px';
        messageDiv.style.right = '20px';
        messageDiv.style.padding = '15px 20px';
        messageDiv.style.borderRadius = '5px';
        messageDiv.style.zIndex = '9999';
        messageDiv.style.maxWidth = '300px';
        messageDiv.style.boxShadow = '0 4px 6px rgba(0,0,0,0.1)';
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
            messageDiv.style.opacity = '0';
            setTimeout(() => {
                if (messageDiv.parentNode) {
                    messageDiv.parentNode.removeChild(messageDiv);
                }
            }, 300);
        }, 3000);
    }
";

// Inclure le pied de page
include 'footer.php';
?>


