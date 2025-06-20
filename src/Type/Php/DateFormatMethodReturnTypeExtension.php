<?php

declare (strict_types=1);
namespace PHPStan\Type\Php;

use DateTimeInterface;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use function count;
#[AutowiredService]
final class DateFormatMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    private \PHPStan\Type\Php\DateFunctionReturnTypeHelper $dateFunctionReturnTypeHelper;
    public function __construct(\PHPStan\Type\Php\DateFunctionReturnTypeHelper $dateFunctionReturnTypeHelper)
    {
        $this->dateFunctionReturnTypeHelper = $dateFunctionReturnTypeHelper;
    }
    public function getClass(): string
    {
        return DateTimeInterface::class;
    }
    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === 'format';
    }
    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
    {
        if (count($methodCall->getArgs()) === 0) {
            return new StringType();
        }
        return $this->dateFunctionReturnTypeHelper->getTypeFromFormatType($scope->getType($methodCall->getArgs()[0]->value), \true);
    }
}
