<?php
/**
 * Cart Table Structure Validator
 * This script ensures the cart_items table exists with the correct structure.
 * It should be included in key application files to prevent database structure issues.
 */

function ensureCartTableExists() {
    global $conn;
    
    if (!isset($conn)) {
        require_once 'db_connection.php';
    }
    
    try {
        // Check if cart_items table exists
        $result = $conn->query("SHOW TABLES LIKE 'cart_items'");
        
        if ($result->num_rows === 0) {
            // Create cart_items table if it doesn't exist
            $conn->query("CREATE TABLE cart_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                product_id INT NOT NULL,
                name VARCHAR(255) NOT NULL,
                category VARCHAR(100) NOT NULL,
                price DECIMAL(10,2) NOT NULL,
                quantity INT NOT NULL DEFAULT 1,
                size VARCHAR(50) NOT NULL DEFAULT 'one-size',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_cart_item (user_id, product_id, size)
            )");
            
            error_log("Cart items table created automatically");
            return true;
        } else {
            // Check if the size column exists
            $result = $conn->query("SHOW COLUMNS FROM cart_items LIKE 'size'");
            
            if ($result->num_rows === 0) {
                // Table exists but missing size column - needs to be rebuilt
                // Backup existing data
                $existingData = [];
                $result = $conn->query("SELECT * FROM cart_items");
                while ($row = $result->fetch_assoc()) {
                    $existingData[] = $row;
                }
                
                // Drop and recreate the table
                $conn->query("DROP TABLE cart_items");
                $conn->query("CREATE TABLE cart_items (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    product_id INT NOT NULL,
                    name VARCHAR(255) NOT NULL,
                    category VARCHAR(100) NOT NULL,
                    price DECIMAL(10,2) NOT NULL,
                    quantity INT NOT NULL DEFAULT 1,
                    size VARCHAR(50) NOT NULL DEFAULT 'one-size',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_cart_item (user_id, product_id, size)
                )");
                
                // Restore data
                foreach ($existingData as $item) {
                    try {
                        $stmt = $conn->prepare("INSERT INTO cart_items 
                            (user_id, product_id, name, category, price, quantity, size) 
                            VALUES (?, ?, ?, ?, ?, ?, 'one-size')");
                        
                        $stmt->bind_param(
                            "iissdi", 
                            $item['user_id'], 
                            $item['product_id'], 
                            $item['name'], 
                            $item['category'], 
                            $item['price'], 
                            $item['quantity']
                        );
                        
                        $stmt->execute();
                    } catch (Exception $e) {
                        error_log("Error restoring cart item: " . $e->getMessage());
                    }
                }
                
                error_log("Cart items table automatically rebuilt with size column");
                return true;
            }
            
            // Check if the unique constraint exists
            $result = $conn->query("SHOW CREATE TABLE cart_items");
            $row = $result->fetch_assoc();
            $createTableSql = $row['Create Table'];
            
            if (strpos($createTableSql, 'UNIQUE KEY `unique_cart_item`') === false) {
                // Try to add the unique constraint
                try {
                    $conn->query("ALTER TABLE cart_items ADD UNIQUE KEY unique_cart_item (user_id, product_id, size)");
                    error_log("Added unique constraint to cart_items table");
                } catch (Exception $e) {
                    error_log("Error adding unique constraint: " . $e->getMessage());
                }
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error ensuring cart table exists: " . $e->getMessage());
        return false;
    }
}

// Run the function when this file is included
ensureCartTableExists();
?>
