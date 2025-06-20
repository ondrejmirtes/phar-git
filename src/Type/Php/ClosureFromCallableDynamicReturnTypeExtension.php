<?php

declare (strict_types=1);
namespace PHPStan\Type\Php;

use Closure;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\ClosureType;
use PHPStan\Type\DynamicStaticMethodReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
#[AutowiredService]
final class ClosureFromCallableDynamicReturnTypeExtension implements DynamicStaticMethodReturnTypeExtension
{
    public function getClass(): string
    {
        return Closure::class;
    }
    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'fromCallable';
    }
    public function getTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?Type
    {
        if (!isset($methodCall->getArgs()[0])) {
            return null;
        }
        $callableType = $scope->getType($methodCall->getArgs()[0]->value);
        if ($callableType->isCallable()->no()) {
            return new ErrorType();
        }
        $closureTypes = [];
        foreach ($callableType->getCallableParametersAcceptors($scope) as $variant) {
            $parameters = $variant->getParameters();
            $closureTypes[] = new ClosureType($parameters, $variant->getReturnType(), $variant->isVariadic(), $variant->getTemplateTypeMap(), $variant->getResolvedTemplateTypeMap(), $variant instanceof ExtendedParametersAcceptor ? $variant->getCallSiteVarianceMap() : TemplateTypeVarianceMap::createEmpty(), [], $variant->getThrowPoints(), $variant->getImpurePoints(), $variant->getInvalidateExpressions(), $variant->getUsedVariables(), $variant->acceptsNamedArguments());
        }
        return TypeCombinator::union(...$closureTypes);
    }
}
