<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PhpParser\Node;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\Scope;
/**
 * @api
 */
final class ReturnStatement
{
    private Scope $scope;
    private Node\Stmt\Return_ $returnNode;
    public function __construct(Scope $scope, Return_ $returnNode)
    {
        $this->scope = $scope;
        $this->returnNode = $returnNode;
    }
    public function getScope(): Scope
    {
        return $this->scope;
    }
    public function getReturnNode(): Return_
    {
        return $this->returnNode;
    }
}
