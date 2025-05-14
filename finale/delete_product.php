<?php
session_start();
require_once 'db_connection.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_username'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Check if product_id is provided
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    // Ensure product_id is an integer
    $product_id = (int)$_POST['product_id'];

    header('Content-Type: application/json'); // Set header early

    if ($product_id > 0) {
        // Delete the product
        // It's good practice to check for related records (e.g., in order_items) first
        // or handle foreign key constraint errors gracefully.

        // Example: Check related order_items (conceptual)
        // $check_query = "SELECT COUNT(*) as count FROM order_items WHERE product_id = $product_id";
        // $check_result = mysqli_query($conn, $check_query);
        // $check_row = mysqli_fetch_assoc($check_result);
        // if ($check_row['count'] > 0) {
        //     echo json_encode(['success' => false, 'error' => 'Product cannot be deleted. It exists in orders.']);
        //     exit();
        // }

        $query = "DELETE FROM products WHERE id = $product_id"; // No quotes around numeric $product_id
        $result = mysqli_query($conn, $query);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'product_id' => $product_id // Sending back the ID can be useful for the client
            ]);
        } else {
            $db_error = mysqli_error($conn); // Get specific database error
            error_log("Failed to delete product ID $product_id: " . $db_error); // Log error for admin
            echo json_encode(['success' => false, 'error' => 'Failed to delete product. DB Error: ' . $db_error]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid Product ID.']);
    }
} else {
    header('Content-Type: application/json'); // Ensure header is set for all paths
    echo json_encode(['success' => false, 'error' => 'Invalid request method or missing product_id.']);
}
?>
