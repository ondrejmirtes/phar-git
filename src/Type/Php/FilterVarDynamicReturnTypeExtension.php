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
use function strtolower;
#[AutowiredService]
final class FilterVarDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{
    private \PHPStan\Type\Php\FilterFunctionReturnTypeHelper $filterFunctionReturnTypeHelper;
    public function __construct(\PHPStan\Type\Php\FilterFunctionReturnTypeHelper $filterFunctionReturnTypeHelper)
    {
        $this->filterFunctionReturnTypeHelper = $filterFunctionReturnTypeHelper;
    }
    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return strtolower($functionReflection->getName()) === 'filter_var';
    }
    public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
    {
        if (count($functionCall->getArgs()) < 1) {
            return null;
        }
        $inputType = $scope->getType($functionCall->getArgs()[0]->value);
        $filterType = isset($functionCall->getArgs()[1]) ? $scope->getType($functionCall->getArgs()[1]->value) : null;
        $flagsType = isset($functionCall->getArgs()[2]) ? $scope->getType($functionCall->getArgs()[2]->value) : null;
        return $this->filterFunctionReturnTypeHelper->getType($inputType, $filterType, $flagsType);
    }
}
