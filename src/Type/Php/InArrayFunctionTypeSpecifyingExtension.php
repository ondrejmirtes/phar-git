<?php

declare (strict_types=1);
namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\Equal;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\AlwaysRememberedExpr;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\TypeCombinator;
use function count;
use function strtolower;
#[\PHPStan\DependencyInjection\AutowiredService]
final class InArrayFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{
    private TypeSpecifier $typeSpecifier;
    public function setTypeSpecifier(TypeSpecifier $typeSpecifier) : void
    {
        $this->typeSpecifier = $typeSpecifier;
    }
    public function isFunctionSupported(FunctionReflection $functionReflection, FuncCall $node, TypeSpecifierContext $context) : bool
    {
        return strtolower($functionReflection->getName()) === 'in_array' && !$context->null();
    }
    public function specifyTypes(FunctionReflection $functionReflection, FuncCall $node, Scope $scope, TypeSpecifierContext $context) : SpecifiedTypes
    {
        $argsCount = count($node->getArgs());
        if ($argsCount < 2) {
            return new SpecifiedTypes();
        }
        $isStrictComparison = \false;
        if ($argsCount >= 3) {
            $strictNodeType = $scope->getType($node->getArgs()[2]->value);
            $isStrictComparison = $strictNodeType->isTrue()->yes();
        }
        $needleExpr = $node->getArgs()[0]->value;
        $arrayExpr = $node->getArgs()[1]->value;
        $needleType = $scope->getType($needleExpr);
        $arrayType = $scope->getType($arrayExpr);
        $arrayValueType = $arrayType->getIterableValueType();
        $isStrictComparison = $isStrictComparison || $needleType->isEnum()->yes() || $arrayValueType->isEnum()->yes() || $needleType->isString()->yes() && $arrayValueType->isString()->yes() || $needleType->isInteger()->yes() && $arrayValueType->isInteger()->yes() || $needleType->isFloat()->yes() && $arrayValueType->isFloat()->yes() || $needleType->isBoolean()->yes() && $arrayValueType->isBoolean()->yes();
        if ($arrayExpr instanceof Array_) {
            $types = null;
            foreach ($arrayExpr->items as $item) {
                if ($item->unpack) {
                    $types = null;
                    break;
                }
                if ($isStrictComparison) {
                    $itemTypes = $this->typeSpecifier->resolveIdentical(new Identical($needleExpr, $item->value), $scope, $context);
                } else {
                    $itemTypes = $this->typeSpecifier->resolveEqual(new Equal($needleExpr, $item->value), $scope, $context);
                }
                if ($types === null) {
                    $types = $itemTypes;
                    continue;
                }
                $types = $context->true() ? $types->normalize($scope)->intersectWith($itemTypes->normalize($scope)) : $types->unionWith($itemTypes);
            }
            if ($types !== null) {
                return $types;
            }
        }
        if (!$isStrictComparison) {
            if ($context->true() && $arrayType->isArray()->yes() && $arrayType->getIterableValueType()->isSuperTypeOf($needleType)->yes()) {
                return $this->typeSpecifier->create($node->getArgs()[1]->value, TypeCombinator::intersect($arrayType, new NonEmptyArrayType()), $context, $scope);
            }
            return new SpecifiedTypes();
        }
        $specifiedTypes = new SpecifiedTypes();
        if ($context->true() || $context->false() && count($arrayValueType->getFiniteTypes()) > 0 && count($needleType->getFiniteTypes()) > 0 && $arrayType->isIterableAtLeastOnce()->yes()) {
            $specifiedTypes = $this->typeSpecifier->create($needleExpr, $arrayValueType, $context, $scope);
            if ($needleExpr instanceof AlwaysRememberedExpr) {
                $specifiedTypes = $specifiedTypes->unionWith($this->typeSpecifier->create($needleExpr->getExpr(), $arrayValueType, $context, $scope));
            }
        }
        if ($context->true() || $context->false() && count($needleType->getFiniteTypes()) === 1) {
            if ($context->true()) {
                $arrayValueType = TypeCombinator::union($arrayValueType, $needleType);
            } else {
                $arrayValueType = TypeCombinator::remove($arrayValueType, $needleType);
            }
            $specifiedTypes = $specifiedTypes->unionWith($this->typeSpecifier->create($node->getArgs()[1]->value, new ArrayType(new MixedType(), $arrayValueType), TypeSpecifierContext::createTrue(), $scope));
        }
        if ($context->true() && $arrayType->isArray()->yes()) {
            $specifiedTypes = $specifiedTypes->unionWith($this->typeSpecifier->create($node->getArgs()[1]->value, TypeCombinator::intersect($arrayType, new NonEmptyArrayType()), $context, $scope));
        }
        return $specifiedTypes;
    }
}
