<?php
// Démarrer la session
session_start();
require_once 'db_connect.php';

// Simulate a logged-in user
$_SESSION['user_id'] = 1; // Example user ID
$problem_id = 1; // Example problem ID

// Connect to the database
$pdo = connect();

if (!$pdo) {
    die("Erreur de connexion à la base de données");
}

// Check the solutions for the specific problem and user
$stmt = $pdo->prepare("
    SELECT * FROM solutions 
    WHERE problem_id = ? AND user_id = ?
");
$stmt->execute([$problem_id, $_SESSION['user_id']]);
$solutions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check the user's problem status
$status_stmt = $pdo->prepare("
    SELECT * FROM user_problem_status 
    WHERE problem_id = ? AND user_id = ?
");
$status_stmt->execute([$problem_id, $_SESSION['user_id']]);
$status = $status_stmt->fetch(PDO::FETCH_ASSOC);

// Display the results
echo "<h1>Solutions for Problem ID: $problem_id</h1>";
foreach ($solutions as $solution) {
    echo "<p>Solution Code: " . htmlspecialchars($solution['solution_code']) . "</p>";
    echo "<p>Explanation: " . htmlspecialchars($solution['explanation']) . "</p>";
    echo "<p>Status: " . htmlspecialchars($solution['status']) . "</p>";
}

echo "<h1>User Problem Status</h1>";
if ($status) {
    echo "<p>Status: " . htmlspecialchars($status['status']) . "</p>";
} else {
    echo "<p>No status found for this user and problem.</p>";
}
?>
