<?php

declare (strict_types=1);
namespace PHPStan\Node\Expr;

use PhpParser\Node\Expr;
use PHPStan\Node\VirtualNode;
use PHPStan\Type\Type;
final class AlwaysRememberedExpr extends Expr implements VirtualNode
{
    public Expr $expr;
    private Type $type;
    private Type $nativeType;
    public function __construct(Expr $expr, Type $type, Type $nativeType)
    {
        $this->expr = $expr;
        $this->type = $type;
        $this->nativeType = $nativeType;
        parent::__construct([]);
    }
    public function getExpr() : Expr
    {
        return $this->expr;
    }
    public function getExprType() : Type
    {
        return $this->type;
    }
    public function getNativeExprType() : Type
    {
        return $this->nativeType;
    }
    public function getType() : string
    {
        return 'PHPStan_Node_AlwaysRememberedExpr';
    }
    /**
     * @return string[]
     */
    public function getSubNodeNames() : array
    {
        return ['expr'];
    }
}
