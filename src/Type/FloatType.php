<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Traits\NonArrayTypeTrait;
use PHPStan\Type\Traits\NonCallableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\NonIterableTypeTrait;
use PHPStan\Type\Traits\NonObjectTypeTrait;
use PHPStan\Type\Traits\NonOffsetAccessibleTypeTrait;
use PHPStan\Type\Traits\NonRemoveableTypeTrait;
use PHPStan\Type\Traits\UndecidedBooleanTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonTypeTrait;
use function get_class;
/** @api */
class FloatType implements \PHPStan\Type\Type
{
    use NonArrayTypeTrait;
    use NonCallableTypeTrait;
    use NonIterableTypeTrait;
    use NonObjectTypeTrait;
    use UndecidedBooleanTypeTrait;
    use UndecidedComparisonTypeTrait;
    use NonGenericTypeTrait;
    use NonOffsetAccessibleTypeTrait;
    use NonRemoveableTypeTrait;
    use NonGeneralizableTypeTrait;
    /** @api */
    public function __construct()
    {
    }
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
    public function getConstantStrings(): array
    {
        return [];
    }
    public function accepts(\PHPStan\Type\Type $type, bool $strictTypes): \PHPStan\Type\AcceptsResult
    {
        if ($type instanceof self || $type->isInteger()->yes()) {
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
    public function describe(\PHPStan\Type\VerbosityLevel $level): string
    {
        return 'float';
    }
    public function toNumber(): \PHPStan\Type\Type
    {
        return $this;
    }
    public function toAbsoluteNumber(): \PHPStan\Type\Type
    {
        return $this;
    }
    public function toFloat(): \PHPStan\Type\Type
    {
        return $this;
    }
    public function toInteger(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\IntegerType();
    }
    public function toString(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\IntersectionType([new \PHPStan\Type\StringType(), new AccessoryUppercaseStringType(), new AccessoryNumericStringType()]);
    }
    public function toArray(): \PHPStan\Type\Type
    {
        return new ConstantArrayType([new ConstantIntegerType(0)], [$this], [1], [], TrinaryLogic::createYes());
    }
    public function toArrayKey(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\IntegerType();
    }
    public function toCoercedArgumentType(bool $strictTypes): \PHPStan\Type\Type
    {
        if (!$strictTypes) {
            return \PHPStan\Type\TypeCombinator::union($this->toInteger(), $this, $this->toString(), $this->toBoolean());
        }
        return $this;
    }
    public function isOffsetAccessLegal(): TrinaryLogic
    {
        return TrinaryLogic::createYes();
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
        return TrinaryLogic::createYes();
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
    public function isScalar(): TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function looseCompare(\PHPStan\Type\Type $type, PhpVersion $phpVersion): \PHPStan\Type\BooleanType
    {
        return new \PHPStan\Type\BooleanType();
    }
    public function traverse(callable $cb): \PHPStan\Type\Type
    {
        return $this;
    }
    public function traverseSimultaneously(\PHPStan\Type\Type $right, callable $cb): \PHPStan\Type\Type
    {
        return $this;
    }
    public function exponentiate(\PHPStan\Type\Type $exponent): \PHPStan\Type\Type
    {
        return \PHPStan\Type\ExponentiateHelper::exponentiate($this, $exponent);
    }
    public function toPhpDocNode(): TypeNode
    {
        return new IdentifierTypeNode('float');
    }
    public function getFiniteTypes(): array
    {
        return [];
    }
}
