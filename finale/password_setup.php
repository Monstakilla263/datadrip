<?php
header('Content-Type: application/json');
require_once 'db_connection.php';

if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed', 'details' => $conn->connect_error]);
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error_log.txt');

function handle_error($errno, $errstr) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error', 'details' => $errstr]);
    exit();
}
set_error_handler('handle_error');

function handle_exception($exception) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error', 'details' => $exception->getMessage()]);
    exit();
}
set_exception_handler('handle_exception');

try {
    $username = 'admin';
    $password = 'admin21';
    $email = 'admin@datadrip.com';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Check if admin user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    $adminExists = $stmt->num_rows > 0;
    $stmt->close();

    if ($adminExists) {
        // Update password and email
        $stmt = $conn->prepare("UPDATE users SET password = ?, email = ? WHERE username = ?");
        if ($stmt === false) {
            throw new Exception("Failed to prepare update statement: " . $conn->error);
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'GET') {
            $stmt->bind_param("sss", $hashed_password, $email, $username);
            if (!$stmt->execute()) {
                throw new Exception("Failed to execute update: " . $stmt->error);
            }
        }
        $stmt->close();

        echo json_encode([
            'success' => true,
            'message' => 'Admin user updated successfully',
            'username' => $username,
            'password' => $password
        ]);
    } else {
        // Create admin user
        $stmt = $conn->prepare("INSERT INTO users (username, password, email) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hashed_password, $email);
        $stmt->execute();
        $user_id = $conn->insert_id;
        $stmt->close();

        // Create admin entry
        $first_name = 'Admin';
        $last_name = 'User';
        $stmt = $conn->prepare("INSERT INTO admins (user_id, first_name, last_name) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $user_id, $first_name, $last_name);
        $stmt->execute();
        $stmt->close();

        echo json_encode([
            'success' => true,
            'message' => 'Admin account created successfully',
            'username' => $username,
            'password' => $password
        ]);
    }

    // Close connection at the end of the script
    $conn->close();

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error', 'details' => $e->getMessage()]);
    exit();
}
?>
