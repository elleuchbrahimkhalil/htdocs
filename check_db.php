<?php
require_once 'db_connect.php';

try {
    $pdo = connect();
    echo "Connexion à la base de données réussie<br>";
    
    // Vérifier la table problems
    $stmt = $pdo->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'problems'");

    echo "<h3>Structure de la table problems:</h3>";
    echo "<pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    echo "</pre>";
    
    // Vérifier la table users
    $stmt = $pdo->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'users'");

    echo "<h3>Structure de la table users:</h3>";
    echo "<pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    echo "</pre>";
    
    // Vérifier la table solutions
    $stmt = $pdo->query("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'solutions'");

    echo "<h3>Structure de la table solutions:</h3>";
    echo "<pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    echo "</pre>";
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}
