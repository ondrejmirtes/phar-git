<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PhpParser\Node\Expr;
/**
 * @api
 */
final class IssetExpr extends Expr implements \PHPStan\Node\VirtualNode
{
    private Expr $expr;
    /**
     * @api
     */
    public function __construct(Expr $expr)
    {
        $this->expr = $expr;
        parent::__construct([]);
    }
    public function getExpr(): Expr
    {
        return $this->expr;
    }
    public function getType(): string
    {
        return 'PHPStan_Node_IssetExpr';
    }
    /**
     * @return string[]
     */
    public function getSubNodeNames(): array
    {
        return [];
    }
}
