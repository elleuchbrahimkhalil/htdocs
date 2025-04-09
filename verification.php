<?php
// Vérifier si une session est déjà active avant de démarrer une nouvelle session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure le fichier de connexion à la base de données
require_once 'db_connect.php';

// Initialiser la connexion à la base de données
$pdo = connect();

// Fonction pour vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Fonction pour récupérer les informations de l'utilisateur connecté
function getCurrentUser() {
    global $pdo;
    
    if (!isLoggedIn()) {
        return null;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id, username, name, email, score, problems_solved, problems_posted, earnings, avatar_url FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user) {
            return $user;
        }
        
        // Si l'utilisateur n'existe pas dans la base de données, déconnexion
        session_unset();
        session_destroy();
        return null;
    } catch (PDOException $e) {
        // Gérer l'erreur de base de données
        error_log('Erreur de base de données: ' . $e->getMessage());
        return null;
    }
}

// Fonction pour authentifier un utilisateur
function loginUser($username, $password) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Authentification réussie
            $_SESSION['user_id'] = $user['id'];
            return true;
        }
        
        return false;
    } catch (PDOException $e) {
        error_log('Erreur de base de données: ' . $e->getMessage());
        return false;
    }
}

// Fonction pour enregistrer un nouvel utilisateur
function registerUser($username, $password, $name, $email) {
    global $pdo; // Utiliser la connexion globale comme les autres fonctions
    
    // Hachage du mot de passe pour sécurité
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        // Préparer la requête d'insertion - noter "users" au lieu de "user"
        $stmt = $pdo->prepare("INSERT INTO users (username, password, name, email) VALUES (?, ?, ?, ?)");
        
        // Exécuter la requête avec les paramètres
        $result = $stmt->execute([$username, $hashedPassword, $name, $email]);
        
        if ($result) {
            return true; // Inscription réussie
        } else {
            return false; // Échec de l'inscription
        }
    } catch (PDOException $e) {
        // Gérer l'erreur (par exemple, si le nom d'utilisateur existe déjà)
        error_log("Erreur d'inscription : " . $e->getMessage());
        return false;
    }
}

// Fonction pour mettre à jour les statistiques de l'utilisateur
function updateUserStats($userId, $field, $value) {
    global $pdo;
    
    $allowedFields = ['score', 'problems_solved', 'problems_posted', 'earnings'];
    
    if (!in_array($field, $allowedFields)) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE users SET $field = $field + ? WHERE id = ?");
        $stmt->execute([$value, $userId]);
        return true;
    } catch (PDOException $e) {
        error_log('Erreur de base de données: ' . $e->getMessage());
        return false;
    }
}

/**
 * Redirige l'utilisateur vers la page de login s'il n'est pas connecté
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: exlogin.php");
        exit;
    }
}

/**
 * Déconnecte l'utilisateur
 */
function logoutUser() {
    // Détruire toutes les variables de session
    $_SESSION = array();
    
    // Détruire la session
    session_destroy();
}
?>
