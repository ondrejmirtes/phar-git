<?php

declare (strict_types=1);
namespace PHPStan\Reflection\PHPStan;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
final class NativeReflectionEnumReturnDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
    private PhpVersion $phpVersion;
    /**
     * @var class-string
     */
    private string $className;
    private string $methodName;
    /**
     * @param class-string $className
     */
    public function __construct(PhpVersion $phpVersion, string $className, string $methodName)
    {
        $this->phpVersion = $phpVersion;
        $this->className = $className;
        $this->methodName = $methodName;
    }
    public function getClass() : string
    {
        return $this->className;
    }
    public function isMethodSupported(MethodReflection $methodReflection) : bool
    {
        return $methodReflection->getName() === $this->methodName;
    }
    public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope) : ?Type
    {
        if ($this->phpVersion->getVersionId() >= 80000) {
            return null;
        }
        return new ObjectType(ReflectionClass::class);
    }
}
