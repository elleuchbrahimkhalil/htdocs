<?php
// Démarrer la session
session_start();
require_once 'db_connect.php';

// Connect to the database
$pdo = connect();

if (!$pdo) {
    die("Erreur de connexion à la base de données");
}

// SQL to create the user_problem_status table
$sql = "
CREATE TABLE user_problem_status (
    id INT IDENTITY(1,1) PRIMARY KEY,
    problem_id INT NOT NULL,
    user_id INT NOT NULL,
    status VARCHAR(20) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT unique_status UNIQUE (problem_id, user_id)
);
";

try {
    $pdo->exec($sql);
    echo "Table user_problem_status created successfully.";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>
