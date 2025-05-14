<?php
session_start();
header('Content-Type: application/json');

// Database connection
require_once 'db_connection.php';

// Enable error reporting (for development — disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize response
$response = [
    'success' => false,
    'error' => ''
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['error'] = 'Invalid request method';
    echo json_encode($response);
    exit();
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

// Check for empty input
if (empty($username) || empty($password)) {
    $response['error'] = 'Username and password are required';
    echo json_encode($response);
    exit();
}

try {
    // First check if the user exists
    $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    if (!$user) {
        $response['error'] = 'User not found';
        echo json_encode($response);
        exit();
    }

    // Verify password
    if (!password_verify($password, $user['password'])) {
        $response['error'] = 'Invalid password';
        echo json_encode($response);
        exit();
    }

    // Check if user is admin
    $stmt = $conn->prepare("SELECT user_id FROM admins WHERE user_id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $isAdmin = $result->num_rows > 0;
    $stmt->close();

    if (!$isAdmin) {
        $response['error'] = 'User is not an admin';
        echo json_encode($response);
        exit();
    }

    // Success: set session and respond
    $_SESSION['admin_username'] = $user['username'];
    $_SESSION['admin_id'] = $user['id'];

    $response['success'] = true;
    $response['redirect'] = 'admin_dashboard.php';
    $response['user_id'] = $user['id'];
    echo json_encode($response);
    exit();

} catch (Exception $e) {
    error_log("Admin login error: " . $e->getMessage());
    $response['error'] = 'Login failed. Please try again.';
    echo json_encode($response);
    exit();
}
?>
