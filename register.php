<?php
require_once 'db_connect.php';

// Initialiser les variables
$name = '';
$username = '';
$email = '';
$password = '';
$verify_password = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $verify_password = isset($_POST['verify_password']) ? $_POST['verify_password'] : '';

    // Validation des champs obligatoires
    if (empty($name)) {
        $errors[] = "Le nom est obligatoire";
    }

    if (empty($username)) {
        $errors[] = "Le nom d'utilisateur est obligatoire";
    }

    // Add more validation rules for email and password

    // Si pas d'erreurs, procéder à l'enregistrement
    if (empty($errors)) {
        $pdo = connect();

        if ($pdo) {
            try {
                // Vérifier si l'email existe déjà
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
                $stmt->execute([':email' => $email]);
                $emailExists = $stmt->fetch();

                // Vérifier si le nom d'utilisateur existe déjà
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
                $stmt->execute([':username' => $username]);
                $usernameExists = $stmt->fetch();

                if ($emailExists) {
                    $errors[] = "Cet email est déjà utilisé";
                } elseif ($usernameExists) {
                    $errors[] = "Ce nom d'utilisateur est déjà utilisé";
                } else {
                    // Hachage du mot de passe
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                    // Préparation de la requête SQL
                    $stmt = $pdo->prepare("INSERT INTO users (name, username, email, password) VALUES (:name, :username, :email, :password)");

                    // Liaison des paramètres
                    $params = [
                        ':name' => $name,
                        ':username' => $username,
                        ':email' => $email,
                        ':password' => $hashedPassword
                    ];

                    // Exécution de la requête
                    $stmt->execute($params);

                    // Redirection vers la page de connexion
                    header("Location: login.php?registered=true");
                    exit;
                }
            } catch (PDOException $e) {
                $errors[] = "Échec de l'enregistrement: " . $e->getMessage();
                error_log("Erreur PDO lors de l'insertion: " . $e->getMessage());
            }
        } else {
            $errors[] = "Échec de la connexion à la base de données";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .container {
            width: 100%;
            max-width: 450px;
            padding: 20px;
        }

        h2 {
            text-align: center;
            color: #2c3e50;
            margin-bottom: 30px;
            font-size: 32px;
            font-weight: 600;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        .error {
            background-color: #ffebee;
            color: #d32f2f;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 14px;
            border-left: 4px solid #d32f2f;
        }

        .error p {
            margin: 5px 0;
        }

        form {
            background-color: white;
            padding: 35px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        form:hover {
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.15);
        }

        .form-group {
            margin-bottom: 22px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #34495e;
            font-size: 15px;
        }

        input[type="text"], 
        input[type="email"], 
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 15px;
            transition: all 0.3s;
            box-sizing: border-box;
            background-color: #f9f9f9;
        }

        input[type="text"]:focus, 
        input[type="email"]:focus, 
        input[type="password"]:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
            outline: none;
            background-color: #fff;
        }

        button[type="submit"] {
            width: 100%;
            background: linear-gradient(to right, #3498db, #2980b9);
            color: white;
            border: none;
            padding: 14px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
            margin-top: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        button[type="submit"]:hover {
            background: linear-gradient(to right, #2980b9, #2c3e50);
            transform: translateY(-2px);
            box-shadow: 0 6px 8px rgba(0, 0, 0, 0.15);
        }

        p {
            text-align: center;
            margin-top: 25px;
            color: #7f8c8d;
            font-size: 15px;
        }

        a {
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        a:hover {
            color: #2980b9;
            text-decoration: underline;
        }

        /* Animation pour les champs de formulaire */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .form-group {
            animation: fadeIn 0.5s ease-out forwards;
            opacity: 0;
        }

        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        .form-group:nth-child(3) { animation-delay: 0.3s; }
        .form-group:nth-child(4) { animation-delay: 0.4s; }
        .form-group:nth-child(5) { animation-delay: 0.5s; }
        .form-group:nth-child(6) { animation-delay: 0.6s; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Inscription</h2>

        <?php if (!empty($errors)): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="name">Nom complet:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
            </div>

            <div class="form-group">
                <label for="username">Nom d'utilisateur:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Mot de passe:</label>
                <input type="password" id="password" name="password" required>
            </div>

            <div class="form-group">
                <label for="verify_password">Confirmer le mot de passe:</label>
                <input type="password" id="verify_password" name="verify_password" required>
            </div>

            <div class="form-group">
                <button type="submit">S'inscrire</button>
            </div>
        </form>

        <p>Déjà inscrit? <a href="login.php">Se connecter</a></p>
    </div>
</body>
</html>
