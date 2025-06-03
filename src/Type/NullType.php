<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Traits\FalseyBooleanTypeTrait;
use PHPStan\Type\Traits\NonArrayTypeTrait;
use PHPStan\Type\Traits\NonCallableTypeTrait;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\NonIterableTypeTrait;
use PHPStan\Type\Traits\NonObjectTypeTrait;
use PHPStan\Type\Traits\NonRemoveableTypeTrait;
/** @api */
class NullType implements \PHPStan\Type\ConstantScalarType
{
    use NonArrayTypeTrait;
    use NonCallableTypeTrait;
    use NonIterableTypeTrait;
    use NonObjectTypeTrait;
    use FalseyBooleanTypeTrait;
    use NonGenericTypeTrait;
    use NonRemoveableTypeTrait;
    /** @api */
    public function __construct()
    {
    }
    public function getReferencedClasses() : array
    {
        return [];
    }
    public function getObjectClassNames() : array
    {
        return [];
    }
    public function getObjectClassReflections() : array
    {
        return [];
    }
    public function getConstantStrings() : array
    {
        return [];
    }
    /**
     * @return null
     */
    public function getValue()
    {
        return null;
    }
    public function generalize(\PHPStan\Type\GeneralizePrecision $precision) : \PHPStan\Type\Type
    {
        return $this;
    }
    public function accepts(\PHPStan\Type\Type $type, bool $strictTypes) : \PHPStan\Type\AcceptsResult
    {
        if ($type instanceof self) {
            return \PHPStan\Type\AcceptsResult::createYes();
        }
        if ($type instanceof \PHPStan\Type\CompoundType) {
            return $type->isAcceptedBy($this, $strictTypes);
        }
        return \PHPStan\Type\AcceptsResult::createNo();
    }
    public function isSuperTypeOf(\PHPStan\Type\Type $type) : \PHPStan\Type\IsSuperTypeOfResult
    {
        if ($type instanceof self) {
            return \PHPStan\Type\IsSuperTypeOfResult::createYes();
        }
        if ($type instanceof \PHPStan\Type\CompoundType) {
            return $type->isSubTypeOf($this);
        }
        return \PHPStan\Type\IsSuperTypeOfResult::createNo();
    }
    public function equals(\PHPStan\Type\Type $type) : bool
    {
        return $type instanceof self;
    }
    public function isSmallerThan(\PHPStan\Type\Type $otherType, PhpVersion $phpVersion) : TrinaryLogic
    {
        if ($otherType instanceof \PHPStan\Type\ConstantScalarType) {
            return TrinaryLogic::createFromBoolean(null < $otherType->getValue());
        }
        if ($otherType instanceof \PHPStan\Type\CompoundType) {
            return $otherType->isGreaterThan($this, $phpVersion);
        }
        return TrinaryLogic::createMaybe();
    }
    public function isSmallerThanOrEqual(\PHPStan\Type\Type $otherType, PhpVersion $phpVersion) : TrinaryLogic
    {
        if ($otherType instanceof \PHPStan\Type\ConstantScalarType) {
            return TrinaryLogic::createFromBoolean(null <= $otherType->getValue());
        }
        if ($otherType instanceof \PHPStan\Type\CompoundType) {
            return $otherType->isGreaterThanOrEqual($this, $phpVersion);
        }
        return TrinaryLogic::createMaybe();
    }
    public function describe(\PHPStan\Type\VerbosityLevel $level) : string
    {
        return 'null';
    }
    public function toNumber() : \PHPStan\Type\Type
    {
        return new ConstantIntegerType(0);
    }
    public function toAbsoluteNumber() : \PHPStan\Type\Type
    {
        return $this->toNumber()->toAbsoluteNumber();
    }
    public function toString() : \PHPStan\Type\Type
    {
        return new ConstantStringType('');
    }
    public function toInteger() : \PHPStan\Type\Type
    {
        return $this->toNumber();
    }
    public function toFloat() : \PHPStan\Type\Type
    {
        return $this->toNumber()->toFloat();
    }
    public function toArray() : \PHPStan\Type\Type
    {
        return new ConstantArrayType([], []);
    }
    public function toArrayKey() : \PHPStan\Type\Type
    {
        return new ConstantStringType('');
    }
    public function toCoercedArgumentType(bool $strictTypes) : \PHPStan\Type\Type
    {
        return $this;
    }
    public function isOffsetAccessible() : TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function isOffsetAccessLegal() : TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function hasOffsetValueType(\PHPStan\Type\Type $offsetType) : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function getOffsetValueType(\PHPStan\Type\Type $offsetType) : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function setOffsetValueType(?\PHPStan\Type\Type $offsetType, \PHPStan\Type\Type $valueType, bool $unionValues = \true) : \PHPStan\Type\Type
    {
        $array = new ConstantArrayType([], []);
        return $array->setOffsetValueType($offsetType, $valueType, $unionValues);
    }
    public function setExistingOffsetValueType(\PHPStan\Type\Type $offsetType, \PHPStan\Type\Type $valueType) : \PHPStan\Type\Type
    {
        return $this;
    }
    public function unsetOffset(\PHPStan\Type\Type $offsetType) : \PHPStan\Type\Type
    {
        return $this;
    }
    public function traverse(callable $cb) : \PHPStan\Type\Type
    {
        return $this;
    }
    public function traverseSimultaneously(\PHPStan\Type\Type $right, callable $cb) : \PHPStan\Type\Type
    {
        return $this;
    }
    public function isNull() : TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function isConstantValue() : TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function isConstantScalarValue() : TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function getConstantScalarTypes() : array
    {
        return [$this];
    }
    public function getConstantScalarValues() : array
    {
        return [$this->getValue()];
    }
    public function isTrue() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isFalse() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isBoolean() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isFloat() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isInteger() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isString() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isNumericString() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isNonEmptyString() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isNonFalsyString() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isLiteralString() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isLowercaseString() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isUppercaseString() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isClassString() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function getClassStringObjectType() : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function getObjectTypeOrClassStringObjectType() : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function isVoid() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isScalar() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function looseCompare(\PHPStan\Type\Type $type, PhpVersion $phpVersion) : \PHPStan\Type\BooleanType
    {
        if ($type instanceof \PHPStan\Type\ConstantScalarType) {
            return \PHPStan\Type\LooseComparisonHelper::compareConstantScalars($this, $type, $phpVersion);
        }
        if ($type->isConstantArray()->yes() && $type->isIterableAtLeastOnce()->no()) {
            // @phpstan-ignore equal.alwaysTrue, equal.notAllowed
            return new ConstantBooleanType($this->getValue() == []);
            // phpcs:ignore
        }
        if ($type instanceof \PHPStan\Type\CompoundType) {
            return $type->looseCompare($this, $phpVersion);
        }
        return new \PHPStan\Type\BooleanType();
    }
    public function getSmallerType(PhpVersion $phpVersion) : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\NeverType();
    }
    public function getSmallerOrEqualType(PhpVersion $phpVersion) : \PHPStan\Type\Type
    {
        // All falsey types except '0'
        return new \PHPStan\Type\UnionType([new \PHPStan\Type\NullType(), new ConstantBooleanType(\false), new ConstantIntegerType(0), new ConstantFloatType(0.0), new ConstantStringType(''), new ConstantArrayType([], [])]);
    }
    public function getGreaterType(PhpVersion $phpVersion) : \PHPStan\Type\Type
    {
        // All truthy types, but also '0'
        return new \PHPStan\Type\MixedType(\false, new \PHPStan\Type\UnionType([new \PHPStan\Type\NullType(), new ConstantBooleanType(\false), new ConstantIntegerType(0), new ConstantFloatType(0.0), new ConstantStringType(''), new ConstantArrayType([], [])]));
    }
    public function getGreaterOrEqualType(PhpVersion $phpVersion) : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\MixedType();
    }
    public function getFiniteTypes() : array
    {
        return [$this];
    }
    public function exponentiate(\PHPStan\Type\Type $exponent) : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\UnionType([new ConstantIntegerType(0), new ConstantIntegerType(1)]);
    }
    public function toPhpDocNode() : TypeNode
    {
        return new IdentifierTypeNode('null');
    }
}
