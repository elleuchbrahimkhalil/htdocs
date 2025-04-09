<?php

/**
 * Établit une connexion à la base de données SQL Server
 * @return PDO Instance de connexion PDO
 */
function connect() {
    // Paramètres de connexion à SQL Server
    $host = 'BRAHIM'; // Ou '127.0.0.1,1433'
    $dbname = 'rcodephp';
    $username = ''; // Remplacez par l'utilisateur SQL Server correct
    $password = ''; // Si vous avez défini un mot de passe
    
    error_log("Attempting to connect to the database with host: $host, dbname: $dbname"); // Debugging line

    // Options PDO
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    // Chaîne de connexion DSN pour SQL Server
    $dsn = "sqlsrv:Server=$host;Database=$dbname"; 

    try {
        // Création de l'instance PDO
        $pdo = new PDO($dsn, $username, $password, $options);
        return $pdo;
    } catch (PDOException $e) {
        // En cas d'erreur de connexion
        error_log('Erreur de connexion à la base de données: ' . $e->getMessage()); // Log the error
        return null; // Return null instead of terminating the script
    }
}

// Exemple d'utilisation (vous pouvez commenter ou supprimer cette ligne si vous ne voulez pas de message à chaque inclusion)
//$pdo = connect();
// echo "Connexion réussie à la base de données SQL Server!";
