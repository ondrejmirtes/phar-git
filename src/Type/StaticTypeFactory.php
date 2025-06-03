<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
final class StaticTypeFactory
{
    public static function falsey(): \PHPStan\Type\Type
    {
        static $falsey;
        if ($falsey === null) {
            $falsey = new \PHPStan\Type\UnionType([new \PHPStan\Type\NullType(), new ConstantBooleanType(\false), new ConstantIntegerType(0), new ConstantFloatType(0.0), new ConstantStringType(''), new ConstantStringType('0'), new ConstantArrayType([], [])]);
        }
        return $falsey;
    }
    public static function truthy(): \PHPStan\Type\Type
    {
        static $truthy;
        if ($truthy === null) {
            $truthy = new \PHPStan\Type\MixedType(\false, self::falsey());
        }
        return $truthy;
    }
}
