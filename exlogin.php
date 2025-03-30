<?php
/* Inclure le script de vérification avec un chemin absolu pour être sûr
require_once __DIR__ . '/verification.php';

// Vérifier si la fonction existe avant de l'appeler
if (!function_exists('isLoggedIn')) {
    die("La fonction isLoggedIn n'existe pas. Vérifiez le fichier verification.php.");
}

// Rediriger si déjà connecté
if (isLoggedIn()) {
    header("Location: exacueil.php");
    exit;
}

// Vérifier si un cookie existe déjà
if(isset($_COOKIE["user_email"])) {
    // Vérifier si l'email du cookie correspond à un utilisateur valide
    $email = $_COOKIE["user_email"];
    
    // Rechercher l'utilisateur par email (sans vérifier le mot de passe)
    foreach ($users as $user) {
        if ($user['email'] === $email) {
            // Connecter l'utilisateur automatiquement
            loginUser($user);
            
            // Rediriger vers la page d'accueil
            header("Location: exacueil.php");
            exit;
        }
    }
    
    // Si on arrive ici, le cookie contient un email invalide, on le supprime
    setcookie("user_email", "", time() - 3600, "/");
}

// Message d'erreur
$error_message = '';

// Vérifier si le formulaire a été soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // Vérifier les identifiants
    $user = verifyUser($email, $password);
    
    if ($user) {
        // Connecter l'utilisateur
        loginUser($user);
        
        // Si "Remember me" est coché, créer un cookie
        if (isset($_POST['remember']) && $_POST['remember'] == 'on') {
            // Le cookie expirera dans 30 jours (30 * 24 * 60 * 60 secondes)
            $cookie_name = "user_email";
            $cookie_value = $email;
            $cookie_expiry = time() + (30 * 24 * 60 * 60);
            setcookie($cookie_name, $cookie_value, $cookie_expiry, "/");
        }
        
        // Rediriger vers la page d'accueil
        header("Location: exacueil.php");
        exit;
    } else {
        // Identifiants incorrects
        $error_message = "Email ou mot de passe incorrect.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            padding: 20px;
        }
        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 20px;
        }
        .login-form {
            background-color: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
        }
        .login-form:hover {
            transform: translateY(-5px);
        }
        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .form-header h2 {
            color: #333;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .form-header p {
            color: #777;
            font-size: 14px;
        }
        .form-group {
            margin-bottom: 20px;
            position: relative;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            padding-left: 40px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }
        .form-group input:focus {
            border-color: #764ba2;
            outline: none;
            box-shadow: 0 0 0 2px rgba(118, 75, 162, 0.2);
        }
        .form-group i {
            position: absolute;
            left: 15px;
            top: 40px;
            color: #aaa;
        }
        .login-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 6px;
            color: #fff;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
        }
        .login-btn:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(118, 75, 162, 0.4);
        }
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .remember-me {
            display: flex;
            align-items: center;
        }
        .remember-me input {
            margin-right: 5px;
        }
        .forgot-password {
            color: #764ba2;
            text-decoration: none;
        }
        .forgot-password:hover {
            text-decoration: underline;
        }
        .signup-link {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #555;
        }
        .signup-link a {
            color: #764ba2;
            text-decoration: none;
            font-weight: 500;
        }
        .signup-link a:hover {
            text-decoration: underline;
        }
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <form class="login-form" method="POST" action="">
            <div class="form-header">
                <h2>Welcome Back</h2>
                <p>Please login to your account</p>
            </div>
            
            <?php if (!empty($error_message)): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <i class="fas fa-envelope"></i>
                <input type="email" id="email" name="email" placeholder="Enter your email" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <i class="fas fa-lock"></i>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            
            <div class="remember-forgot">
                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me</label>
                </div>
                <a href="#" class="forgot-password">Forgot password?</a>
            </div>
            
            <button type="submit" class="login-btn">Login</button>
            
            <div class="signup-link">
                Don't have an account? <a href="#">Sign up</a>
            </div>
        </form>
    </div>
</body>
</html>
*/