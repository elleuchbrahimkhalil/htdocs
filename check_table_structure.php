<?php
// Démarrer la session
session_start();
require_once 'db_connect.php';

// Connect to the database
$pdo = connect();

if (!$pdo) {
    die("Erreur de connexion à la base de données");
}

// Function to get table structure
function getTableStructure($pdo, $tableName) {
    $query = $pdo->prepare("
        SELECT COLUMN_NAME, DATA_TYPE 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = ?
    ");
    $query->execute([$tableName]);
    return $query->fetchAll(PDO::FETCH_ASSOC);
}

$problemsStructure = getTableStructure($pdo, 'problems'); // Added to check problems table structure
// Check the structure of the solutions and user_corrections tables
$solutionsStructure = getTableStructure($pdo, 'solutions');
$userCorrectionsStructure = getTableStructure($pdo, 'user_corrections');


// Display the results
echo "<h1>Structure of the 'solutions' Table</h1>";
foreach ($solutionsStructure as $column) {
    echo "<p>" . htmlspecialchars($column['COLUMN_NAME']) . " - " . htmlspecialchars($column['DATA_TYPE']) . "</p>";
}

echo "<h1>Structure of the 'problems' Table</h1>"; // Added to display problems table structure
foreach ($problemsStructure as $column) {
    echo "<p>" . htmlspecialchars($column['COLUMN_NAME']) . " - " . htmlspecialchars($column['DATA_TYPE']) . "</p>";
}
echo "<h1>Structure of the 'user_corrections' Table</h1>";

foreach ($userCorrectionsStructure as $column) {
    echo "<p>" . htmlspecialchars($column['COLUMN_NAME']) . " - " . htmlspecialchars($column['DATA_TYPE']) . "</p>";
}
?>
