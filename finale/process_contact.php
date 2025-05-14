<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $user_id = null;
} else {
    $user_id = $_SESSION['user_id'];
}

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get form data
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    $created_at = date('Y-m-d H:i:s');

    // Insert message into database
    $query = "INSERT INTO messages (user_id, name, email, message, created_at) 
              VALUES (?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $query);
    // Handle null user_id by using 0
    $user_id = $user_id ?? 0;
    mysqli_stmt_bind_param($stmt, "issss", $user_id, $name, $email, $message, $created_at);
    
    if (mysqli_stmt_execute($stmt)) {
        // Set success message in session
        $_SESSION['message'] = "Your message has been sent successfully!";
        $_SESSION['message_type'] = "success";
    } else {
        // Set error message in session
        $_SESSION['message'] = "Error sending message. Please try again.";
        $_SESSION['message_type'] = "error";
    }

    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    // Redirect back to contact page
    header('Location: contact.php');
    exit();
} else {
    // If someone tries to access this file directly, redirect to contact page
    header('Location: contact.php');
    exit();
}
?> 