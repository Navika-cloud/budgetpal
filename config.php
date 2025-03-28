<?php
function getDBConnection() {
    $host = 'localhost';
    $user = 'root';
    $pass = '';
    $db = 'budgetpal';

    $conn = new mysqli($host, $user, $pass, $db);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Create business_accounts table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS business_accounts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        company_name VARCHAR(255),
        business_type VARCHAR(50),
        tax_id VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    
    $conn->query($sql);
    
    return $conn;
}
?>