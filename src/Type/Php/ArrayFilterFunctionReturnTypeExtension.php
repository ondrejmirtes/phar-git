<?php

declare (strict_types=1);
namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
#[\PHPStan\DependencyInjection\AutowiredService]
final class ArrayFilterFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{
    private \PHPStan\Type\Php\ArrayFilterFunctionReturnTypeHelper $arrayFilterFunctionReturnTypeHelper;
    public function __construct(\PHPStan\Type\Php\ArrayFilterFunctionReturnTypeHelper $arrayFilterFunctionReturnTypeHelper)
    {
        $this->arrayFilterFunctionReturnTypeHelper = $arrayFilterFunctionReturnTypeHelper;
    }
    public function isFunctionSupported(FunctionReflection $functionReflection) : bool
    {
        return $functionReflection->getName() === 'array_filter';
    }
    public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope) : Type
    {
        $arrayArg = $functionCall->getArgs()[0]->value ?? null;
        $callbackArg = $functionCall->getArgs()[1]->value ?? null;
        $flagArg = $functionCall->getArgs()[2]->value ?? null;
        return $this->arrayFilterFunctionReturnTypeHelper->getType($scope, $arrayArg, $callbackArg, $flagArg);
    }
}
