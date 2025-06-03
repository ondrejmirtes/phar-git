<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;
/**
 * @api
 */
final class InvalidateExprNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    private Expr $expr;
    public function __construct(Expr $expr)
    {
        $this->expr = $expr;
        parent::__construct($expr->getAttributes());
    }
    public function getExpr() : Expr
    {
        return $this->expr;
    }
    public function getType() : string
    {
        return 'PHPStan_Node_InvalidateExpr';
    }
    /**
     * @return string[]
     */
    public function getSubNodeNames() : array
    {
        return [];
    }
}
