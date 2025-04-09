<?php
// Démarrer la session si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
require_once 'verification.php';

// Récupérer les messages de session
$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

$error_message = '';
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Génération du token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Soumettre une Solution'; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/theme/dracula.min.css">
    <link rel="stylesheet" href="common.css"> <!-- Ensure common styles are included -->
    <style>
        /* Additional styles to enhance the submission area */
        .content {
            padding: 40px; /* Increased padding for a larger submission area */
            background-color: #ffffff; /* White background for contrast */
            border-radius: 8px; /* Rounded corners */
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); /* Subtle shadow for depth */
        }
        .form-group {
            margin-bottom: 20px; /* Space between form elements */
        }
        .form-actions {
            margin-top: 20px; /* Space above the submit button */
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="main-container">
        <?php include 'sidebar.php'; ?> <!-- Only include the sidebar from the sidebar.php file -->

        <div class="content">
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

            <h2>Soumettre votre solution</h2>
            <form method="POST" action="process_solution.php" onsubmit="return validateForm()">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <div class="form-group">
                    <label for="solution_code">Votre solution</label>
                    <textarea id="solution_code" name="solution_code" placeholder="Entrez votre code ici..." class="form-control"></textarea>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Soumettre la solution</button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
