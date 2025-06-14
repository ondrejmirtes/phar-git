<?php

declare (strict_types=1);
namespace PHPStan\Type\Generic;

use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function sprintf;
final class TypeProjectionHelper
{
    public static function describe(Type $type, ?\PHPStan\Type\Generic\TemplateTypeVariance $variance, VerbosityLevel $level): string
    {
        $describedType = $type->describe($level);
        if ($variance === null || $variance->invariant()) {
            return $describedType;
        }
        if ($variance->bivariant()) {
            return '*';
        }
        return sprintf('%s %s', $variance->describe(), $describedType);
    }
}
