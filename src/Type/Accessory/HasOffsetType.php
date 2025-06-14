<?php

declare (strict_types=1);
namespace PHPStan\Type\Accessory;

use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\TrinaryLogic;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\BooleanType;
use PHPStan\Type\CompoundType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\Traits\MaybeArrayTypeTrait;
use PHPStan\Type\Traits\MaybeCallableTypeTrait;
use PHPStan\Type\Traits\MaybeIterableTypeTrait;
use PHPStan\Type\Traits\MaybeObjectTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\NonRemoveableTypeTrait;
use PHPStan\Type\Traits\TruthyBooleanTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;
class HasOffsetType implements CompoundType, \PHPStan\Type\Accessory\AccessoryType
{
    /**
     * @var \PHPStan\Type\Constant\ConstantStringType|\PHPStan\Type\Constant\ConstantIntegerType
     */
    private $offsetType;
    use MaybeArrayTypeTrait;
    use MaybeCallableTypeTrait;
    use MaybeIterableTypeTrait;
    use MaybeObjectTypeTrait;
    use TruthyBooleanTypeTrait;
    use NonGenericTypeTrait;
    use UndecidedComparisonCompoundTypeTrait;
    use NonRemoveableTypeTrait;
    use NonGeneralizableTypeTrait;
    /**
     * @api
     * @param \PHPStan\Type\Constant\ConstantStringType|\PHPStan\Type\Constant\ConstantIntegerType $offsetType
     */
    public function __construct($offsetType)
    {
        $this->offsetType = $offsetType;
    }
    /**
     * @return ConstantStringType|ConstantIntegerType
     */
    public function getOffsetType(): Type
    {
        return $this->offsetType;
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
    public function accepts(Type $type, bool $strictTypes): AcceptsResult
    {
        if ($type instanceof CompoundType) {
            return $type->isAcceptedBy($this, $strictTypes);
        }
        return new AcceptsResult($type->isOffsetAccessible()->and($type->hasOffsetValueType($this->offsetType)), []);
    }
    public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
    {
        if ($this->equals($type)) {
            return IsSuperTypeOfResult::createYes();
        }
        return new IsSuperTypeOfResult($type->isOffsetAccessible()->and($type->hasOffsetValueType($this->offsetType)), []);
    }
    public function isSubTypeOf(Type $otherType): IsSuperTypeOfResult
    {
        if ($otherType instanceof UnionType || $otherType instanceof IntersectionType) {
            return $otherType->isSuperTypeOf($this);
        }
        $result = new IsSuperTypeOfResult($otherType->isOffsetAccessible()->and($otherType->hasOffsetValueType($this->offsetType)), []);
        return $result->and($otherType instanceof self ? IsSuperTypeOfResult::createYes() : IsSuperTypeOfResult::createMaybe());
    }
    public function isAcceptedBy(Type $acceptingType, bool $strictTypes): AcceptsResult
    {
        return $this->isSubTypeOf($acceptingType)->toAcceptsResult();
    }
    public function equals(Type $type): bool
    {
        return $type instanceof self && $this->offsetType->equals($type->offsetType);
    }
    public function describe(VerbosityLevel $level): string
    {
        return sprintf('hasOffset(%s)', $this->offsetType->describe($level));
    }
    public function isOffsetAccessible(): TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function isOffsetAccessLegal(): TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function hasOffsetValueType(Type $offsetType): TrinaryLogic
    {
        if ($offsetType->isConstantScalarValue()->yes() && $offsetType->equals($this->offsetType)) {
            return TrinaryLogic::createYes();
        }
        return TrinaryLogic::createMaybe();
    }
    public function getOffsetValueType(Type $offsetType): Type
    {
        return new MixedType();
    }
    public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $unionValues = \true): Type
    {
        return $this;
    }
    public function setExistingOffsetValueType(Type $offsetType, Type $valueType): Type
    {
        return $this;
    }
    public function unsetOffset(Type $offsetType): Type
    {
        if ($this->offsetType->isSuperTypeOf($offsetType)->yes()) {
            return new ErrorType();
        }
        return $this;
    }
    public function chunkArray(Type $lengthType, TrinaryLogic $preserveKeys): Type
    {
        return new \PHPStan\Type\Accessory\NonEmptyArrayType();
    }
    public function fillKeysArray(Type $valueType): Type
    {
        return new \PHPStan\Type\Accessory\NonEmptyArrayType();
    }
    public function intersectKeyArray(Type $otherArraysType): Type
    {
        if ($otherArraysType->hasOffsetValueType($this->offsetType)->yes()) {
            return $this;
        }
        return new MixedType();
    }
    public function reverseArray(TrinaryLogic $preserveKeys): Type
    {
        if ($preserveKeys->yes()) {
            return $this;
        }
        return new \PHPStan\Type\Accessory\NonEmptyArrayType();
    }
    public function shuffleArray(): Type
    {
        return new \PHPStan\Type\Accessory\NonEmptyArrayType();
    }
    public function sliceArray(Type $offsetType, Type $lengthType, TrinaryLogic $preserveKeys): Type
    {
        if ($this->offsetType->isSuperTypeOf($offsetType)->yes() && ($lengthType->isNull()->yes() || IntegerRangeType::fromInterval(1, null)->isSuperTypeOf($lengthType)->yes())) {
            return $preserveKeys->yes() ? TypeCombinator::intersect($this, new \PHPStan\Type\Accessory\NonEmptyArrayType()) : new \PHPStan\Type\Accessory\NonEmptyArrayType();
        }
        return new MixedType();
    }
    public function isIterableAtLeastOnce(): TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function isList(): TrinaryLogic
    {
        if ($this->offsetType->isString()->yes()) {
            return TrinaryLogic::createNo();
        }
        return TrinaryLogic::createMaybe();
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
        return TrinaryLogic::createMaybe();
    }
    public function isNumericString(): TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
    public function isNonEmptyString(): TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
    public function isNonFalsyString(): TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
    public function isLiteralString(): TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
    public function isLowercaseString(): TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
    public function isClassString(): TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
    public function isUppercaseString(): TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
    public function getClassStringObjectType(): Type
    {
        return new ObjectWithoutClassType();
    }
    public function getObjectTypeOrClassStringObjectType(): Type
    {
        return new ObjectWithoutClassType();
    }
    public function isVoid(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isScalar(): TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
    public function looseCompare(Type $type, PhpVersion $phpVersion): BooleanType
    {
        return new BooleanType();
    }
    public function getKeysArray(): Type
    {
        return new \PHPStan\Type\Accessory\NonEmptyArrayType();
    }
    public function getValuesArray(): Type
    {
        return new \PHPStan\Type\Accessory\NonEmptyArrayType();
    }
    public function toNumber(): Type
    {
        return new ErrorType();
    }
    public function toAbsoluteNumber(): Type
    {
        return new ErrorType();
    }
    public function toInteger(): Type
    {
        return new ErrorType();
    }
    public function toFloat(): Type
    {
        return new ErrorType();
    }
    public function toString(): Type
    {
        return new ErrorType();
    }
    public function toArray(): Type
    {
        return new MixedType();
    }
    public function toArrayKey(): Type
    {
        return new ErrorType();
    }
    public function toCoercedArgumentType(bool $strictTypes): Type
    {
        return $this;
    }
    public function getEnumCases(): array
    {
        return [];
    }
    public function traverse(callable $cb): Type
    {
        return $this;
    }
    public function traverseSimultaneously(Type $right, callable $cb): Type
    {
        return $this;
    }
    public function exponentiate(Type $exponent): Type
    {
        return new ErrorType();
    }
    public function getFiniteTypes(): array
    {
        return [];
    }
    public function toPhpDocNode(): TypeNode
    {
        return new IdentifierTypeNode('');
        // no PHPDoc representation
    }
}
