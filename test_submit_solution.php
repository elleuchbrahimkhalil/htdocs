<?php
// Simulate form submission to process_solution.php
session_start();
$_SESSION['user_id'] = 1; // Simulate a logged-in user

// Simulate POST data
$_POST['problem_id'] = 17; // Use a valid problem ID
$_POST['solution_code'] = 'print("Hello, World!");'; // Example solution code
$_POST['explanation'] = 'This is a simple hello world solution.'; // Example explanation

// Set the request method to POST
$_SERVER['REQUEST_METHOD'] = 'POST';

// Include the process_solution.php file to handle the submission
require_once 'process_solution.php';
