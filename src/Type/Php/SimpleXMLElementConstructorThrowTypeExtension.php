<?php

declare (strict_types=1);
namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicStaticMethodThrowTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use SimpleXMLElement;
use function count;
use function extension_loaded;
use function libxml_use_internal_errors;
#[AutowiredService]
final class SimpleXMLElementConstructorThrowTypeExtension implements DynamicStaticMethodThrowTypeExtension
{
    public function isStaticMethodSupported(MethodReflection $methodReflection): bool
    {
        return extension_loaded('simplexml') && $methodReflection->getName() === '__construct' && $methodReflection->getDeclaringClass()->getName() === SimpleXMLElement::class;
    }
    public function getThrowTypeFromStaticMethodCall(MethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?Type
    {
        if (count($methodCall->getArgs()) === 0) {
            return $methodReflection->getThrowType();
        }
        $valueType = $scope->getType($methodCall->getArgs()[0]->value);
        $constantStrings = $valueType->getConstantStrings();
        $internalErrorsOld = libxml_use_internal_errors(\true);
        try {
            foreach ($constantStrings as $constantString) {
                try {
                    new SimpleXMLElement($constantString->getValue());
                } catch (\Exception $e) {
                    // phpcs:ignore
                    return $methodReflection->getThrowType();
                }
                $valueType = TypeCombinator::remove($valueType, $constantString);
            }
        } finally {
            libxml_use_internal_errors($internalErrorsOld);
        }
        if (!$valueType instanceof NeverType) {
            return $methodReflection->getThrowType();
        }
        return null;
    }
}
