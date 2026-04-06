<?php
// Autoloader logic from index.php
spl_autoload_register(function (string $class): void {
    $base = dirname(__DIR__) . '/app/';
    $file = $base . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

try {
    $db = \Core\Database::getInstance()->getConnection();
    
    // Check if column exists first
    $stmt = $db->query("SHOW COLUMNS FROM `orders` LIKE 'dispute_resolution_url'");
    if ($stmt->rowCount() === 0) {
        $db->exec("ALTER TABLE `orders` ADD COLUMN `dispute_resolution_url` VARCHAR(500) DEFAULT NULL AFTER `payment_receipt_url` ");
        echo "<h1>Success</h1><p>Column 'dispute_resolution_url' added successfully.</p>";
    } else {
        echo "<h1>Notice</h1><p>Column 'dispute_resolution_url' already exists.</p>";
    }
} catch (\Throwable $e) {
    echo "<h1>Error</h1><p>" . $e->getMessage() . "</p>";
}
