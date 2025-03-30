<?php
require 'db_connect.php';

$pdo = connect();
if ($pdo) {
    $query = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_NAME = 'favorites'";
    $stmt = $pdo->query($query);
    $tableExists = $stmt->fetch();

    if ($tableExists) {
        echo "The 'favorites' table exists in the database.";
    } else {
    echo "The 'favorites' table does not exist in the database. Please ensure it is created.";

    }
} else {
    echo "Failed to connect to the database.";
}
?>
