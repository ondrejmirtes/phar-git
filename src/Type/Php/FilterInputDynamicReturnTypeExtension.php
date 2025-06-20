<?php

declare (strict_types=1);
namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;
use function count;
#[AutowiredService]
final class FilterInputDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{
    private \PHPStan\Type\Php\FilterFunctionReturnTypeHelper $filterFunctionReturnTypeHelper;
    public function __construct(\PHPStan\Type\Php\FilterFunctionReturnTypeHelper $filterFunctionReturnTypeHelper)
    {
        $this->filterFunctionReturnTypeHelper = $filterFunctionReturnTypeHelper;
    }
    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return $functionReflection->getName() === 'filter_input';
    }
    public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
    {
        if (count($functionCall->getArgs()) < 2) {
            return null;
        }
        return $this->filterFunctionReturnTypeHelper->getInputType($scope->getType($functionCall->getArgs()[0]->value), $scope->getType($functionCall->getArgs()[1]->value), isset($functionCall->getArgs()[2]) ? $scope->getType($functionCall->getArgs()[2]->value) : null, isset($functionCall->getArgs()[3]) ? $scope->getType($functionCall->getArgs()[3]->value) : null);
    }
}
