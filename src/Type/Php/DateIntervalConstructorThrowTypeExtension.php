<?php

declare (strict_types=1);
namespace PHPStan\Type\Php;

use DateInterval;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodThrowTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
#[AutowiredService]
final class DateIntervalConstructorThrowTypeExtension implements DynamicStaticMethodThrowTypeExtension
{
    private PhpVersion $phpVersion;
    public function __construct(PhpVersion $phpVersion)
    {
        $this->phpVersion = $phpVersion;
    }
    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return $methodReflection->getName() === '__construct' && $methodReflection->getDeclaringClass()->getName() === DateInterval::class;
    }
    public function getThrowTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?Type
    {
        if (count($methodCall->getArgs()) === 0) {
            return $methodReflection->getThrowType();
        }
        $valueType = $scope->getType($methodCall->getArgs()[0]->value);
        $constantStrings = $valueType->getConstantStrings();
        foreach ($constantStrings as $constantString) {
            try {
                new DateInterval($constantString->getValue());
            } catch (\Exception $e) {
                // phpcs:ignore
                return $this->exceptionType();
            }
            $valueType = TypeCombinator::remove($valueType, $constantString);
        }
        if (!$valueType instanceof NeverType) {
            return $this->exceptionType();
        }
        return null;
    }
    private function exceptionType(): Type
    {
        if ($this->phpVersion->hasDateTimeExceptions()) {
            return new ObjectType('DateMalformedIntervalStringException');
        }
        return new ObjectType('Exception');
    }
}
