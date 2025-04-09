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
    <link rel="stylesheet" href="common.css">
    <style>
        .submit-container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }

        .form-header {
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f0f0;
        }

        .form-header h2 {
            color: #2c3e50;
            font-size: 24px;
            margin: 0;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .code-editor-container {
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
        }

        .CodeMirror {
            height: 400px;
            font-size: 15px;
        }

        .submit-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
        }

        .btn-submit {
            background: linear-gradient(to right, #4CAF50, #45a049);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.3);
        }

        .alert {
            margin-bottom: 20px;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="main-container">
        <div class="submit-container">
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

            <div class="form-header">
                <h2><i class="fas fa-code"></i> Soumettre votre solution</h2>
            </div>

            <form method="POST" action="process_solution.php" onsubmit="return validateForm()">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                
                <div class="form-group">
                    <div class="code-editor-container">
                        <textarea id="solution_code" name="solution_code" placeholder="Entrez votre code ici..."></textarea>
                    </div>
                </div>

                <div class="submit-actions">
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Soumettre la solution
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.2/codemirror.min.js"></script>
    <script>
        // Initialize CodeMirror
        var editor = CodeMirror.fromTextArea(document.getElementById("solution_code"), {
            lineNumbers: true,
            theme: "dracula",
            mode: "javascript",
            autoCloseBrackets: true,
            matchBrackets: true,
            indentUnit: 4,
            tabSize: 4,
            lineWrapping: true
        });
    </script>
</body>
</html>
