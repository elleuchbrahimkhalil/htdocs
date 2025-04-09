<?php
// Démarrer la session
session_start();
require_once 'db_connect.php';

// Connect to the database
$pdo = connect();

if (!$pdo) {
    die("Erreur de connexion à la base de données");
}

// List all tables in the database
$query = $pdo->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE'");
$tables = $query->fetchAll(PDO::FETCH_COLUMN);

echo "<h1>Tables in the Database</h1>";
if ($tables) {
    foreach ($tables as $table) {
        echo "<p>" . htmlspecialchars($table) . "</p>";
    }
} else {
    echo "<p>Aucune table trouvée dans la base de données.</p>";
}
?>
