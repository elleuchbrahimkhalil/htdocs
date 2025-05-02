<?php
require 'db_connect.php';

$pdo = connect();
if ($pdo) {
    // Check table structure
    $query = "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'favorites'";
    $stmt = $pdo->query($query);
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Favorites Table Structure:</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Column Name</th><th>Data Type</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['COLUMN_NAME']) . "</td>";
        echo "<td>" . htmlspecialchars($column['DATA_TYPE']) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Check for sample data
    $query = "SELECT TOP 5 * FROM favorites";
    $stmt = $pdo->query($query);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Sample Data (up to 5 rows):</h2>";
    
    if (count($rows) > 0) {
        echo "<table border='1'>";
        echo "<tr>";
        foreach (array_keys($rows[0]) as $key) {
            echo "<th>" . htmlspecialchars($key) . "</th>";
        }
        echo "</tr>";
        
        foreach ($rows as $row) {
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No data found in the favorites table.</p>";
    }
} else {
    echo "Failed to connect to the database.";
}
?>
