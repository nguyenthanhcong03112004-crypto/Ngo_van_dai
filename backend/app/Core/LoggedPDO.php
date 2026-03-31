<?php
declare(strict_types=1);

namespace Core;

class LoggedPDO extends \PDO
{
    public function __construct(string $dsn, ?string $username, ?string $password, ?array $options)
    {
        parent::__construct($dsn, $username, $password, $options);

        $this->setAttribute(
            \PDO::ATTR_STATEMENT_CLASS,
            [LoggedPDOStatement::class, [Logger::getInstance()]]
        );
    }
}