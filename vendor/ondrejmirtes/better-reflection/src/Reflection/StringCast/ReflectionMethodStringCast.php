<?php

declare (strict_types=1);
namespace PHPStan\BetterReflection\Reflection\StringCast;

use PHPStan\BetterReflection\Reflection\Exception\MethodPrototypeNotFound;
use PHPStan\BetterReflection\Reflection\ReflectionMethod;
use PHPStan\BetterReflection\Reflection\ReflectionParameter;
use function array_reduce;
use function assert;
use function count;
use function is_string;
use function sprintf;
/** @internal */
final class ReflectionMethodStringCast
{
    /**
     * @return non-empty-string
     *
     * @psalm-pure
     */
    public static function toString(ReflectionMethod $methodReflection): string
    {
        $parametersFormat = $methodReflection->getNumberOfParameters() > 0 || $methodReflection->hasReturnType() ? "\n\n  - Parameters [%d] {%s\n  }" : '';
        $returnTypeFormat = $methodReflection->hasReturnType() ? "\n  - Return [ %s ]" : '';
        return sprintf('Method [ <%s%s%s%s%s%s>%s%s%s %s method %s ] {%s' . $parametersFormat . $returnTypeFormat . "\n}", self::sourceToString($methodReflection), $methodReflection->isConstructor() ? ', ctor' : '', $methodReflection->isDestructor() ? ', dtor' : '', self::overwritesToString($methodReflection), self::inheritsToString($methodReflection), self::prototypeToString($methodReflection), $methodReflection->isFinal() ? ' final' : '', $methodReflection->isStatic() ? ' static' : '', $methodReflection->isAbstract() ? ' abstract' : '', self::visibilityToString($methodReflection), $methodReflection->getName(), self::fileAndLinesToString($methodReflection), count($methodReflection->getParameters()), self::parametersToString($methodReflection), self::returnTypeToString($methodReflection));
    }
    /** @psalm-pure */
    private static function sourceToString(ReflectionMethod $methodReflection): string
    {
        if ($methodReflection->isUserDefined()) {
            return 'user';
        }
        $extensionName = $methodReflection->getExtensionName();
        assert(is_string($extensionName));
        return sprintf('internal:%s', $extensionName);
    }
    /** @psalm-pure */
    private static function overwritesToString(ReflectionMethod $methodReflection): string
    {
        $parentClass = $methodReflection->getDeclaringClass()->getParentClass();
        if ($parentClass === null) {
            return '';
        }
        if (!$parentClass->hasMethod($methodReflection->getName())) {
            return '';
        }
        return sprintf(', overwrites %s', $parentClass->getName());
    }
    /** @psalm-pure */
    private static function inheritsToString(ReflectionMethod $methodReflection): string
    {
        if ($methodReflection->getDeclaringClass() === $methodReflection->getCurrentClass()) {
            return '';
        }
        return sprintf(', inherits %s', $methodReflection->getDeclaringClass()->getName());
    }
    /** @psalm-pure */
    private static function prototypeToString(ReflectionMethod $methodReflection): string
    {
        try {
            return sprintf(', prototype %s', $methodReflection->getPrototype()->getDeclaringClass()->getName());
        } catch (MethodPrototypeNotFound $exception) {
            return '';
        }
    }
    /** @psalm-pure */
    private static function visibilityToString(ReflectionMethod $methodReflection): string
    {
        if ($methodReflection->isProtected()) {
            return 'protected';
        }
        if ($methodReflection->isPrivate()) {
            return 'private';
        }
        return 'public';
    }
    /** @psalm-pure */
    private static function fileAndLinesToString(ReflectionMethod $methodReflection): string
    {
        if ($methodReflection->isInternal()) {
            return '';
        }
        $fileName = $methodReflection->getFileName();
        assert(is_string($fileName));
        return sprintf("\n  @@ %s %d - %d", $fileName, $methodReflection->getStartLine(), $methodReflection->getEndLine());
    }
    /** @psalm-pure */
    private static function parametersToString(ReflectionMethod $methodReflection): string
    {
        return array_reduce($methodReflection->getParameters(), static fn(string $string, ReflectionParameter $parameterReflection): string => $string . "\n    " . \PHPStan\BetterReflection\Reflection\StringCast\ReflectionParameterStringCast::toString($parameterReflection), '');
    }
    /** @psalm-pure */
    private static function returnTypeToString(ReflectionMethod $methodReflection): string
    {
        $type = $methodReflection->getReturnType();
        if ($type === null) {
            return '';
        }
        return \PHPStan\BetterReflection\Reflection\StringCast\ReflectionTypeStringCast::toString($type);
    }
}
