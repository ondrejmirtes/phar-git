<?php

declare (strict_types=1);
namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\GenericObjectType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use ReflectionAttribute;
use function count;
final class ReflectionGetAttributesMethodReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    /**
     * @var class-string
     */
    private string $className;
    /**
     * @param class-string $className One of reflection classes: https://www.php.net/manual/en/book.reflection.php
     */
    public function __construct(string $className)
    {
        $this->className = $className;
    }
    public function getClass(): string
    {
        return $this->className;
    }
    public function isMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getDeclaringClass()->getName() === $this->className && $methodReflection->getName() === 'getAttributes';
    }
    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
    {
        if (count($methodCall->getArgs()) === 0) {
            return null;
        }
        $argType = $scope->getType($methodCall->getArgs()[0]->value);
        $classType = $argType->getClassStringObjectType();
        return TypeCombinator::intersect(new ArrayType(new IntegerType(), new GenericObjectType(ReflectionAttribute::class, [$classType])), new AccessoryArrayListType());
    }
}
