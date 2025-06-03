<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
final class EnsuredNonNullabilityResultExpression
{
    private Expr $expression;
    private Type $originalType;
    private Type $originalNativeType;
    private TrinaryLogic $certainty;
    public function __construct(Expr $expression, Type $originalType, Type $originalNativeType, TrinaryLogic $certainty)
    {
        $this->expression = $expression;
        $this->originalType = $originalType;
        $this->originalNativeType = $originalNativeType;
        $this->certainty = $certainty;
    }
    public function getExpression() : Expr
    {
        return $this->expression;
    }
    public function getOriginalType() : Type
    {
        return $this->originalType;
    }
    public function getOriginalNativeType() : Type
    {
        return $this->originalNativeType;
    }
    public function getCertainty() : TrinaryLogic
    {
        return $this->certainty;
    }
}
