<?php
// Démarrer la session si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
require_once 'verification.php';
require_once 'db_connect.php';

// Récupérer l'ID du problème depuis l'URL
$problem_id = isset($_GET['problem_id']) ? (int)$_GET['problem_id'] : 0;

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    header('Location: exlogin.php');
    exit;
}

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
    }

    .btn-cancel {
        background-color: transparent;
        color: #666;
        border: 1px solid #ddd;
    }

    .btn-cancel:hover {
        background-color: #f5f5f5;
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
if ($problem_id > 0) {
    try {
        $pdo = connect();
        $stmt = $pdo->prepare("SELECT * FROM problems WHERE problem_id = ?");
        $stmt->execute([$problem_id]);
        $problem = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Erreur lors de la récupération du problème: " . $e->getMessage());
    }
}

// Inclure l'en-tête
include 'header.php';
?>

<h1><i class="fas fa-paper-plane"></i> Soumettre une Solution</h1>

<?php if ($problem): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> Vous soumettez une solution pour: <strong><?php echo htmlspecialchars($problem['title']); ?></strong>
    </div>
<?php endif; ?>

<?php if (!empty($form_errors['general'])): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars(implode(', ', $form_errors['general'])); ?>
    </div>
<?php endif; ?>

<div class="solution-form">
    <form method="POST" action="process_solution.php">
        <input type="hidden" name="problem_id" value="<?php echo $problem_id; ?>">
        
        <div class="form-group">
            <label for="solution_code">Code de la solution</label>
            <div class="code-editor">
                <textarea id="solution_code" name="solution_code" placeholder="Entrez votre code ici..."><?php echo htmlspecialchars($old_solution_code); ?></textarea>
            </div>
            <?php if (!empty($form_errors['solution_code'])): ?>
                <div class="field-error"><?php echo htmlspecialchars(implode(', ', $form_errors['solution_code'])); ?></div>
            <?php endif; ?>
        </div>
        
        <div class="form-group">
            <label for="explanation">Explication de votre solution</label>
            <textarea id="explanation" name="explanation" placeholder="Expliquez votre approche et la logique derrière votre solution..."><?php echo htmlspecialchars($old_explanation); ?></textarea>
            <?php if (!empty($form_errors['explanation'])): ?>
                <div class="field-error"><?php echo htmlspecialchars(implode(', ', $form_errors['explanation'])); ?></div>
            <?php endif; ?>
        </div>
        
        <div class="price-container">
            <label for="price">Prix proposé</label>
            <p>Proposez un prix pour votre solution (en euros)</p>
            <input type="number" id="price" name="price" class="price-input" min="1" step="0.01" placeholder="0.00 €" value="<?php echo htmlspecialchars($old_price); ?>" required>
            <?php if (!empty($form_errors['price'])): ?>
                <div class="field-error"><?php echo htmlspecialchars(implode(', ', $form_errors['price'])); ?></div>
            <?php endif; ?>
        </div>
        
        <div class="form-footer">
            <a href="problem.php?id=<?php echo $problem_id; ?>" class="btn btn-cancel">
                <i class="fas fa-arrow-left"></i> Retour au problème
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-paper-plane"></i> Soumettre la solution
            </button>
        </div>
    </form>
</div>

<?php
// Scripts additionnels
$additional_scripts = "
    // Initialisation de CodeMirror
    var editor = CodeMirror.fromTextArea(document.getElementById('solution_code'), {
        lineNumbers: true,
        mode: 'javascript',
        theme: 'dracula',
        indentUnit: 4,
        autoCloseBrackets: true,
        matchBrackets: true,
        lineWrapping: true
    });
    
    // Ajuster la hauteur de l'éditeur
    editor.setSize(null, 300);
";

// Inclure le pied de page
include 'footer.php';
?>
