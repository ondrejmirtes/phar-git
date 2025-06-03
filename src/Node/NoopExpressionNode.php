<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;
final class NoopExpressionNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    private Expr $originalExpr;
    private bool $hasAssign;
    public function __construct(Expr $originalExpr, bool $hasAssign)
    {
        $this->originalExpr = $originalExpr;
        $this->hasAssign = $hasAssign;
        parent::__construct($this->originalExpr->getAttributes());
    }
    public function getOriginalExpr(): Expr
    {
        return $this->originalExpr;
    }
    public function hasAssign(): bool
    {
        return $this->hasAssign;
    }
    public function getType(): string
    {
        return 'PHPStan_Node_NoopExpressionNode';
    }
    /**
     * @return string[]
     */
    public function getSubNodeNames(): array
    {
        return [];
    }
}
