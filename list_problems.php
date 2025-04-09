<?php
// Démarrer la session
session_start();
require_once 'db_connect.php';

// Connect to the database
$pdo = connect();

error_log("Tentative de récupération des problèmes."); // Log attempt to retrieve problems

if (!$pdo) {
    die("Erreur de connexion à la base de données");
}

// List all problems in the database
$query = $pdo->query("SELECT * FROM problems");
$problems = $query->fetchAll(PDO::FETCH_ASSOC);

echo "<h1>Problems in the Database</h1>";
if ($problems) {
    foreach ($problems as $problem) {
        echo "<p>ID: " . htmlspecialchars($problem['problem_id']) . " - Title: " . htmlspecialchars($problem['title']) . "</p>";
    }
error_log("Requête exécutée, nombre de résultats: " . count($problems)); // Log number of results
    echo "<p>Aucun problème trouvé dans la base de données.</p>";
}
?>
