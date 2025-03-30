<?php
session_start();
require_once 'verification.php';
require_once 'db_connect.php';

// Vérifier si l'utilisateur est connecté
if (!isLoggedIn()) {
    echo "Vous devez être connecté pour accéder à cette page.";
    exit;
}

$user_id = $_SESSION['user_id'];
echo "<h1>Diagnostic des favoris pour l'utilisateur ID: $user_id</h1>";

try {
    $pdo = connect();
    
    // 1. Vérifier les favoris de l'utilisateur
    $sql = "
        SELECT p.*, u.username, u.avatar_url,
               EXISTS (
                   SELECT 1 FROM user_correction 
                   WHERE user_correction.problem_id = p.problem_id 
                   AND user_correction.user_id = ?
               ) AS is_solved_by_me
        FROM favorites f
        JOIN problems p ON f.problem_id = p.problem_id
        JOIN users u ON p.user_id = u.id
        WHERE f.user_id = ?
        ORDER BY f.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $problems_per_page = 10; // Example value, adjust as needed
    $offset = 0; // Example value, adjust as needed
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $user_id, $problems_per_page, $offset]);
    $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Favoris trouvés dans la base de données: " . count($favorites) . "</h2>";
    
    if (count($favorites) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Problem ID</th><th>Created At</th><th>Avatar</th><th>Is Solved By Me</th></tr>";
        
        foreach ($favorites as $fav) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($fav['id']) . "</td>";
            echo "<td>" . htmlspecialchars($fav['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($fav['problem_id']) . "</td>";
            echo "<td>" . htmlspecialchars($fav['created_at']) . "</td>";
            echo "<td><img src='" . htmlspecialchars($fav['avatar_url']) . "' alt='Avatar' width='50' height='50'></td>";
            echo "<td>" . ($fav['is_solved_by_me'] ? "Oui" : "Non") . "</td>"; // Display if solved by user
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    // Additional logic for checking corresponding problems and solutions can be added here...
    
} catch (Exception $e) {
    echo "<h2>Erreur:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
