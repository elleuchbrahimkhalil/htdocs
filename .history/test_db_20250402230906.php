<?php
// Script simple pour tester la connexion à la base de données et afficher la structure des tables

require_once 'db_connect.php';

try {
    // Établir la connexion
    $pdo = connect();
    
    if (!$pdo) {
        die("La connexion à la base de données a échoué.");
    }
    
    echo "Connexion à la base de données réussie.\n\n";
    
    // Récupérer la liste des tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables disponibles dans la base de données:\n";
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    
    // Vérifier si la table 'problems' existe
    if (in_array('problems', $tables)) {
        echo "\nStructure de la table 'problems':\n";
        $columns = $pdo->query("DESCRIBE problems")->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $column) {
            echo "- {$column['Field']} ({$column['Type']})\n";
        }
        
        // Afficher quelques données
        echo "\nPremiers enregistrements de la table 'problems':\n";
        $problems = $pdo->query("SELECT * FROM problems LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
        print_r($problems);
    } else {
        echo "\nLa table 'problems' n'existe pas dans la base de données.\n";
    }
    
} catch (PDOException $e) {
    die("Erreur PDO: " . $e->getMessage());
} catch (Exception $e) {
    die("Erreur: " . $e->getMessage());
}
?>
