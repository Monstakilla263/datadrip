<?php
/**
 * Order Items Table Structure Validator
 * This script ensures the order_items table exists with the correct structure.
 * It should be included in key application files to prevent database structure issues.
 */

function ensureOrderItemsTableExists() {
    global $conn;
    
    if (!isset($conn)) {
        require_once 'db_connection.php';
    }
    
    try {
        // Check if order_items table exists
        $result = $conn->query("SHOW TABLES LIKE 'order_items'");
        
        if ($result->num_rows === 0) {
            // Create order_items table if it doesn't exist
            $conn->query("CREATE TABLE order_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                order_id INT NOT NULL,
                product_id INT NOT NULL,
                quantity INT NOT NULL DEFAULT 1,
                price DECIMAL(10,2) NOT NULL,
                subtotal DECIMAL(10,2) NOT NULL,
                name VARCHAR(255) NOT NULL,
                size VARCHAR(50) NOT NULL DEFAULT 'one-size',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
            )");
            
            error_log("Order items table created automatically");
            return true;
        } else {
            // Check if the name column exists
            $result = $conn->query("SHOW COLUMNS FROM order_items LIKE 'name'");
            if ($result->num_rows === 0) {
                // Add the name column
                $conn->query("ALTER TABLE order_items ADD COLUMN name VARCHAR(255) NOT NULL AFTER subtotal");
                error_log("Added 'name' column to order_items table");
            }
            
            // Check if the size column exists
            $result = $conn->query("SHOW COLUMNS FROM order_items LIKE 'size'");
            if ($result->num_rows === 0) {
                // Add the size column
                $conn->query("ALTER TABLE order_items ADD COLUMN size VARCHAR(50) NOT NULL DEFAULT 'one-size' AFTER name");
                error_log("Added 'size' column to order_items table");
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error ensuring order_items table exists: " . $e->getMessage());
        return false;
    }
}

// Run the function when this file is included
ensureOrderItemsTableExists();
?>
