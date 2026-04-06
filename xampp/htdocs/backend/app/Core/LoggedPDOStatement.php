<?php
declare(strict_types=1);

namespace Core;

class LoggedPDOStatement extends \PDOStatement
{
    private Logger $logger;

    /**
     * The constructor is protected and should not be called directly.
     * It is called by PDO when a new statement is created.
     */
    protected function __construct(Logger $logger)
    {
        $this->logger = $logger;
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