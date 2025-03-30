<?php
// Inclure les fichiers nécessaires
require_once 'verification.php';
require_once 'db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    echo "Vous devez être connecté pour télécharger un avatar.";
    exit;
}

// Récupérer les informations de l'utilisateur
$user = getCurrentUser();
if (!$user) {
    echo "Impossible de récupérer les informations de l'utilisateur.";
    exit;
}

$message = '';
$success = false;

// Traiter le formulaire d'upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];
    
    // Vérifier s'il y a des erreurs
    if ($file['error'] === 0) {
        // Vérifier le type de fichier
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($file['type'], $allowedTypes)) {
            // Créer le répertoire d'upload s'il n'existe pas
            $uploadDir = 'uploads/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Générer un nom de fichier unique
            $fileName = uniqid('avatar_') . '_' . $user['id'] . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
            $uploadFile = $uploadDir . $fileName;
            
            // Déplacer le fichier téléchargé
            if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
                // Mettre à jour l'URL de l'avatar dans la base de données
                $db = connect();
                if ($db) {
                    $query = "UPDATE users SET avatar_url = ? WHERE id = ?";
                    $stmt = $db->prepare($query);
                    $stmt->execute([$uploadFile, $user['id']]);
                    
                    $message = "Avatar téléchargé avec succès!";
                    $success = true;
                    
                    // Script pour rafraîchir la page parent
                    echo "<script>window.parent.location.reload();</script>";
                } else {
                    $message = "Erreur de connexion à la base de données.";
                }
            } else {
                $message = "Erreur lors du téléchargement du fichier.";
            }
        } else {
            $message = "Type de fichier non autorisé. Veuillez télécharger une image (JPEG, PNG ou GIF).";
        }
    } else {
        $message = "Erreur lors du téléchargement: " . $file['error'];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Télécharger un avatar</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            color: #333;
        }
        h2 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        .upload-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="file"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background: #3498db;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }
        button:hover {
            background: #2980b9;
        }
        .message {
            padding: 10px;
            margin-top: 15px;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .preview {
            margin-top: 20px;
            text-align: center;
        }
        .preview img {
            max-width: 150px;
            max-height: 150px;
            border-radius: 50%;
            border: 3px solid #3498db;
        }
    </style>
</head>
<body>
    <h2>Télécharger un nouvel avatar</h2>
    
    <?php if ($message): ?>
        <div class="message <?php echo $success ? 'success' : 'error'; ?>">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    
    <div class="upload-form">
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="avatar">Sélectionner une image:</label>
                <input type="file" id="avatar" name="avatar" accept="image/*" onchange="previewImage(this)">
            </div>
            
            <div class="preview" id="imagePreview">
                <!-- L'aperçu de l'image sera affiché ici -->
            </div>
            
            <button type="submit">Télécharger</button>
        </form>
    </div>
    
    <script>
        function previewImage(input) {
            var preview = document.getElementById('imagePreview');
            preview.innerHTML = '';
            
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                
                reader.onload = function(e) {
                    var img = document.createElement('img');
                    img.src = e.target.result;
                    preview.appendChild(img);
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>
</html>
