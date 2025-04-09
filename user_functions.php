<?php
/**
 * Retrieves the current user's information from the database.
 * @return array The current user's information.
 */
function getCurrentUser() {
    $pdo = connect();
    session_start(); // Start the session to access session variables
    $userId = $_SESSION['user_id'] ?? null; // Get the user ID from the session

    if ($userId === null) {
        return null; // Return null if no user is logged in
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute(['id' => $userId]);
    return $stmt->fetch(PDO::FETCH_ASSOC); // Return the user's information
}

function getNextAvailableId() {
    $pdo = connect();
    $stmt = $pdo->query("SELECT MAX(id) AS max_id FROM users");
    $row = $stmt->fetch();
    return $row['max_id'] + 1; // Return the next available ID
}
?>
