<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\TrinaryLogic;
use function get_class;
trait JustNullableTypeTrait
{
    public function getReferencedClasses(): array
    {
        return [];
    }
    public function getObjectClassNames(): array
    {
        return [];
    }
    public function getObjectClassReflections(): array
    {
        return [];
    }
    public function accepts(\PHPStan\Type\Type $type, bool $strictTypes): \PHPStan\Type\AcceptsResult
    {
        if ($type instanceof static) {
            return \PHPStan\Type\AcceptsResult::createYes();
        }
        if ($type instanceof \PHPStan\Type\CompoundType) {
            return $type->isAcceptedBy($this, $strictTypes);
        }
        return \PHPStan\Type\AcceptsResult::createNo();
    }
    public function isSuperTypeOf(\PHPStan\Type\Type $type): \PHPStan\Type\IsSuperTypeOfResult
    {
        if ($type instanceof self) {
            return \PHPStan\Type\IsSuperTypeOfResult::createYes();
        }
        if ($type instanceof \PHPStan\Type\CompoundType) {
            return $type->isSubTypeOf($this);
        }
        return \PHPStan\Type\IsSuperTypeOfResult::createNo();
    }
    public function equals(\PHPStan\Type\Type $type): bool
    {
        return get_class($type) === static::class;
    }
    public function traverse(callable $cb): \PHPStan\Type\Type
    {
        return $this;
    }
    public function traverseSimultaneously(\PHPStan\Type\Type $right, callable $cb): \PHPStan\Type\Type
    {
        return $this;
    }
    public function isNull(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isConstantValue(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isConstantScalarValue(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function getConstantScalarTypes(): array
    {
        return [];
    }
    public function getConstantScalarValues(): array
    {
        return [];
    }
    public function isTrue(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isFalse(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isBoolean(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isFloat(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isInteger(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isString(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isNumericString(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isNonEmptyString(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isNonFalsyString(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isLiteralString(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isLowercaseString(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isClassString(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isUppercaseString(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function getClassStringObjectType(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function getObjectTypeOrClassStringObjectType(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function isVoid(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
}
