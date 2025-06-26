<?php
// Démarrer la session si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
require_once 'verification.php';
require_once 'db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: exlogin.php');
    exit;
}

// Récupérer l'ID du problème depuis l'URL
$problem_id = isset($_GET['problem_id']) ? (int)$_GET['problem_id'] : 0;
// Récupérer l'utilisateur connecté
$user = getCurrentUser();
$user_id = $user['id'];

// Définir le titre de la page
$page_title = "Soumettre une Solution";

// CSS spécifique à cette page
$additional_css = "
    .solution-form {
        max-width: 800px;
        margin: 0 auto;
        background: white;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .form-group {
        margin-bottom: 20px;
    }

    label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #333;
    }

    .code-editor {
        border: 1px solid #ddd;
        border-radius: 6px;
        overflow: hidden;
        margin-bottom: 20px;
    }

    .CodeMirror {
        height: 300px;
        font-size: 14px;
    }

    textarea {
        width: 100%;
        min-height: 120px;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-family: inherit;
        resize: vertical;
        font-size: 14px;
        box-sizing: border-box;
    }

    .code-textarea {
        font-family: 'Courier New', Courier, monospace;
        min-height: 200px;
    }

    .price-input {
        width: 150px;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 16px;
    }

    .price-input:focus, textarea:focus {
        border-color: #4CAF50;
        box-shadow: 0 0 0 2px rgba(76, 175, 80, 0.2);
        outline: none;
    }

    .price-container {
        background-color: #f0f7fb;
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        border-left: 3px solid #3498db;
    }

    .price-container p {
        margin-top: 0;
        color: #666;
    }

    .form-footer {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid #eee;
        flex-wrap: wrap;
        gap: 15px;
    }

    .btn-cancel {
        background-color: transparent;
        color: #666;
        border: 1px solid #ddd;
    }

    .btn-cancel:hover {
        background-color: #f5f5f5;
    }

    .problem-info {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        border-left: 4px solid #3498db;
    }

    .problem-info h4 {
        margin-top: 0;
        color: #2c3e50;
    }

    .existing-solution-info {
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 6px;
        padding: 15px;
        margin-bottom: 20px;
    }

    .existing-solution-info h4 {
        margin-top: 0;
        color: #856404;
    }

    .field-error {
        color: #e74c3c;
        font-size: 14px;
        margin-top: 5px;
    }

    @media (max-width: 768px) {
        .solution-form {
            margin: 0 10px;
            padding: 20px;
        }
        
        .form-footer {
            flex-direction: column;
        }
        
        .btn {
            width: 100%;
            text-align: center;
        }
        
        .price-input {
            width: 100%;
        }
    }
";

// Récupérer les messages de session
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';

// Nettoyer les messages de session
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);

// Récupérer les anciennes valeurs en cas d'erreur
$old_solution_code = isset($_SESSION['old_solution_code']) ? $_SESSION['old_solution_code'] : '';
$old_explanation = isset($_SESSION['old_explanation']) ? $_SESSION['old_explanation'] : '';
$old_price = isset($_SESSION['old_price']) ? $_SESSION['old_price'] : '';

// Nettoyer les anciennes valeurs
unset($_SESSION['old_solution_code']);
unset($_SESSION['old_explanation']);
unset($_SESSION['old_price']);

// Récupérer les erreurs de formulaire
$form_errors = isset($_SESSION['form_errors']) ? $_SESSION['form_errors'] : [];
unset($_SESSION['form_errors']);

// Récupérer les informations du problème
$problem = null;
$problem_error = null;
$existing_solution = null;

if ($problem_id <= 0) {
    $problem_error = "Aucun problème spécifié. Veuillez sélectionner un problème.";
} else {
    try {
        $conn = connect();
        
        if (!$conn) {
            $problem_error = "Erreur de connexion à la base de données.";
        } else {
            // Récupérer le problème
            $stmt = $conn->prepare("
                SELECT p.*, u.username as author_username, u.name as author_name
                FROM problems p
                JOIN users u ON p.user_id = u.id
                WHERE p.problem_id = ?
            ");
            $stmt->execute([$problem_id]);
            $problem = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$problem) {
                $problem_error = "Le problème demandé n'existe pas ou a été supprimé.";
            } else {
                // Vérifier si l'utilisateur est l'auteur du problème
                if ($problem['user_id'] == $user_id) {
                    $problem_error = "Vous ne pouvez pas soumettre une solution à votre propre problème.";
                } else {
                    // Vérifier si l'utilisateur a déjà soumis une solution pour ce problème
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
                    
                    // Si une solution existe et qu'il n'y a pas de données d'erreur, utiliser ses valeurs
                    if ($existing_solution && empty($old_solution_code) && empty($old_explanation) && empty($old_price)) {
                        $old_solution_code = $existing_solution['solution_code'];
                        $old_explanation = $existing_solution['explanation'];
                        $old_price = $existing_solution['price'] ?? '';
                    }
                }
            }
        }
    } catch (PDOException $e) {
        error_log("Erreur PDO lors de la récupération du problème: " . $e->getMessage());
        $problem_error = "Erreur de base de données: " . $e->getMessage();
    } catch (Exception $e) {
        error_log("Exception lors de la récupération du problème: " . $e->getMessage());
        $problem_error = "Une erreur est survenue lors de la récupération du problème: " . $e->getMessage();
    }
}

// Inclure l'en-tête
include 'header.php';
?>

<h1><i class="fas fa-paper-plane"></i> Soumettre une Solution</h1>

<?php if ($problem_error): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($problem_error); ?>
        <div style="margin-top: 15px;">
            <a href="exacueil.php" class="btn btn-primary">
                <i class="fas fa-home"></i> Retourner à l'accueil
            </a>
        </div>
    </div>
<?php elseif ($problem): ?>
    
    <?php if ($existing_solution): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i> 
            <strong>Solution existante détectée</strong><br>
            Vous avez déjà soumis une solution pour ce problème 
            (statut: <?php 
                switch($existing_solution['status']) {
                    case 'pending': echo '<span style="color: #f39c12;">En attente</span>'; break;
                    case 'approved': echo '<span style="color: #27ae60;">Approuvée</span>'; break;
                    case 'rejected': echo '<span style="color: #e74c3c;">Rejetée</span>'; break;
                    default: echo $existing_solution['status'];
                }
            ?>). 
            En soumettant à nouveau, vous mettrez à jour votre solution existante.
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

    <?php if (!empty($form_errors['general'])): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars(implode(', ', $form_errors['general'])); ?>
        </div>
    <?php endif; ?>

    <div class="solution-form">
        <div class="problem-info">
            <h4><i class="fas fa-info-circle"></i> Problème: <?php echo htmlspecialchars($problem['title']); ?></h4>
            <p><strong>Auteur:</strong> <?php echo htmlspecialchars($problem['author_name'] ?: $problem['author_username']); ?></p>
            <p><strong>Langage:</strong> <?php echo htmlspecialchars(ucfirst($problem['language'])); ?></p>
            <p><strong>Difficulté:</strong> <?php 
                switch($problem['difficulty']) {
                    case 'easy': echo 'Facile'; break;
                    case 'medium': echo 'Moyen'; break;
                    case 'hard': echo 'Difficile'; break;
                    default: echo ucfirst($problem['difficulty']);
                }
            ?></p>
            <p><strong>Points:</strong> <?php echo htmlspecialchars($problem['points']); ?></p>
            <p><a href="problem.php?id=<?php echo $problem_id; ?>" target="_blank">
                <i class="fas fa-external-link-alt"></i> Voir le problème complet
            </a></p>
        </div>

        <?php if ($existing_solution): ?>
            <div class="existing-solution-info">
                <h4><i class="fas fa-history"></i> Votre solution actuelle</h4>
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
                <p><strong>Soumis le:</strong> <?php echo date('d/m/Y à H:i', strtotime($existing_solution['created_at'])); ?></p>
                <?php if ($existing_solution['feedback']): ?>
                    <p><strong>Feedback reçu:</strong> <?php echo nl2br(htmlspecialchars($existing_solution['feedback'])); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="process_solution.php">
            <input type="hidden" name="problem_id" value="<?php echo $problem_id; ?>">
            <?php if ($existing_solution): ?>
                <input type="hidden" name="solution_id" value="<?php echo $existing_solution['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="solution_code">
                    <i class="fas fa-code"></i> Code de la solution *
                </label>
                <textarea id="solution_code" name="solution_code" class="code-textarea" required
                          placeholder="Entrez votre code ici..."><?php echo htmlspecialchars($old_solution_code); ?></textarea>
                <?php if (!empty($form_errors['solution_code'])): ?>
                    <div class="field-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo htmlspecialchars(implode(', ', $form_errors['solution_code'])); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="explanation">
                    <i class="fas fa-comment"></i> Explication de votre solution
                </label>
                <textarea id="explanation" name="explanation" 
                          placeholder="Expliquez votre approche, la logique derrière votre solution, les algorithmes utilisés, etc."><?php echo htmlspecialchars($old_explanation); ?></textarea>
                <?php if (!empty($form_errors['explanation'])): ?>
                    <div class="field-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo htmlspecialchars(implode(', ', $form_errors['explanation'])); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="price-container">
                <label for="price">
                    <i class="fas fa-euro-sign"></i> Prix proposé *
                </label>
                <p>Proposez un prix équitable pour votre solution. L'auteur du problème paiera ce montant si votre solution est approuvée.</p>
                <input type="number" id="price" name="price" class="price-input" 
                       min="0.01" step="0.01" placeholder="0.00" 
                       value="<?php echo htmlspecialchars($old_price); ?>" required>
                <span style="margin-left: 5px;">€</span>
                <?php if (!empty($form_errors['price'])): ?>
                    <div class="field-error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo htmlspecialchars(implode(', ', $form_errors['price'])); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="form-footer">
                <a href="problem.php?id=<?php echo $problem_id; ?>" class="btn btn-cancel">
                    <i class="fas fa-arrow-left"></i> Retour au problème
                </a>
                <button type="submit" class="btn btn-primary">
                    <?php if ($existing_solution): ?>
                        <i class="fas fa-sync-alt"></i> Mettre à jour la solution
                    <?php else: ?>
                        <i class="fas fa-paper-plane"></i> Soumettre la solution
                    <?php endif; ?>
                </button>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php
// Scripts additionnels
$additional_scripts = "
    // Auto-resize des textareas
    document.addEventListener('DOMContentLoaded', function() {
        const textareas = document.querySelectorAll('textarea');
        textareas.forEach(textarea => {
            // Fonction pour ajuster la hauteur
            function adjustHeight() {
                textarea.style.height = 'auto';
                textarea.style.height = Math.max(textarea.scrollHeight, 120) + 'px';
            }
            
            // Ajuster la hauteur initiale
            adjustHeight();
            
            // Ajuster lors de la saisie
            textarea.addEventListener('input', adjustHeight);
            textarea.addEventListener('paste', function() {
                setTimeout(adjustHeight, 10);
            });
        });
    });

    // Validation du formulaire
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form[action=\"process_solution.php\"]');
        if (form) {
            form.addEventListener('submit', function(e) {
                const solutionCode = document.getElementById('solution_code').value.trim();
                const price = parseFloat(document.getElementById('price').value);
                
                // Validation du code
                if (!solutionCode) {
                    alert('Veuillez entrer votre code de solution.');
                    document.getElementById('solution_code').focus();
                    e.preventDefault();
                    return false;
                }
                
                if (solutionCode.length < 10) {
                    if (!confirm('Votre code semble très court. Êtes-vous sûr qu\\'il est complet?')) {
                        document.getElementById('solution_code').focus();
                        e.preventDefault();
                        return false;
                    }
                }
                
                // Validation du prix
                if (!price || price <= 0) {
                    alert('Veuillez entrer un prix valide supérieur à 0.');
                    document.getElementById('price').focus();
                    e.preventDefault();
                    return false;
                }
                
                if (price > 1000) {
                    if (!confirm('Le prix proposé est très élevé (' + price.toFixed(2) + '€). Êtes-vous sûr?')) {
                        document.getElementById('price').focus();
                        e.preventDefault();
                        return false;
                    }
                }
                
                // Confirmation finale
                const isUpdate = " . ($existing_solution ? 'true' : 'false') . ";
                let confirmMessage;
                
                if (isUpdate) {
                    confirmMessage = 'Êtes-vous sûr de vouloir mettre à jour votre solution?\\n\\n';
                    confirmMessage += 'Nouveau prix: ' + price.toFixed(2) + '€';
                } else {
                    confirmMessage = 'Êtes-vous sûr de vouloir soumettre cette solution?\\n\\n';
                    confirmMessage += 'Prix proposé: ' + price.toFixed(2) + '€\\n';
                    confirmMessage += 'Une fois soumise, votre solution sera évaluée par l\\'auteur du problème.';
                }
                
                if (!confirm(confirmMessage)) {
                    e.preventDefault();
                    return false;
                }
                
                // Désactiver le bouton pour éviter les doubles soumissions
                const submitBtn = form.querySelector('button[type=\"submit\"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class=\"fas fa-spinner fa-spin\"></i> Envoi en cours...';
                }
            });
        }
    });

    // Formatage automatique du prix
    document.addEventListener('DOMContentLoaded', function() {
        const priceInput = document.getElementById('price');
        if (priceInput) {
            priceInput.addEventListener('blur', function() {
                const value = parseFloat(this.value);
                if (!isNaN(value) && value > 0) {
                    this.value = value.toFixed(2);
                }
            });
            
            // Empêcher la saisie de valeurs négatives
            priceInput.addEventListener('input', function() {
                if (this.value < 0) {
                    this.value = '';
                }
            });
        }
    });

    // Compteur de caractères pour le code
    document.addEventListener('DOMContentLoaded', function() {
        const codeTextarea = document.getElementById('solution_code');
        if (codeTextarea) {
            const counter = document.createElement('div');
            counter.style.textAlign = 'right';
            counter.style.fontSize = '12px';
            counter.style.color = '#666';
            counter.style.marginTop = '5px';
            
            function updateCounter() {
                const length = codeTextarea.value.length;
                counter.textContent = length + ' caractères';
                
                if (length > 10000) {
                    counter.style.color = '#e74c3c';
                    counter.textContent += ' (limite dépassée)';
                } else if (length > 8000) {
                    counter.style.color = '#f39c12';
                } else {
                    counter.style.color = '#666';
                }
            }
            
            codeTextarea.parentNode.appendChild(counter);
            updateCounter();
            
            codeTextarea.addEventListener('input', updateCounter);
        }
    });

    // Sauvegarde automatique dans le localStorage
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form[action=\"process_solution.php\"]');
        if (form) {
            const problemId = document.querySelector('input[name=\"problem_id\"]').value;
            const storageKey = 'solution_draft_' + problemId;
            
            // Charger le brouillon s'il existe et si les champs sont vides
            const savedDraft = localStorage.getItem(storageKey);
            if (savedDraft) {
                try {
                    const draft = JSON.parse(savedDraft);
                    const codeField = document.getElementById('solution_code');
                    const explanationField = document.getElementById('explanation');
                    const priceField = document.getElementById('price');
                    
                    if (!codeField.value && draft.code) {
                        codeField.value = draft.code;
                    }
                    if (!explanationField.value && draft.explanation) {
                        explanationField.value = draft.explanation;
                    }
                    if (!priceField.value && draft.price) {
                        priceField.value = draft.price;
                    }
                    
                    // Ajuster les hauteurs des textareas
                    [codeField, explanationField].forEach(field => {
                        if (field.value) {
                            field.style.height = 'auto';
                            field.style.height = Math.max(field.scrollHeight, 120) + 'px';
                        }
                    });
                } catch (e) {
                    console.error('Erreur lors du chargement du brouillon:', e);
                }
            }
            
            // Sauvegarder automatiquement
            function saveDraft() {
                const draft = {
                    code: document.getElementById('solution_code').value,
                    explanation: document.getElementById('explanation').value,
                    price: document.getElementById('price').value,
                    timestamp: Date.now()
                };
                
                try {
                    localStorage.setItem(storageKey, JSON.stringify(draft));
                } catch (e) {
                    console.error('Erreur lors de la sauvegarde du brouillon:', e);
                }
            }
            
            // Sauvegarder toutes les 30 secondes
            let saveTimer;
            function scheduleSave() {
                clearTimeout(saveTimer);
                saveTimer = setTimeout(saveDraft, 30000);
            }
            
            // Écouter les changements
            ['solution_code', 'explanation', 'price'].forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.addEventListener('input', scheduleSave);
                    field.addEventListener('change', saveDraft);
                }
            });
            
            // Nettoyer le brouillon lors de la soumission réussie
            form.addEventListener('submit', function() {
                setTimeout(() => {
                    localStorage.removeItem(storageKey);
                }, 1000);
            });
        }
    });

    // Animation d'apparition
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('.solution-form');
        if (form) {
            form.style.opacity = '0';
            form.style.transform = 'translateY(20px)';
            setTimeout(() => {
                form.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                form.style.opacity = '1';
                form.style.transform = 'translateY(0)';
            }, 100);
        }
    });

    // Raccourcis clavier
    document.addEventListener('keydown', function(e) {
        // Ctrl+S pour sauvegarder le brouillon
        if (e.ctrlKey && e.key === 's') {
            e.preventDefault();
            const problemId = document.querySelector('input[name=\"problem_id\"]')?.value;
            if (problemId) {
                const storageKey = 'solution_draft_' + problemId;
                const draft = {
                    code: document.getElementById('solution_code')?.value || '',
                    explanation: document.getElementById('explanation')?.value || '',
                    price: document.getElementById('price')?.value || '',
                    timestamp: Date.now()
                };
                
                try {
                    localStorage.setItem(storageKey, JSON.stringify(draft));
                    showMessage('Brouillon sauvegardé!', 'success');
                } catch (error) {
                    showMessage('Erreur lors de la sauvegarde', 'error');
                }
            }
        }
        
        // Ctrl+Enter pour soumettre
        if (e.ctrlKey && e.key === 'Enter') {
            const form = document.querySelector('form[action=\"process_solution.php\"]');
            if (form) {
                form.requestSubmit();
            }
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
";

// Inclure le pied de page
include 'footer.php';
?>

