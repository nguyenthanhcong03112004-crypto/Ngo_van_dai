<?php
declare(strict_types=1);

namespace Core;

class LoggedPDO extends \PDO
{
    public function __construct(string $dsn, ?string $username, ?string $password, ?array $options)
    {
        parent::__construct($dsn, $username, $password, $options);

        // PHP 8.x: ATTR_STATEMENT_CLASS không cho phép constructor args khi class extends PDOStatement.
        // Logger được inject qua Logger::getInstance() singleton bên trong LoggedPDOStatement.
        $this->setAttribute(
            \PDO::ATTR_STATEMENT_CLASS,
            [LoggedPDOStatement::class]
        );
    }
}