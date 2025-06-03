<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\TrivialParametersAcceptor;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\TemplateMixedType;
use PHPStan\Type\Generic\TemplateStrictMixedType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\ArrayTypeTrait;
use PHPStan\Type\Traits\MaybeCallableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonObjectTypeTrait;
use PHPStan\Type\Traits\UndecidedBooleanTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonTypeTrait;
use function array_merge;
use function count;
use function sprintf;
/** @api */
class ArrayType implements \PHPStan\Type\Type
{
    private \PHPStan\Type\Type $itemType;
    use ArrayTypeTrait;
    use MaybeCallableTypeTrait;
    use NonObjectTypeTrait;
    use UndecidedBooleanTypeTrait;
    use UndecidedComparisonTypeTrait;
    use NonGeneralizableTypeTrait;
    private \PHPStan\Type\Type $keyType;
    /** @api */
    public function __construct(\PHPStan\Type\Type $keyType, \PHPStan\Type\Type $itemType)
    {
        $this->itemType = $itemType;
        if ($keyType->describe(\PHPStan\Type\VerbosityLevel::value()) === '(int|string)') {
            $keyType = new \PHPStan\Type\MixedType();
        }
        if ($keyType instanceof \PHPStan\Type\StrictMixedType && !$keyType instanceof TemplateStrictMixedType) {
            $keyType = new \PHPStan\Type\UnionType([new \PHPStan\Type\StringType(), new \PHPStan\Type\IntegerType()]);
        }
        $this->keyType = $keyType;
    }
    public function getKeyType() : \PHPStan\Type\Type
    {
        return $this->keyType;
    }
    public function getItemType() : \PHPStan\Type\Type
    {
        return $this->itemType;
    }
    public function getReferencedClasses() : array
    {
        return array_merge($this->keyType->getReferencedClasses(), $this->getItemType()->getReferencedClasses());
    }
    public function getConstantArrays() : array
    {
        return [];
    }
    public function accepts(\PHPStan\Type\Type $type, bool $strictTypes) : \PHPStan\Type\AcceptsResult
    {
        if ($type instanceof \PHPStan\Type\CompoundType) {
            return $type->isAcceptedBy($this, $strictTypes);
        }
        if ($type instanceof ConstantArrayType) {
            $result = \PHPStan\Type\AcceptsResult::createYes();
            $thisKeyType = $this->keyType;
            $itemType = $this->getItemType();
            foreach ($type->getKeyTypes() as $i => $keyType) {
                $valueType = $type->getValueTypes()[$i];
                $acceptsKey = $thisKeyType->accepts($keyType, $strictTypes);
                $acceptsValue = $itemType->accepts($valueType, $strictTypes);
                $result = $result->and($acceptsKey)->and($acceptsValue);
            }
            return $result;
        }
        if ($type instanceof \PHPStan\Type\ArrayType) {
            return $this->getItemType()->accepts($type->getItemType(), $strictTypes)->and($this->keyType->accepts($type->keyType, $strictTypes));
        }
        return \PHPStan\Type\AcceptsResult::createNo();
    }
    public function isSuperTypeOf(\PHPStan\Type\Type $type) : \PHPStan\Type\IsSuperTypeOfResult
    {
        if ($type instanceof self || $type instanceof ConstantArrayType) {
            return $this->getItemType()->isSuperTypeOf($type->getItemType())->and($this->getIterableKeyType()->isSuperTypeOf($type->getIterableKeyType()));
        }
        if ($type instanceof \PHPStan\Type\CompoundType) {
            return $type->isSubTypeOf($this);
        }
        return \PHPStan\Type\IsSuperTypeOfResult::createNo();
    }
    public function equals(\PHPStan\Type\Type $type) : bool
    {
        return $type instanceof self && $this->getItemType()->equals($type->getIterableValueType()) && $this->keyType->equals($type->keyType);
    }
    public function describe(\PHPStan\Type\VerbosityLevel $level) : string
    {
        $isMixedKeyType = $this->keyType instanceof \PHPStan\Type\MixedType && $this->keyType->describe(\PHPStan\Type\VerbosityLevel::precise()) === 'mixed' && !$this->keyType->isExplicitMixed();
        $isMixedItemType = $this->itemType instanceof \PHPStan\Type\MixedType && $this->itemType->describe(\PHPStan\Type\VerbosityLevel::precise()) === 'mixed' && !$this->itemType->isExplicitMixed();
        $valueHandler = function () use($level, $isMixedKeyType, $isMixedItemType) : string {
            if ($isMixedKeyType || $this->keyType instanceof \PHPStan\Type\NeverType) {
                if ($isMixedItemType || $this->itemType instanceof \PHPStan\Type\NeverType) {
                    return 'array';
                }
                return sprintf('array<%s>', $this->itemType->describe($level));
            }
            return sprintf('array<%s, %s>', $this->keyType->describe($level), $this->itemType->describe($level));
        };
        return $level->handle($valueHandler, $valueHandler, function () use($level, $isMixedKeyType, $isMixedItemType) : string {
            if ($isMixedKeyType) {
                if ($isMixedItemType) {
                    return 'array';
                }
                return sprintf('array<%s>', $this->itemType->describe($level));
            }
            return sprintf('array<%s, %s>', $this->keyType->describe($level), $this->itemType->describe($level));
        });
    }
    public function generalizeValues() : self
    {
        return new self($this->keyType, $this->itemType->generalize(\PHPStan\Type\GeneralizePrecision::lessSpecific()));
    }
    public function getKeysArray() : \PHPStan\Type\Type
    {
        return \PHPStan\Type\TypeCombinator::intersect(new self(new \PHPStan\Type\IntegerType(), $this->getIterableKeyType()), new AccessoryArrayListType());
    }
    public function getValuesArray() : \PHPStan\Type\Type
    {
        return \PHPStan\Type\TypeCombinator::intersect(new self(new \PHPStan\Type\IntegerType(), $this->itemType), new AccessoryArrayListType());
    }
    public function isIterableAtLeastOnce() : TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
    public function getArraySize() : \PHPStan\Type\Type
    {
        return \PHPStan\Type\IntegerRangeType::fromInterval(0, null);
    }
    public function getIterableKeyType() : \PHPStan\Type\Type
    {
        $keyType = $this->keyType;
        if ($keyType instanceof \PHPStan\Type\MixedType && !$keyType instanceof TemplateMixedType) {
            return new \PHPStan\Type\BenevolentUnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]);
        }
        if ($keyType instanceof \PHPStan\Type\StrictMixedType) {
            return new \PHPStan\Type\BenevolentUnionType([new \PHPStan\Type\IntegerType(), new \PHPStan\Type\StringType()]);
        }
        return $keyType;
    }
    public function getFirstIterableKeyType() : \PHPStan\Type\Type
    {
        return $this->getIterableKeyType();
    }
    public function getLastIterableKeyType() : \PHPStan\Type\Type
    {
        return $this->getIterableKeyType();
    }
    public function getIterableValueType() : \PHPStan\Type\Type
    {
        return $this->getItemType();
    }
    public function getFirstIterableValueType() : \PHPStan\Type\Type
    {
        return $this->getItemType();
    }
    public function getLastIterableValueType() : \PHPStan\Type\Type
    {
        return $this->getItemType();
    }
    public function isConstantArray() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isList() : TrinaryLogic
    {
        if (\PHPStan\Type\IntegerRangeType::fromInterval(0, null)->isSuperTypeOf($this->getKeyType())->no()) {
            return TrinaryLogic::createNo();
        }
        return TrinaryLogic::createMaybe();
    }
    public function isConstantValue() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function looseCompare(\PHPStan\Type\Type $type, PhpVersion $phpVersion) : \PHPStan\Type\BooleanType
    {
        if ($type->isInteger()->yes()) {
            return new ConstantBooleanType(\false);
        }
        return new \PHPStan\Type\BooleanType();
    }
    public function hasOffsetValueType(\PHPStan\Type\Type $offsetType) : TrinaryLogic
    {
        $offsetType = $offsetType->toArrayKey();
        if ($this->getKeyType()->isSuperTypeOf($offsetType)->no() && ($offsetType->isString()->no() || !$offsetType->isConstantScalarValue()->no())) {
            return TrinaryLogic::createNo();
        }
        return TrinaryLogic::createMaybe();
    }
    public function getOffsetValueType(\PHPStan\Type\Type $offsetType) : \PHPStan\Type\Type
    {
        $offsetType = $offsetType->toArrayKey();
        if ($this->getKeyType()->isSuperTypeOf($offsetType)->no() && ($offsetType->isString()->no() || !$offsetType->isConstantScalarValue()->no())) {
            return new \PHPStan\Type\ErrorType();
        }
        $type = $this->getItemType();
        if ($type instanceof \PHPStan\Type\ErrorType) {
            return new \PHPStan\Type\MixedType();
        }
        return $type;
    }
    public function setOffsetValueType(?\PHPStan\Type\Type $offsetType, \PHPStan\Type\Type $valueType, bool $unionValues = \true) : \PHPStan\Type\Type
    {
        if ($offsetType === null) {
            $isKeyTypeInteger = $this->keyType->isInteger();
            if ($isKeyTypeInteger->no()) {
                $offsetType = new \PHPStan\Type\IntegerType();
            } elseif ($isKeyTypeInteger->yes()) {
                /** @var list<ConstantIntegerType> $constantScalars */
                $constantScalars = $this->keyType->getConstantScalarTypes();
                if (count($constantScalars) > 0) {
                    foreach ($constantScalars as $constantScalar) {
                        $constantScalars[] = \PHPStan\Type\ConstantTypeHelper::getTypeFromValue($constantScalar->getValue() + 1);
                    }
                    $offsetType = \PHPStan\Type\TypeCombinator::union(...$constantScalars);
                } else {
                    $offsetType = $this->keyType;
                }
            } else {
                $integerTypes = [];
                \PHPStan\Type\TypeTraverser::map($this->keyType, static function (\PHPStan\Type\Type $type, callable $traverse) use(&$integerTypes) : \PHPStan\Type\Type {
                    if ($type instanceof \PHPStan\Type\UnionType) {
                        return $traverse($type);
                    }
                    $isInteger = $type->isInteger();
                    if ($isInteger->yes()) {
                        $integerTypes[] = $type;
                    }
                    return $type;
                });
                if (count($integerTypes) === 0) {
                    $offsetType = $this->keyType;
                } else {
                    $offsetType = \PHPStan\Type\TypeCombinator::union(...$integerTypes);
                }
            }
        } else {
            $offsetType = $offsetType->toArrayKey();
        }
        if ($offsetType instanceof ConstantStringType || $offsetType instanceof ConstantIntegerType) {
            if ($offsetType->isSuperTypeOf($this->keyType)->yes()) {
                $builder = ConstantArrayTypeBuilder::createEmpty();
                $builder->setOffsetValueType($offsetType, $valueType);
                return $builder->getArray();
            }
            return \PHPStan\Type\TypeCombinator::intersect(new self(\PHPStan\Type\TypeCombinator::union($this->keyType, $offsetType), \PHPStan\Type\TypeCombinator::union($this->itemType, $valueType)), new HasOffsetValueType($offsetType, $valueType), new NonEmptyArrayType());
        }
        return \PHPStan\Type\TypeCombinator::intersect(new self(\PHPStan\Type\TypeCombinator::union($this->keyType, $offsetType), $unionValues ? \PHPStan\Type\TypeCombinator::union($this->itemType, $valueType) : $valueType), new NonEmptyArrayType());
    }
    public function setExistingOffsetValueType(\PHPStan\Type\Type $offsetType, \PHPStan\Type\Type $valueType) : \PHPStan\Type\Type
    {
        return new self($this->keyType, \PHPStan\Type\TypeCombinator::union($this->itemType, $valueType));
    }
    public function unsetOffset(\PHPStan\Type\Type $offsetType) : \PHPStan\Type\Type
    {
        $offsetType = $offsetType->toArrayKey();
        if (($offsetType instanceof ConstantIntegerType || $offsetType instanceof ConstantStringType) && !$this->keyType->isSuperTypeOf($offsetType)->no()) {
            $keyType = \PHPStan\Type\TypeCombinator::remove($this->keyType, $offsetType);
            if ($keyType instanceof \PHPStan\Type\NeverType) {
                return new ConstantArrayType([], []);
            }
            return new self($keyType, $this->itemType);
        }
        return $this;
    }
    public function fillKeysArray(\PHPStan\Type\Type $valueType) : \PHPStan\Type\Type
    {
        $itemType = $this->getItemType();
        if ($itemType->isInteger()->no()) {
            $stringKeyType = $itemType->toString();
            if ($stringKeyType instanceof \PHPStan\Type\ErrorType) {
                return $stringKeyType;
            }
            return new \PHPStan\Type\ArrayType($stringKeyType, $valueType);
        }
        return new \PHPStan\Type\ArrayType($itemType, $valueType);
    }
    public function flipArray() : \PHPStan\Type\Type
    {
        return new self($this->getIterableValueType()->toArrayKey(), $this->getIterableKeyType());
    }
    public function intersectKeyArray(\PHPStan\Type\Type $otherArraysType) : \PHPStan\Type\Type
    {
        $isKeySuperType = $otherArraysType->getIterableKeyType()->isSuperTypeOf($this->getIterableKeyType());
        if ($isKeySuperType->no()) {
            return ConstantArrayTypeBuilder::createEmpty()->getArray();
        }
        if ($isKeySuperType->yes()) {
            return $this;
        }
        return new self($otherArraysType->getIterableKeyType(), $this->getIterableValueType());
    }
    public function popArray() : \PHPStan\Type\Type
    {
        return $this;
    }
    public function reverseArray(TrinaryLogic $preserveKeys) : \PHPStan\Type\Type
    {
        return $this;
    }
    public function searchArray(\PHPStan\Type\Type $needleType) : \PHPStan\Type\Type
    {
        return \PHPStan\Type\TypeCombinator::union($this->getIterableKeyType(), new ConstantBooleanType(\false));
    }
    public function shiftArray() : \PHPStan\Type\Type
    {
        return $this;
    }
    public function shuffleArray() : \PHPStan\Type\Type
    {
        return \PHPStan\Type\TypeCombinator::intersect(new self(new \PHPStan\Type\IntegerType(), $this->itemType), new AccessoryArrayListType());
    }
    public function sliceArray(\PHPStan\Type\Type $offsetType, \PHPStan\Type\Type $lengthType, TrinaryLogic $preserveKeys) : \PHPStan\Type\Type
    {
        if ((new ConstantIntegerType(0))->isSuperTypeOf($lengthType)->yes()) {
            return new ConstantArrayType([], []);
        }
        if ($preserveKeys->no() && $this->keyType->isInteger()->yes()) {
            return \PHPStan\Type\TypeCombinator::intersect(new self(new \PHPStan\Type\IntegerType(), $this->itemType), new AccessoryArrayListType());
        }
        return $this;
    }
    public function isCallable() : TrinaryLogic
    {
        return TrinaryLogic::createMaybe()->and($this->itemType->isString());
    }
    public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope) : array
    {
        if ($this->isCallable()->no()) {
            throw new ShouldNotHappenException();
        }
        return [new TrivialParametersAcceptor()];
    }
    public function toInteger() : \PHPStan\Type\Type
    {
        return \PHPStan\Type\TypeCombinator::union(new ConstantIntegerType(0), new ConstantIntegerType(1));
    }
    public function toFloat() : \PHPStan\Type\Type
    {
        return \PHPStan\Type\TypeCombinator::union(new ConstantFloatType(0.0), new ConstantFloatType(1.0));
    }
    public function inferTemplateTypes(\PHPStan\Type\Type $receivedType) : TemplateTypeMap
    {
        if ($receivedType instanceof \PHPStan\Type\UnionType || $receivedType instanceof \PHPStan\Type\IntersectionType) {
            return $receivedType->inferTemplateTypesOn($this);
        }
        if ($receivedType->isArray()->yes()) {
            $keyTypeMap = $this->getIterableKeyType()->inferTemplateTypes($receivedType->getIterableKeyType());
            $itemTypeMap = $this->getItemType()->inferTemplateTypes($receivedType->getIterableValueType());
            return $keyTypeMap->union($itemTypeMap);
        }
        return TemplateTypeMap::createEmpty();
    }
    public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance) : array
    {
        $variance = $positionVariance->compose(TemplateTypeVariance::createCovariant());
        return array_merge($this->getIterableKeyType()->getReferencedTemplateTypes($variance), $this->getItemType()->getReferencedTemplateTypes($variance));
    }
    public function traverse(callable $cb) : \PHPStan\Type\Type
    {
        $keyType = $cb($this->keyType);
        $itemType = $cb($this->itemType);
        if ($keyType !== $this->keyType || $itemType !== $this->itemType) {
            if ($keyType instanceof \PHPStan\Type\NeverType && $itemType instanceof \PHPStan\Type\NeverType) {
                return new ConstantArrayType([], []);
            }
            return new self($keyType, $itemType);
        }
        return $this;
    }
    public function toPhpDocNode() : TypeNode
    {
        $isMixedKeyType = $this->keyType instanceof \PHPStan\Type\MixedType && $this->keyType->describe(\PHPStan\Type\VerbosityLevel::precise()) === 'mixed' && !$this->keyType->isExplicitMixed();
        $isMixedItemType = $this->itemType instanceof \PHPStan\Type\MixedType && $this->itemType->describe(\PHPStan\Type\VerbosityLevel::precise()) === 'mixed' && !$this->itemType->isExplicitMixed();
        if ($isMixedKeyType) {
            if ($isMixedItemType) {
                return new IdentifierTypeNode('array');
            }
            return new GenericTypeNode(new IdentifierTypeNode('array'), [$this->itemType->toPhpDocNode()]);
        }
        return new GenericTypeNode(new IdentifierTypeNode('array'), [$this->keyType->toPhpDocNode(), $this->itemType->toPhpDocNode()]);
    }
    public function traverseSimultaneously(\PHPStan\Type\Type $right, callable $cb) : \PHPStan\Type\Type
    {
        $keyType = $cb($this->keyType, $right->getIterableKeyType());
        $itemType = $cb($this->itemType, $right->getIterableValueType());
        if ($keyType !== $this->keyType || $itemType !== $this->itemType) {
            if ($keyType instanceof \PHPStan\Type\NeverType && $itemType instanceof \PHPStan\Type\NeverType) {
                return new ConstantArrayType([], []);
            }
            return new self($keyType, $itemType);
        }
        return $this;
    }
    public function tryRemove(\PHPStan\Type\Type $typeToRemove) : ?\PHPStan\Type\Type
    {
        if ($typeToRemove->isConstantArray()->yes() && $typeToRemove->isIterableAtLeastOnce()->no()) {
            return \PHPStan\Type\TypeCombinator::intersect($this, new NonEmptyArrayType());
        }
        if ($typeToRemove instanceof NonEmptyArrayType) {
            return new ConstantArrayType([], []);
        }
        return null;
    }
    public function getFiniteTypes() : array
    {
        return [];
    }
}
