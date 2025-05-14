<?php
session_start();

// Always ensure user_id is set if username is available
if (isset($_SESSION['username']) && !isset($_SESSION['user_id'])) {
    require_once 'db_connection.php';
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $_SESSION['username']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $_SESSION['user_id'] = $row['id'];
        // Log successful user_id retrieval
        error_log("Set user_id in session: " . $row['id'] . " for username: " . $_SESSION['username']);
    } else {
        error_log("Failed to retrieve user_id for username: " . $_SESSION['username']);
    }
}

function checkUserSession() {
    // Check if user is logged in
    if (!isset($_SESSION['username']) || !isset($_SESSION['user_id'])) {
        // Try to get user_id from database if only username exists
        if (isset($_SESSION['username'])) {
            require_once 'db_connection.php';
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param("s", $_SESSION['username']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $_SESSION['user_id'] = $row['id'];
                error_log("Set user_id in checkUserSession: " . $row['id']);
            }
        }
        
        if (!isset($_SESSION['user_id'])) {
            header('Location: index.html');
            exit();
        }
    }
}

function checkAdminSession() {
    if (!isset($_SESSION['admin_username'])) {
        header('Location: index.html');
        exit();
    }
}
?> 