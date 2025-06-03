<?php

declare (strict_types=1);
namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ErrorType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
#[\PHPStan\DependencyInjection\AutowiredService]
final class ConstantFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{
    private \PHPStan\Type\Php\ConstantHelper $constantHelper;
    public function __construct(\PHPStan\Type\Php\ConstantHelper $constantHelper)
    {
        $this->constantHelper = $constantHelper;
    }
    public function isFunctionSupported(FunctionReflection $functionReflection): bool
    {
        return $functionReflection->getName() === 'constant';
    }
    public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
    {
        if (count($functionCall->getArgs()) < 1) {
            return null;
        }
        $nameType = $scope->getType($functionCall->getArgs()[0]->value);
        $results = [];
        foreach ($nameType->getConstantStrings() as $constantName) {
            $expr = $this->constantHelper->createExprFromConstantName($constantName->getValue());
            if ($expr === null) {
                return new ErrorType();
            }
            $results[] = $scope->getType($expr);
        }
        if (count($results) > 0) {
            return TypeCombinator::union(...$results);
        }
        return null;
    }
}
