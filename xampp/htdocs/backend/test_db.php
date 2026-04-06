<?php
require_once 'app/Core/Database.php';
require_once 'app/Core/LoggedPDO.php';
require_once 'app/Core/Logger.php';
require_once 'app/Core/LoggedPDOStatement.php';

// Fix namespaces if needed
use Core\Database;

// Mock environment variables if not set
$_ENV['DB_HOST'] = 'localhost';
$_ENV['DB_NAME'] = 'ecommerce_db';
$_ENV['DB_USER'] = 'root';
$_ENV['DB_PASS'] = '';

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT COUNT(*) FROM vouchers");
    echo "SUCCESS: Vouchers count = " . $stmt->fetchColumn();
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
