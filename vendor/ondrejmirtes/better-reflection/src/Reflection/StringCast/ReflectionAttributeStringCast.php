<?php

declare (strict_types=1);
namespace PHPStan\BetterReflection\Reflection\StringCast;

use PHPStan\BetterReflection\Reflection\ReflectionAttribute;
use function count;
use function is_array;
use function is_string;
use function sprintf;
use function strlen;
use function substr;
use function var_export;
/** @internal */
final class ReflectionAttributeStringCast
{
    /**
     * @return non-empty-string
     *
     * @psalm-pure
     */
    public static function toString(ReflectionAttribute $attributeReflection): string
    {
        $arguments = $attributeReflection->getArguments();
        $argumentsFormat = $arguments !== [] ? " {\n  - Arguments [%d] {%s\n  }\n}" : '';
        return sprintf('Attribute [ %s ]' . $argumentsFormat . "\n", $attributeReflection->getName(), count($arguments), self::argumentsToString($arguments));
    }
    /**
     * @param array<int|string, mixed> $arguments
     *
     * @psalm-pure
     */
    private static function argumentsToString(array $arguments): string
    {
        $string = '';
        $argumentNo = 0;
        /** @psalm-suppress MixedAssignment */
        foreach ($arguments as $argumentName => $argumentValue) {
            $string .= sprintf("\n    Argument #%d [ %s%s ]", $argumentNo, is_string($argumentName) ? sprintf('%s = ', $argumentName) : '', self::argumentValueToString($argumentValue));
            $argumentNo++;
        }
        return $string;
    }
    /** @psalm-pure
     * @param mixed $value */
    private static function argumentValueToString($value): string
    {
        if (is_array($value)) {
            return 'Array';
        }
        if (is_string($value) && strlen($value) > 15) {
            return var_export(substr($value, 0, 15) . '...', \true);
        }
        return var_export($value, \true);
    }
}
