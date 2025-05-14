<?php
session_start();
header('Content-Type: application/json');

// Database connection
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $address = trim($_POST['address']);
    $contact_number = trim($_POST['contact_number']);
    $password = $_POST['password'];

    // Validation
    $errors = [];

    if (empty($full_name)) {
        $errors[] = "Full Name is required";
    }
    
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters long";
    }

    if (!$email) {
        $errors[] = "Valid email is required";
    }

    if (empty($address)) {
        $errors[] = "Address is required";
    }

    if (empty($contact_number)) {
        $errors[] = "Contact Number is required";
    }

    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long";
    }

    if (!empty($errors)) {
        echo json_encode(['success' => false, 'error' => implode(", ", $errors)]);
        exit();
    }

    try {
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            echo json_encode(['success' => false, 'error' => 'Username or email already exists']);
            $stmt->close();
            exit();
        }
        $stmt->close();

        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert new user
$stmt = $conn->prepare("INSERT INTO users (full_name, username, email, shipping_address, contact_number, password) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $full_name, $username, $email, $address, $contact_number, $hashed_password);
        
        if ($stmt->execute()) {
            $_SESSION['username'] = $username;
            echo json_encode(['success' => true, 'redirect' => 'dashboard.php']);
        } else {
            // Capture and throw a more specific database error
            $mysql_error = $stmt->error;
            error_log("Signup - SQL Execution Error: " . $mysql_error);
            throw new Exception("Database error during account creation: " . $mysql_error);
        }
        $stmt->close();

    } catch (Exception $e) {
        // Log the detailed exception message which now includes MySQL errors
        error_log("Signup - Exception: " . $e->getMessage());
        // Return the specific error message to the client
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }

    exit();
}

echo json_encode(['success' => false, 'error' => 'Invalid request method']);
?>