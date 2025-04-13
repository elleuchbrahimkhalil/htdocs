<?php
// Set page title
$page_title = "Changer mon nom";

// Additional CSS specific to this page
$additional_css = "
    .form-container {
        max-width: 500px;
        margin: 40px auto;
        background: white;
        padding: 30px;
        border-radius: 10px;
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

    input[type=\"text\"] {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 16px;
    }

    input[type=\"text\"]:focus {
        border-color: #3498db;
        box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
        outline: none;
    }

    .btn-submit {
        background: linear-gradient(135deg, #3498db, #2980b9);
        color: white;
        border: none;
        padding: 12px 20px;
        border-radius: 6px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        width: 100%;
    }

    .btn-submit:hover {
        background: linear-gradient(135deg, #2980b9, #3498db);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .current-name {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 6px;
        margin-bottom: 20px;
        border-left: 3px solid #3498db;
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
$current_name = '';
$success_message = '';
$error_message = '';

try {
    $pdo = connect();
    
    if (!$pdo) {
        throw new Exception("Erreur de connexion à la base de données");
    }
    
    // Traitement du formulaire si soumis
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $new_name = isset($_POST['new_name']) ? trim($_POST['new_name']) : '';
        
        // Validation
        if (empty($new_name)) {
            $error_message = "Le nom ne peut pas être vide.";
        } elseif (strlen($new_name) < 2) {
            $error_message = "Le nom doit contenir au moins 2 caractères.";
        } elseif (strlen($new_name) > 50) {
            $error_message = "Le nom ne peut pas dépasser 50 caractères.";
        } else {
            // Mettre à jour le nom dans la base de données
            $stmt = $pdo->prepare("UPDATE users SET name = ? WHERE id = ?");
            $result = $stmt->execute([$new_name, $user_id]);
            
            if ($result) {
                $success_message = "Votre nom a été mis à jour avec succès.";
                // Mettre à jour la session
                $_SESSION['user_name'] = $new_name;
            } else {
                $error_message = "Une erreur est survenue lors de la mise à jour de votre nom.";
            }
        }
    }
    
    // Récupérer le nom actuel de l'utilisateur
    $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        $current_name = $user['name'];
    }
    
} catch (Exception $e) {
    error_log("Erreur lors de la gestion du changement de nom: " . $e->getMessage());
    $error_message = "Une erreur est survenue lors de la communication avec la base de données.";
}
?>

<h1><i class="fas fa-user-edit"></i> Changer mon nom</h1>

<div class="form-container">
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
    
    <div class="current-name">
        <p><strong>Nom actuel:</strong> <?php echo htmlspecialchars($current_name); ?></p>
    </div>
    
    <form method="POST" action="">
        <div class="form-group">
            <label for="new_name">Nouveau nom</label>
            <input type="text" id="new_name" name="new_name" required 
                   placeholder="Entrez votre nouveau nom" 
                   value="<?php echo isset($_POST['new_name']) ? htmlspecialchars($_POST['new_name']) : ''; ?>">
        </div>
        
        <button type="submit" class="btn-submit">
            <i class="fas fa-save"></i> Enregistrer les modifications
        </button>
    </form>
</div>

<?php
// Additional scripts
$additional_scripts = "
    // Animation pour le bouton
    document.querySelector('.btn-submit').addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-2px)';
        this.style.boxShadow = '0 4px 8px rgba(0,0,0,0.1)';
    });
    
    document.querySelector('.btn-submit').addEventListener('mouseleave', function() {
        this.style.transform = '';
        this.style.boxShadow = '';
    });
";

// Include footer
include 'footer.php';
?>
