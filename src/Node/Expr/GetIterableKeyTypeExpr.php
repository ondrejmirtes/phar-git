<?php

declare (strict_types=1);
namespace PHPStan\Node\Expr;

use PhpParser\Node\Expr;
use PHPStan\Node\VirtualNode;
final class GetIterableKeyTypeExpr extends Expr implements VirtualNode
{
    private Expr $expr;
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
        return 'PHPStan_Node_GetIterableKeyTypeExpr';
    }
    /**
     * @return string[]
     */
    public function getSubNodeNames(): array
    {
        return [];
    }
}
