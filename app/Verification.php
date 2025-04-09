<?php
namespace App;

use PDOException;

class Verification {
    public static function loginUser($username, $password) {
        $pdo = connect();
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                return true; // Login successful
            } else {
                return false; // Invalid credentials
            }
        } catch (PDOException $e) {
            error_log('Erreur de connexion: ' . $e->getMessage());
            return false;
        }
    }


    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public static function registerUser($username, $password, $name, $email) {
        $pdo = connect();
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, password, name, email) VALUES (?, ?, ?, ?)");
            $result = $stmt->execute([$username, $hashedPassword, $name, $email]);
            
            return $result; // Return true if successful, false otherwise
        } catch (PDOException $e) {
            error_log('Erreur d\'inscription: ' . $e->getMessage());
            return false;
        }
    }

    public static function checkUser($userId) {
        // Logic to verify user
        // This is a placeholder for actual verification logic
        return true; // Assume verification is successful for now
    }
}
?>
