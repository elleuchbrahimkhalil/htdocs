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
$page_title = "Résoudre le Problème";
$additional_css = "
    .container {
        max-width: 900px;
        margin: 40px auto;
        padding: 0 20px;
    }

    .problem-summary {
        background: white;
        padding: 25px;
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

    .problem-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 20px;
        font-size: 14px;
        color: #7f8c8d;
    }

    .problem-meta span {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .difficulty {
        padding: 4px 8px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
    }

    .difficulty.easy { 
        background: #d5f5e3; 
        color: #27ae60; 
    }
    
    .difficulty.medium { 
        background: #fef9e7; 
        color: #f39c12; 
    }
    
    .difficulty.hard { 
        background: #fdedec; 
        color: #e74c3c; 
    }

    .code-block {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        overflow-x: auto;
        font-family: 'Courier New', Courier, monospace;
        line-height: 1.5;
        border: 1px solid #e0e0e0;
        margin-bottom: 20px;
    }

    .solution-form {
        background: white;
        padding: 25px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .form-group {
        margin-bottom: 20px;
    }

    label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #2c3e50;
    }

    textarea {
        width: 100%;
        padding: 12px;
        border: 1px solid #e0e0e0;
        border-radius: 6px;
        font-family: 'Courier New', Courier, monospace;
        font-size: 14px;
        resize: vertical;
        min-height: 200px;
        box-sizing: border-box;
    }

    .explanation-textarea {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        min-height: 100px;
    }

    .btn {
        padding: 12px 24px;
        border-radius: 6px;
        font-weight: 600;
        text-decoration: none;
        display: inline-block;
        cursor: pointer;
        transition: all 0.3s;
        border: none;
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

    .btn-outline {
        background: transparent;
        color: #3498db;
        border: 2px solid #3498db;
    }

    .btn-outline:hover {
        background: #ebf5fb;
    }

    .error-message {
        background-color: #f8d7da;
        color: #721c24;
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        border: 1px solid #f5c6cb;
    }

    .success-message {
        background-color: #d4edda;
        color: #155724;
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        border: 1px solid #c3e6cb;
    }

    .warning-message {
        background-color: #fff3cd;
        color: #856404;
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        border: 1px solid #ffeaa7;
    }

    .form-actions {
        display: flex;
        gap: 15px;
        margin-top: 25px;
    }

    .existing-solution {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        border-left: 4px solid #3498db;
    }

    @media (max-width: 768px) {
        .container {
            margin: 20px;
            padding: 0 10px;
        }
        
        .form-actions {
            flex-direction: column;
        }
        
        .btn {
            width: 100%;
            text-align: center;
        }
        
        .problem-meta {
            flex-direction: column;
            gap: 10px;
        }
    }
";

// Récupérer les détails du problème
try {
    $conn = connect();
    if (!$conn) {
        throw new Exception("Erreur de connexion à la base de données");
    }
    
    $stmt = $conn->prepare("
        SELECT p.*, u.username, u.name as author_name
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
    
    // Vérifier si l'utilisateur est l'auteur du problème (ne peut pas résoudre son propre problème)
    if ($problem['user_id'] == $user_id) {
        $_SESSION['error_message'] = "Vous ne pouvez pas résoudre votre propre problème";
        header('Location: problem.php?id=' . $problem_id);
        exit;
    }
    
    // Vérifier si l'utilisateur a déjà soumis une solution
    $stmt = $conn->prepare("
        SELECT s.*, p.amount as price
        FROM solutions s
        LEFT JOIN prices p ON s.price_id = p.price_id
        WHERE s.problem_id = ? AND s.user_id = ?
        ORDER BY s.created_at DESC
        LIMIT 1
    ");
    
    $stmt->execute([$problem_id, $user_id]);
    $existing_solution = $stmt->fetch(PDO::FETCH_ASSOC);
    
} catch (Exception $e) {
    error_log("Erreur lors de la récupération du problème: " . $e->getMessage());
    $_SESSION['error_message'] = "Erreur lors de la récupération du problème";
    header('Location: exacueil.php');
    exit;
}

// Traiter la soumission de solution
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $solution_code = trim($_POST['solution_code'] ?? '');
    $explanation = trim($_POST['explanation'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    
    if (empty($solution_code)) {
        $error = "Le code de solution est obligatoire.";
    } elseif ($price <= 0) {
        $error = "Le prix doit être supérieur à 0.";
    } else {
        try {
            $conn->beginTransaction();
            
            // Créer le prix
            $stmt = $conn->prepare("INSERT INTO prices (amount) VALUES (?)");
            $stmt->execute([$price]);
            $price_id = $conn->lastInsertId();
            
            if ($existing_solution) {
                // Mettre à jour la solution existante
                $stmt = $conn->prepare("
                    UPDATE solutions 
                    SET solution_code = ?, explanation = ?, price_id = ?, status = 'pending', updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $result = $stmt->execute([$solution_code, $explanation, $price_id, $existing_solution['id']]);
                $success = "Votre solution a été mise à jour avec succès et est en attente d'évaluation.";
            } else {
                // Insérer une nouvelle solution
                $stmt = $conn->prepare("
                    INSERT INTO solutions (
                        problem_id, user_id, solution_code, explanation, price_id, status, created_at
                                      ) VALUES (
                        ?, ?, ?, ?, ?, 'pending', CURRENT_TIMESTAMP
                    )
                ");
                $result = $stmt->execute([$problem_id, $user_id, $solution_code, $explanation, $price_id]);
                $success = "Votre solution a été soumise avec succès et est en attente d'évaluation.";
            }
            
            if ($result) {
                $conn->commit();
                // Rediriger vers la page du problème après 3 secondes
                header("refresh:3;url=problem.php?id=$problem_id");
            } else {
                $conn->rollBack();
                $error = "Une erreur est survenue lors de la soumission de votre solution.";
            }
        } catch (Exception $e) {
            if ($conn) {
                $conn->rollBack();
            }
            error_log("Erreur lors de la soumission de la solution: " . $e->getMessage());
            $error = "Une erreur est survenue lors de la soumission de votre solution.";
        }
    }
}

// Inclure l'en-tête
include 'header.php';
?>

<div class="container">
    <h1><i class="fas fa-puzzle-piece"></i> Résoudre: <?php echo htmlspecialchars($problem['title']); ?></h1>
    
    <?php if (!empty($error)): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="success-message">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            <br><small>Redirection automatique dans 3 secondes...</small>
        </div>
    <?php endif; ?>
    
    <?php if ($existing_solution): ?>
        <div class="warning-message">
            <i class="fas fa-info-circle"></i> 
            <strong>Solution existante détectée</strong><br>
            Vous avez déjà soumis une solution pour ce problème (statut: <?php 
                switch($existing_solution['status']) {
                    case 'pending': echo 'En attente'; break;
                    case 'approved': echo 'Approuvée'; break;
                    case 'rejected': echo 'Rejetée'; break;
                    default: echo $existing_solution['status'];
                }
            ?>). 
            En soumettant à nouveau, vous mettrez à jour votre solution existante.
        </div>
    <?php endif; ?>

    <div class="problem-summary">
        <h2 class="problem-title"><?php echo htmlspecialchars($problem['title']); ?></h2>
        
        <div class="problem-meta">
            <span><i class="fas fa-user"></i> Publié par <?php echo htmlspecialchars($problem['author_name'] ?: $problem['username']); ?></span>
            <span><i class="fas fa-calendar"></i> Le <?php echo date('d/m/Y', strtotime($problem['created_at'])); ?></span>
            <span><i class="fas fa-code"></i> Langage: <?php echo htmlspecialchars(ucfirst($problem['language'])); ?></span>
            <span class="difficulty <?php echo htmlspecialchars($problem['difficulty']); ?>">
                <i class="fas fa-signal"></i>
                <?php 
                $difficulty_text = '';
                switch($problem['difficulty']) {
                    case 'easy': $difficulty_text = 'Facile'; break;
                    case 'medium': $difficulty_text = 'Moyen'; break;
                    case 'hard': $difficulty_text = 'Difficile'; break;
                    default: $difficulty_text = ucfirst($problem['difficulty']);
                }
                echo htmlspecialchars($difficulty_text);
                ?>
            </span>
            <span><i class="fas fa-star"></i> <?php echo htmlspecialchars($problem['points']); ?> points</span>
        </div>
        
        <h3><i class="fas fa-info-circle"></i> Description</h3>
        <div><?php echo nl2br(htmlspecialchars($problem['description'])); ?></div>
        
        <?php if (!empty($problem['code'])): ?>
            <h3><i class="fas fa-code"></i> Code du problème</h3>
            <pre class="code-block"><?php echo htmlspecialchars($problem['code']); ?></pre>
        <?php endif; ?>
        
        <?php if (!empty($problem['tags'])): ?>
            <h3><i class="fas fa-tags"></i> Tags</h3>
            <div class="tags-container">
                <?php foreach (explode(',', $problem['tags']) as $tag): ?>
                    <span class="tag"><?php echo htmlspecialchars(trim($tag)); ?></span>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <?php if (empty($success)): ?>
        <div class="solution-form">
            <h2><i class="fas fa-lightbulb"></i> Proposer une solution</h2>
            
            <?php if ($existing_solution): ?>
                <div class="existing-solution">
                    <h4><i class="fas fa-history"></i> Votre solution actuelle:</h4>
                    <p><strong>Statut:</strong> <?php 
                        switch($existing_solution['status']) {
                            case 'pending': echo '<span style="color: #f39c12;">En attente d\'évaluation</span>'; break;
                            case 'approved': echo '<span style="color: #27ae60;">Approuvée</span>'; break;
                            case 'rejected': echo '<span style="color: #e74c3c;">Rejetée</span>'; break;
                            default: echo $existing_solution['status'];
                        }
                    ?></p>
                    <?php if ($existing_solution['price']): ?>
                        <p><strong>Prix actuel:</strong> <?php echo number_format($existing_solution['price'], 2); ?>€</p>
                    <?php endif; ?>
                    <p><strong>Dernière modification:</strong> <?php echo date('d/m/Y à H:i', strtotime($existing_solution['updated_at'] ?: $existing_solution['created_at'])); ?></p>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label for="solution_code">
                        <i class="fas fa-code"></i> Votre solution (<?php echo htmlspecialchars(ucfirst($problem['language'])); ?>)
                    </label>
                    <textarea id="solution_code" name="solution_code" required 
                              placeholder="Écrivez votre code ici..."><?php echo htmlspecialchars($existing_solution['solution_code'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="explanation">
                        <i class="fas fa-comment"></i> Explication (optionnelle)
                    </label>
                    <textarea id="explanation" name="explanation" class="explanation-textarea" 
                              placeholder="Expliquez votre approche et votre solution..."><?php echo htmlspecialchars($existing_solution['explanation'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="price">
                        <i class="fas fa-euro-sign"></i> Prix proposé (€)
                    </label>
                    <input type="number" id="price" name="price" step="0.01" min="0.01" required
                           placeholder="0.00" value="<?php echo htmlspecialchars($existing_solution['price'] ?? ''); ?>"
                           style="width: 150px; padding: 10px; border: 1px solid #e0e0e0; border-radius: 6px;">
                    <small style="display: block; margin-top: 5px; color: #666;">
                        Proposez un prix équitable pour votre solution. L'auteur du problème paiera ce montant si votre solution est approuvée.
                    </small>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> 
                        <?php echo $existing_solution ? 'Mettre à jour ma solution' : 'Soumettre ma solution'; ?>
                    </button>
                    <a href="problem.php?id=<?php echo $problem_id; ?>" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Retour au problème
                    </a>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<?php
// Scripts additionnels
$additional_scripts = "
    // Auto-resize des textareas
    document.addEventListener('DOMContentLoaded', function() {
        const textareas = document.querySelectorAll('textarea');
        textareas.forEach(textarea => {
            // Ajuster la hauteur initiale
            textarea.style.height = 'auto';
            textarea.style.height = (textarea.scrollHeight) + 'px';
            
            // Ajuster lors de la saisie
            textarea.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = (this.scrollHeight) + 'px';
            });
        });
    });

    // Validation du formulaire
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form[method=\"POST\"]');
        if (form) {
            form.addEventListener('submit', function(e) {
                const solutionCode = document.getElementById('solution_code').value.trim();
                const price = parseFloat(document.getElementById('price').value);
                
                if (!solutionCode) {
                    alert('Veuillez entrer votre code de solution.');
                    e.preventDefault();
                    return false;
                }
                
                if (!price || price <= 0) {
                    alert('Veuillez entrer un prix valide supérieur à 0.');
                    e.preventDefault();
                    return false;
                }
                
                // Confirmation avant soumission
                const isUpdate = " . ($existing_solution ? 'true' : 'false') . ";
                const confirmMessage = isUpdate 
                    ? 'Êtes-vous sûr de vouloir mettre à jour votre solution?'
                    : 'Êtes-vous sûr de vouloir soumettre cette solution pour ' + price.toFixed(2) + '€?';
                
                if (!confirm(confirmMessage)) {
                    e.preventDefault();
                    return false;
                }
            });
        }
    });

    // Copier le code du problème
    document.addEventListener('DOMContentLoaded', function() {
        const codeBlock = document.querySelector('.code-block');
        if (codeBlock) {
            codeBlock.addEventListener('click', function() {
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
            
            codeBlock.title = 'Cliquer pour copier le code';
            codeBlock.style.cursor = 'pointer';
        }
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

    // Animation d'apparition
    document.addEventListener('DOMContentLoaded', function() {
        const containers = document.querySelectorAll('.problem-summary, .solution-form');
        containers.forEach((container, index) => {
            container.style.opacity = '0';
            container.style.transform = 'translateY(20px)';
            setTimeout(() => {
                container.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
            }, index * 200);
        });
    });
";

// Inclure le pied de page
include 'footer.php';
?>
