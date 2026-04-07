<?php
require_once '/opt/lampp/htdocs/backend/vendor/autoload.php';

// Mocking some constants if needed
if (!defined('DB_HOST'))
    define('DB_HOST', 'localhost');
if (!defined('DB_NAME'))
    define('DB_NAME', 'ecommerce_db_test');
