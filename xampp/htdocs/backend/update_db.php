<?php
$host = 'localhost';
$db   = 'electro_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERR_SET, PDO::ERRMODE_EXCEPTION);
    
    // Check if column exists first
    $columns = $pdo->query("SHOW COLUMNS FROM `orders` LIKE 'dispute_resolution_url'")->fetchAll();
    if (empty($columns)) {
        $pdo->exec("ALTER TABLE `orders` ADD COLUMN `dispute_resolution_url` VARCHAR(500) DEFAULT NULL AFTER `payment_receipt_url` ");
        echo "Column 'dispute_resolution_url' added successfully.\n";
    } else {
        echo "Column 'dispute_resolution_url' already exists.\n";
    }
} catch (\PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
