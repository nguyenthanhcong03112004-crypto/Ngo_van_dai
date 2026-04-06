<?php
declare(strict_types=1);

namespace Core;

class LoggedPDOStatement extends \PDOStatement
{
    private Logger $logger;

    /**
     * Constructor phải được protected (không có tham số) để tương thích với PHP 8.x.
     * PDO::ATTR_STATEMENT_CLASS không cho phép public constructor có tham số.
     * Logger được inject qua setLogger() từ LoggedPDO sau khi prepare().
     */
    protected function __construct()
    {
        $this->logger = Logger::getInstance();
    }

    /**
     * Overrides the default execute method to add logging.
     */
    public function execute(?array $params = null): bool
    {
        $start = microtime(true);
        try {
            $result = parent::execute($params);
            $durationMs = (microtime(true) - $start) * 1000;
            $this->logger->logQuery($this->queryString, $params ?? [], $durationMs);
            return $result;
        } catch (\PDOException $e) {
            $this->logger->logQueryError($this->queryString, $e, $params ?? []);
            throw $e; // Re-throw the exception to be handled by application logic
        }
    }
}