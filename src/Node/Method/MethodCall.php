<?php

declare (strict_types=1);
namespace PHPStan\Node\Method;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
/**
 * @api
 */
final class MethodCall
{
    /**
     * @var \PhpParser\Node\Expr\MethodCall|\PhpParser\Node\Expr\StaticCall|\PhpParser\Node\Expr\Array_
     */
    private $node;
    private Scope $scope;
    /**
     * @param \PhpParser\Node\Expr\MethodCall|\PhpParser\Node\Expr\StaticCall|\PhpParser\Node\Expr\Array_ $node
     */
    public function __construct($node, Scope $scope)
    {
        $this->node = $node;
        $this->scope = $scope;
    }
    /**
     * @return Node\Expr\MethodCall|StaticCall|Array_
     */
    public function getNode()
    {
        return $this->node;
    }
    public function getScope(): Scope
    {
        return $this->scope;
    }
}
