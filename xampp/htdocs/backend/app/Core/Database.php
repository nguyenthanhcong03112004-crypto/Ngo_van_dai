<?php
declare(strict_types=1);

namespace Core;

use PDO;
use PDOException;

class Database
{
    private static ?Database $instance = null;
    private PDO $pdo;
    private Logger $logger;

    private function __construct()
    {
        $this->logger = Logger::getInstance();

        $host = $_ENV['DB_HOST'] ?? 'mysql';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $name = $_ENV['DB_NAME'] ?? 'ecommerce_db';
        $user = $_ENV['DB_USER'] ?? 'ecommerce_user';
        $pass = $_ENV['DB_PASS'] ?? '';

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

        try {
            $this->pdo = new LoggedPDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);

            $this->logger->debug('Database connection established successfully');
        } catch (PDOException $e) {
            $this->logger->critical("Database Connection Error", ['error' => $e->getMessage()]);
            http_response_code(500);
            $msg = 'Lỗi kết nối Database: ' . $e->getMessage();
            echo json_encode([
                'status'  => 'error',
                'message' => $msg,
                'data'    => null,
            ]);
            exit;
        }
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return $this->pdo;
    }

    // Prevent cloning and unserialization
    private function __clone() {}
    public function __wakeup(): void {}
}
