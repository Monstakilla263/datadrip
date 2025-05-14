<?php
// Database configuration
$host = "localhost";
$username = "root";
$password = ""; // Default XAMPP password is empty
$database = "user_auth";

// Create connection
$conn = new mysqli($host, $username, $password, $database);
if ($conn->connect_error) {
    $error = $conn->connect_error;
    error_log("Database connection error: " . $error);
    
    // For AJAX requests, return JSON error
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Database connection failed', 'details' => $error]);
        exit();
    }
    
    // For regular requests, show user-friendly message
    die("Sorry, there was a problem connecting to the database. Please try again later.");
}
?> 