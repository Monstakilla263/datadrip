<?php
require_once 'db_connection.php';

try {
    // Check users table structure
    $result = $conn->query("DESCRIBE users");
    echo "Users table structure:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }

    // Check admins table structure
    $result = $conn->query("DESCRIBE admins");
    echo "\nAdmins table structure:\n";
    while ($row = $result->fetch_assoc()) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }

    // Check if any users exist
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $row = $result->fetch_assoc();
    echo "\nTotal users in database: " . $row['count'];

    // Check if admin user exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", 'admin');
    $stmt->execute();
    $stmt->store_result();
    echo "\nAdmin user exists: " . ($stmt->num_rows > 0 ? "Yes" : "No");

    $conn->close();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
