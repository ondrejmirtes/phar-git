<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PhpParser\Node\Stmt;
final class EndStatementResult
{
    private Stmt $statement;
    private \PHPStan\Analyser\StatementResult $result;
    public function __construct(Stmt $statement, \PHPStan\Analyser\StatementResult $result)
    {
        $this->statement = $statement;
        $this->result = $result;
    }
    public function getStatement() : Stmt
    {
        return $this->statement;
    }
    public function getResult() : \PHPStan\Analyser\StatementResult
    {
        return $this->result;
    }
}
