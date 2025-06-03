<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PhpParser\Node\Stmt;
/**
 * @api
 */
final class StatementExitPoint
{
    private Stmt $statement;
    private \PHPStan\Analyser\MutatingScope $scope;
    public function __construct(Stmt $statement, \PHPStan\Analyser\MutatingScope $scope)
    {
        $this->statement = $statement;
        $this->scope = $scope;
    }
    public function getStatement() : Stmt
    {
        return $this->statement;
    }
    public function getScope() : \PHPStan\Analyser\MutatingScope
    {
        return $this->scope;
    }
}
