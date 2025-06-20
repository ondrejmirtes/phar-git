<?php

declare (strict_types=1);
namespace PHPStan\BetterReflection\Reflection\StringCast;

use PHPStan\BetterReflection\Reflection\ReflectionProperty;
use function sprintf;
/** @internal */
final class ReflectionPropertyStringCast
{
    /**
     * @return non-empty-string
     *
     * @psalm-pure
     */
    public static function toString(ReflectionProperty $propertyReflection, bool $indentDocComment = \true): string
    {
        $stateModifier = '';
        if (!$propertyReflection->isStatic()) {
            $stateModifier = $propertyReflection->isDefault() ? ' <default>' : ' <dynamic>';
        }
        $type = $propertyReflection->getType();
        return sprintf('%sProperty [%s %s%s%s%s $%s ]', \PHPStan\BetterReflection\Reflection\StringCast\ReflectionStringCastHelper::docCommentToString($propertyReflection, $indentDocComment), $stateModifier, self::visibilityToString($propertyReflection), $propertyReflection->isStatic() ? ' static' : '', $propertyReflection->isReadOnly() ? ' readonly' : '', $type !== null ? sprintf(' %s', \PHPStan\BetterReflection\Reflection\StringCast\ReflectionTypeStringCast::toString($type)) : '', $propertyReflection->getName());
    }
    /** @psalm-pure */
    private static function visibilityToString(ReflectionProperty $propertyReflection): string
    {
        if ($propertyReflection->isProtected()) {
            return 'protected';
        }
        if ($propertyReflection->isPrivate()) {
            return 'private';
        }
        return 'public';
    }
}
