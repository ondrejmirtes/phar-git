<?php

declare (strict_types=1);
namespace PHPStan\Rules\Comparison;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Type\BooleanType;
final class ConstantConditionRuleHelper
{
    private \PHPStan\Rules\Comparison\ImpossibleCheckTypeHelper $impossibleCheckTypeHelper;
    private bool $treatPhpDocTypesAsCertain;
    public function __construct(\PHPStan\Rules\Comparison\ImpossibleCheckTypeHelper $impossibleCheckTypeHelper, bool $treatPhpDocTypesAsCertain)
    {
        $this->impossibleCheckTypeHelper = $impossibleCheckTypeHelper;
        $this->treatPhpDocTypesAsCertain = $treatPhpDocTypesAsCertain;
    }
    public function shouldSkip(Scope $scope, Expr $expr): bool
    {
        if ($expr instanceof Expr\BinaryOp\Equal || $expr instanceof Expr\BinaryOp\NotEqual) {
            return \true;
        }
        if ($expr instanceof Expr\Instanceof_ || $expr instanceof Expr\BinaryOp\Identical || $expr instanceof Expr\BinaryOp\NotIdentical || $expr instanceof Expr\BooleanNot || $expr instanceof Expr\BinaryOp\BooleanOr || $expr instanceof Expr\BinaryOp\BooleanAnd || $expr instanceof Expr\Ternary || $expr instanceof Expr\Isset_ || $expr instanceof Expr\Empty_ || $expr instanceof Expr\BinaryOp\Greater || $expr instanceof Expr\BinaryOp\GreaterOrEqual || $expr instanceof Expr\BinaryOp\Smaller || $expr instanceof Expr\BinaryOp\SmallerOrEqual) {
            // already checked by different rules
            return \true;
        }
        if ($expr instanceof FuncCall || $expr instanceof MethodCall || $expr instanceof Expr\StaticCall) {
            $isAlways = $this->impossibleCheckTypeHelper->findSpecifiedType($scope, $expr);
            if ($isAlways !== null) {
                return \true;
            }
        }
        return \false;
    }
    public function getBooleanType(Scope $scope, Expr $expr): BooleanType
    {
        if ($this->shouldSkip($scope, $expr)) {
            return new BooleanType();
        }
        if ($this->treatPhpDocTypesAsCertain) {
            return $scope->getType($expr)->toBoolean();
        }
        return $scope->getNativeType($expr)->toBoolean();
    }
    public function getNativeBooleanType(Scope $scope, Expr $expr): BooleanType
    {
        if ($this->shouldSkip($scope, $expr)) {
            return new BooleanType();
        }
        return $scope->getNativeType($expr)->toBoolean();
    }
}
