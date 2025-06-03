<?php

declare (strict_types=1);
namespace PHPStan\Type;

final class RecursionGuard
{
    /** @var true[] */
    private static array $context = [];
    /**
     * @template T
     * @param callable(): T $callback
     * @return T|ErrorType
     */
    public static function run(\PHPStan\Type\Type $type, callable $callback)
    {
        $key = $type->describe(\PHPStan\Type\VerbosityLevel::value());
        if (isset(self::$context[$key])) {
            return new \PHPStan\Type\ErrorType();
        }
        try {
            self::$context[$key] = \true;
            return $callback();
        } finally {
            unset(self::$context[$key]);
        }
    }
}
