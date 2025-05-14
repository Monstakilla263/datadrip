<?php
session_start();
header('Content-Type: application/json');

// Database connection
$host = 'localhost';
$db   = 'user_auth';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    error_log("Login failed: Database connection error: " . $conn->connect_error);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        error_log("Login failed: Missing username or password");
        echo json_encode(['success' => false, 'error' => 'Username and password are required']);
        exit();
    }

    try {
        // Find user by username
        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
        if (!$stmt) {
            error_log("Login failed: SQL prepare error: " . $conn->error);
            echo json_encode(['success' => false, 'error' => 'Database error']);
            exit();
        }

        $stmt->bind_param("s", $username);
        
        if (!$stmt->execute()) {
            error_log("Login failed: SQL execute error: " . $stmt->error);
            echo json_encode(['success' => false, 'error' => 'Database error']);
            exit();
        }

        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_id'] = $user['id'];
                echo json_encode([
                    'success' => true, 
                    'redirect' => 'dashboard.php'
                ]);
                exit();
            } else {
                error_log("Login failed: Invalid password for user " . $username);
            }
        } else {
            error_log("Login failed: User not found or is admin: " . $username);
        }

        // If no match found or password incorrect
        echo json_encode([
            'success' => false, 
            'error' => 'Invalid credentials'
        ]);
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        echo json_encode([
            'success' => false, 
            'error' => 'Login failed. Please try again.'
        ]);
    }

    $stmt->close();
    $conn->close();
    exit();
} else {
    error_log("Login failed: Invalid request method");
    echo json_encode([
        'success' => false, 
        'error' => 'Invalid request method'
    ]);
}
?>