<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Diagnostic du système</h1>";

// Vérifier la version de PHP
echo "<h2>Version PHP</h2>";
echo "Version PHP: " . phpversion();

// Vérifier les extensions PDO
echo "<h2>Extensions PDO</h2>";
$pdo_drivers = PDO::getAvailableDrivers();
echo "Drivers PDO disponibles: " . implode(", ", $pdo_drivers);

// Tester la connexion à la base de données
echo "<h2>Test de connexion à la base de données</h2>";
require_once 'db_connect.php';

try {
    $pdo = connect();
    if ($pdo) {
        echo "<p style='color:green'>Connexion réussie à la base de données!</p>";
        
        // Vérifier les tables
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Tables dans la base de données: " . implode(", ", $tables) . "</p>";
        
        // Vérifier la structure de la table solutions
        if (in_array('solutions', $tables)) {
            echo "<h3>Structure de la table 'solutions':</h3>";
            echo "<pre>";
            $columns = $pdo->query("DESCRIBE solutions")->fetchAll(PDO::FETCH_ASSOC);
            print_r($columns);
            echo "</pre>";
        } else {
            echo "<p style='color:red'>La table 'solutions' n'existe pas!</p>";
        }
    } else {
        echo "<p style='color:red'>Échec de la connexion à la base de données.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Erreur: " . $e->getMessage() . "</p>";
}

// Vérifier les sessions
echo "<h2>Sessions</h2>";
echo "Session status: " . session_status() . "<br>";
echo "Session active: " . (session_status() === PHP_SESSION_ACTIVE ? "Oui" : "Non") . "<br>";

if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<h3>Variables de session:</h3>";
    echo "<pre>";
    print_r($_SESSION);
    echo "</pre>";
}

// Vérifier les permissions sur les fichiers
echo "<h2>Permissions de fichiers</h2>";
$files_to_check = [
    'db_connect.php',
    'user_feedback.php',
    'submit_solution.php',
    'process_solution.php'
];

foreach ($files_to_check as $file) {
    echo "$file: ";
    if (file_exists($file)) {
        echo "Existe, ";
        echo "Permissions: " . substr(sprintf('%o', fileperms($file)), -4);
    } else {
        echo "<span style='color:red'>N'existe pas</span>";
    }
    echo "<br>";
}
?>
