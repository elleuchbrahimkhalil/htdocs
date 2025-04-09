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

// Récupérer l'ID de la solution depuis l'URL
$solution_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($solution_id <= 0) {
    $_SESSION['error_message'] = "ID de solution invalide.";
    header('Location: dashboard.php');
    exit;
}

// Récupérer l'ID de l'utilisateur connecté
$user_id = $_SESSION['user_id'];

// Initialiser les variables
$solution = null;
$problem = null;
$error_message = '';
$success_message = '';

try {
    $pdo = connect();
    
    // Récupérer la solution et vérifier qu'elle appartient à l'utilisateur connecté
    $stmt = $pdo->prepare("
        SELECT s.*, p.title as problem_title, p.description as problem_description
        FROM solutions s
        JOIN problems p ON s.problem_id = p.problem_id
        WHERE s.id = ? AND s.user_id = ?
    ");
    
    $stmt->execute([$solution_id, $user_id]);
    $solution = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$solution) {
        $_SESSION['error_message'] = "Solution introuvable ou vous n'êtes pas autorisé à la modifier.";
        header('Location: dashboard.php');
        exit;
    }
    
    // Traiter le formulaire de modification
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $solution_code = trim($_POST['solution_code']);
        $explanation = trim($_POST['explanation']);
        $price = isset($_POST['price']) ? (float)$_POST['price'] : 0.00;
        
        // Validation
        $errors = [];
        
        if (empty($solution_code)) {
            $errors[] = "Le code de la solution est requis.";
        }
        
        if (empty($explanation)) {
            $errors[] = "L'explication est requise.";
        }
        
        if ($price <= 0) {
            $errors[] = "Le prix doit être supérieur à zéro.";
        }
        
        if (empty($errors)) {
            // Mettre à jour la solution
            $stmt = $pdo->prepare("
                UPDATE solutions 
                SET solution_code = ?, explanation = ?, price = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ? AND user_id = ?
            ");
            
            $result = $stmt->execute([$solution_code, $explanation, $price, $solution_id, $user_id]);
            
            if ($result) {
                // Récupérer l'ID du propriétaire du problème
                $stmt = $pdo->prepare("SELECT user_id FROM problems WHERE problem_id = ?");
                $stmt->execute([$solution['problem_id']]);
                $problem_owner_id = $stmt->fetchColumn();
                
                // Créer une notification
                $stmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, type, content, related_id, created_at)
                    VALUES (?, 'solution_updated', ?, ?, CURRENT_TIMESTAMP)
                ");
                
                $notification_content = "Une solution pour votre problème \"" . $solution['problem_title'] . "\" a été mise à jour.";
                $stmt->execute([$problem_owner_id, $notification_content, $solution_id]);
                
                $success_message = "Votre solution a été mise à jour avec succès.";
                
                // Récupérer la solution mise à jour
                $stmt = $pdo->prepare("
                    SELECT s.*, p.title as problem_title, p.description as problem_description
                    FROM solutions s
                    JOIN problems p ON s.problem_id = p.problem_id
                    WHERE s.id = ?
                ");
                
                $stmt->execute([$solution_id]);
                $solution = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error_message = "Une erreur est survenue lors de la mise à jour de la solution.";
            }
        } else {
            $error_message = implode("<br>", $errors);
        }
    }
    
} catch (Exception $e) {
    error_log("Erreur lors de la récupération/modification de la solution: " . $e->getMessage());
    $error_message = "Une erreur est survenue lors de la récupération ou de la modification de la solution.";
}

// Définir le titre de la page
$page_title = "Modifier ma Solution";

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

    .problem-details {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        border-left: 3px solid #3498db;
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

// Inclure l'en-tête
include 'header.php';
?>

<h1><i class="fas fa-edit"></i> Modifier ma Solution</h1>

<?php if (!empty($error_message)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
    </div>
<?php endif; ?>

<?php if (!empty($success_message)): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
    </div>
<?php endif; ?>

<div class="solution-form">
    <div class="problem-details">
        <h3><?php echo htmlspecialchars($solution['problem_title']); ?></h3>
        <p><?php echo nl2br(htmlspecialchars($solution['problem_description'])); ?></p>
    </div>

    <form method="POST" action="">
        <div class="form-group">
            <label for="solution_code">Code de la solution</label>
            <div class="code-editor">
                <textarea id="solution_code" name="solution_code" placeholder="Entrez votre code ici..."><?php echo htmlspecialchars($solution['solution_code']); ?></textarea>
            </div>
        </div>
        
        <div class="form-group">
            <label for="explanation">Explication de votre solution</label>
            <textarea id="explanation" name="explanation" placeholder="Expliquez votre approche et la logique derrière votre solution..."><?php echo htmlspecialchars($solution['explanation']); ?></textarea>
        </div>
        
        <div class="price-container">
            <label for="price">Prix proposé</label>
            <p>Proposez un prix pour votre solution (en euros)</p>
            <input type="number" id="price" name="price" class="price-input" min="1" step="0.01" placeholder="0.00 €" value="<?php echo htmlspecialchars($solution['price']); ?>" required>
        </div>
        
        <div class="form-footer">
            <a href="dashboard.php" class="btn btn-cancel">
                <i class="fas fa-arrow-left"></i> Annuler
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Enregistrer les modifications
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
