<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\ArrayShapeNode;
use PHPStan\PhpDocParser\Ast\Type\GenericTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\IntersectionTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\TrivialParametersAcceptor;
use PHPStan\Reflection\Type\IntersectionTypeUnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\IntersectionTypeUnresolvedPropertyPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\AccessoryType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\Accessory\HasOffsetType;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonRemoveableTypeTrait;
use function array_intersect_key;
use function array_map;
use function array_shift;
use function array_unique;
use function array_values;
use function count;
use function implode;
use function in_array;
use function is_int;
use function ksort;
use function md5;
use function sprintf;
use function strcasecmp;
use function strlen;
use function substr;
use function usort;
/** @api */
class IntersectionType implements \PHPStan\Type\CompoundType
{
    /**
     * @var Type[]
     */
    private array $types;
    use NonRemoveableTypeTrait;
    use NonGeneralizableTypeTrait;
    private bool $sortedTypes = \false;
    /**
     * @api
     * @param Type[] $types
     */
    public function __construct(array $types)
    {
        $this->types = $types;
        if (count($types) < 2) {
            throw new ShouldNotHappenException(sprintf('Cannot create %s with: %s', self::class, implode(', ', array_map(static fn(\PHPStan\Type\Type $type): string => $type->describe(\PHPStan\Type\VerbosityLevel::value()), $types))));
        }
    }
    /**
     * @return Type[]
     */
    public function getTypes(): array
    {
        return $this->types;
    }
    /**
     * @return Type[]
     */
    private function getSortedTypes(): array
    {
        if ($this->sortedTypes) {
            return $this->types;
        }
        $this->types = \PHPStan\Type\UnionTypeHelper::sortTypes($this->types);
        $this->sortedTypes = \true;
        return $this->types;
    }
    public function inferTemplateTypesOn(\PHPStan\Type\Type $templateType): TemplateTypeMap
    {
        $types = TemplateTypeMap::createEmpty();
        foreach ($this->types as $type) {
            $types = $types->intersect($templateType->inferTemplateTypes($type));
        }
        return $types;
    }
    public function getReferencedClasses(): array
    {
        $classes = [];
        foreach ($this->types as $type) {
            foreach ($type->getReferencedClasses() as $className) {
                $classes[] = $className;
            }
        }
        return $classes;
    }
    public function getObjectClassNames(): array
    {
        $objectClassNames = [];
        foreach ($this->types as $type) {
            $innerObjectClassNames = $type->getObjectClassNames();
            foreach ($innerObjectClassNames as $innerObjectClassName) {
                $objectClassNames[] = $innerObjectClassName;
            }
        }
        return array_values(array_unique($objectClassNames));
    }
    public function getObjectClassReflections(): array
    {
        $reflections = [];
        foreach ($this->types as $type) {
            foreach ($type->getObjectClassReflections() as $reflection) {
                $reflections[] = $reflection;
            }
        }
        return $reflections;
    }
    public function getArrays(): array
    {
        $arrays = [];
        foreach ($this->types as $type) {
            foreach ($type->getArrays() as $array) {
                $arrays[] = $array;
            }
        }
        return $arrays;
    }
    public function getConstantArrays(): array
    {
        $constantArrays = [];
        foreach ($this->types as $type) {
            foreach ($type->getConstantArrays() as $constantArray) {
                $constantArrays[] = $constantArray;
            }
        }
        return $constantArrays;
    }
    public function getConstantStrings(): array
    {
        $strings = [];
        foreach ($this->types as $type) {
            foreach ($type->getConstantStrings() as $string) {
                $strings[] = $string;
            }
        }
        return $strings;
    }
    public function accepts(\PHPStan\Type\Type $otherType, bool $strictTypes): \PHPStan\Type\AcceptsResult
    {
        $result = \PHPStan\Type\AcceptsResult::createYes();
        foreach ($this->types as $type) {
            $result = $result->and($type->accepts($otherType, $strictTypes));
        }
        if (!$result->yes()) {
            $isList = $otherType->isList();
            $reasons = $result->reasons;
            $verbosity = \PHPStan\Type\VerbosityLevel::getRecommendedLevelByType($this, $otherType);
            if ($this->isList()->yes() && !$isList->yes()) {
                $reasons[] = sprintf('%s %s a list.', $otherType->describe($verbosity), $isList->no() ? 'is not' : 'might not be');
            }
            $isNonEmpty = $otherType->isIterableAtLeastOnce();
            if ($this->isIterableAtLeastOnce()->yes() && !$isNonEmpty->yes()) {
                $reasons[] = sprintf('%s %s empty.', $otherType->describe($verbosity), $isNonEmpty->no() ? 'is' : 'might be');
            }
            if (count($reasons) > 0) {
                return new \PHPStan\Type\AcceptsResult($result->result, $reasons);
            }
        }
        return $result;
    }
    public function isSuperTypeOf(\PHPStan\Type\Type $otherType): \PHPStan\Type\IsSuperTypeOfResult
    {
        if ($otherType instanceof \PHPStan\Type\IntersectionType && $this->equals($otherType)) {
            return \PHPStan\Type\IsSuperTypeOfResult::createYes();
        }
        if ($otherType instanceof \PHPStan\Type\NeverType) {
            return \PHPStan\Type\IsSuperTypeOfResult::createYes();
        }
        return \PHPStan\Type\IsSuperTypeOfResult::createYes()->and(...array_map(static fn(\PHPStan\Type\Type $innerType) => $innerType->isSuperTypeOf($otherType), $this->types));
    }
    public function isSubTypeOf(\PHPStan\Type\Type $otherType): \PHPStan\Type\IsSuperTypeOfResult
    {
        if (($otherType instanceof self || $otherType instanceof \PHPStan\Type\UnionType) && !$otherType instanceof TemplateType) {
            return $otherType->isSuperTypeOf($this);
        }
        $result = \PHPStan\Type\IsSuperTypeOfResult::maxMin(...array_map(static fn(\PHPStan\Type\Type $innerType) => $otherType->isSuperTypeOf($innerType), $this->types));
        if ($this->isOversizedArray()->yes()) {
            if (!$result->no()) {
                return \PHPStan\Type\IsSuperTypeOfResult::createYes();
            }
        }
        return $result;
    }
    public function isAcceptedBy(\PHPStan\Type\Type $acceptingType, bool $strictTypes): \PHPStan\Type\AcceptsResult
    {
        $result = \PHPStan\Type\AcceptsResult::maxMin(...array_map(static fn(\PHPStan\Type\Type $innerType) => $acceptingType->accepts($innerType, $strictTypes), $this->types));
        if ($this->isOversizedArray()->yes()) {
            if (!$result->no()) {
                return \PHPStan\Type\AcceptsResult::createYes();
            }
        }
        return $result;
    }
    public function equals(\PHPStan\Type\Type $type): bool
    {
        if (!$type instanceof static) {
            return \false;
        }
        if (count($this->types) !== count($type->types)) {
            return \false;
        }
        $otherTypes = $type->types;
        foreach ($this->types as $innerType) {
            $match = \false;
            foreach ($otherTypes as $i => $otherType) {
                if (!$innerType->equals($otherType)) {
                    continue;
                }
                $match = \true;
                unset($otherTypes[$i]);
                break;
            }
            if (!$match) {
                return \false;
            }
        }
        return count($otherTypes) === 0;
    }
    public function describe(\PHPStan\Type\VerbosityLevel $level): string
    {
        return $level->handle(function () use ($level): string {
            $typeNames = [];
            $isList = $this->isList()->yes();
            $valueType = null;
            foreach ($this->getSortedTypes() as $type) {
                if ($isList) {
                    if ($type instanceof \PHPStan\Type\ArrayType || $type instanceof ConstantArrayType) {
                        $valueType = $type->getIterableValueType();
                        continue;
                    }
                    if ($type instanceof NonEmptyArrayType) {
                        continue;
                    }
                }
                if ($type instanceof AccessoryType) {
                    continue;
                }
                $typeNames[] = $type->generalize(\PHPStan\Type\GeneralizePrecision::lessSpecific())->describe($level);
            }
            if ($isList) {
                $isMixedValueType = $valueType instanceof \PHPStan\Type\MixedType && $valueType->describe(\PHPStan\Type\VerbosityLevel::precise()) === 'mixed' && !$valueType->isExplicitMixed();
                $innerType = '';
                if ($valueType !== null && !$isMixedValueType) {
                    $innerType = sprintf('<%s>', $valueType->describe($level));
                }
                $typeNames[] = 'list' . $innerType;
            }
            usort($typeNames, static function ($a, $b) {
                $cmp = strcasecmp($a, $b);
                if ($cmp !== 0) {
                    return $cmp;
                }
                return $a <=> $b;
            });
            return implode('&', $typeNames);
        }, fn(): string => $this->describeItself($level, \true), fn(): string => $this->describeItself($level, \false));
    }
    private function describeItself(\PHPStan\Type\VerbosityLevel $level, bool $skipAccessoryTypes): string
    {
        $baseTypes = [];
        $typesToDescribe = [];
        $skipTypeNames = [];
        $nonEmptyStr = \false;
        $nonFalsyStr = \false;
        $isList = $this->isList()->yes();
        $isArray = $this->isArray()->yes();
        $isNonEmptyArray = $this->isIterableAtLeastOnce()->yes();
        $describedTypes = [];
        foreach ($this->getSortedTypes() as $i => $type) {
            if ($type instanceof AccessoryNonEmptyStringType || $type instanceof AccessoryLiteralStringType || $type instanceof AccessoryNumericStringType || $type instanceof AccessoryNonFalsyStringType || $type instanceof AccessoryLowercaseStringType || $type instanceof AccessoryUppercaseStringType) {
                if (($type instanceof AccessoryLowercaseStringType || $type instanceof AccessoryUppercaseStringType) && !$level->isPrecise()) {
                    continue;
                }
                if ($type instanceof AccessoryNonFalsyStringType) {
                    $nonFalsyStr = \true;
                }
                if ($type instanceof AccessoryNonEmptyStringType) {
                    $nonEmptyStr = \true;
                }
                if ($nonEmptyStr && $nonFalsyStr) {
                    // prevent redundant 'non-empty-string&non-falsy-string'
                    foreach ($typesToDescribe as $key => $typeToDescribe) {
                        if (!$typeToDescribe instanceof AccessoryNonEmptyStringType) {
                            continue;
                        }
                        unset($typesToDescribe[$key]);
                    }
                }
                $typesToDescribe[$i] = $type;
                $skipTypeNames[] = 'string';
                continue;
            }
            if ($isList || $isArray) {
                if ($type instanceof \PHPStan\Type\ArrayType) {
                    $keyType = $type->getKeyType();
                    $valueType = $type->getItemType();
                    if ($isList) {
                        $isMixedValueType = $valueType instanceof \PHPStan\Type\MixedType && $valueType->describe(\PHPStan\Type\VerbosityLevel::precise()) === 'mixed' && !$valueType->isExplicitMixed();
                        $valueTypeDescription = '';
                        if (!$isMixedValueType) {
                            $valueTypeDescription = sprintf('<%s>', $valueType->describe($level));
                        }
                        $describedTypes[$i] = ($isNonEmptyArray ? 'non-empty-list' : 'list') . $valueTypeDescription;
                    } else {
                        $isMixedKeyType = $keyType instanceof \PHPStan\Type\MixedType && $keyType->describe(\PHPStan\Type\VerbosityLevel::precise()) === 'mixed' && !$keyType->isExplicitMixed();
                        $isMixedValueType = $valueType instanceof \PHPStan\Type\MixedType && $valueType->describe(\PHPStan\Type\VerbosityLevel::precise()) === 'mixed' && !$valueType->isExplicitMixed();
                        $typeDescription = '';
                        if (!$isMixedKeyType) {
                            $typeDescription = sprintf('<%s, %s>', $keyType->describe($level), $valueType->describe($level));
                        } elseif (!$isMixedValueType) {
                            $typeDescription = sprintf('<%s>', $valueType->describe($level));
                        }
                        $describedTypes[$i] = ($isNonEmptyArray ? 'non-empty-array' : 'array') . $typeDescription;
                    }
                    continue;
                } elseif ($type instanceof ConstantArrayType) {
                    $description = $type->describe($level);
                    $descriptionWithoutKind = substr($description, strlen('array'));
                    $begin = $isList ? 'list' : 'array';
                    if ($isNonEmptyArray && !$type->isIterableAtLeastOnce()->yes()) {
                        $begin = 'non-empty-' . $begin;
                    }
                    $describedTypes[$i] = $begin . $descriptionWithoutKind;
                    continue;
                }
                if ($type instanceof NonEmptyArrayType || $type instanceof AccessoryArrayListType) {
                    continue;
                }
            }
            if ($type instanceof \PHPStan\Type\CallableType && $type->isCommonCallable()) {
                $typesToDescribe[$i] = $type;
                $skipTypeNames[] = 'object';
                $skipTypeNames[] = 'string';
                continue;
            }
            if (!$type instanceof AccessoryType) {
                $baseTypes[$i] = $type;
                continue;
            }
            if ($skipAccessoryTypes) {
                continue;
            }
            $typesToDescribe[$i] = $type;
        }
        foreach ($baseTypes as $i => $type) {
            $typeDescription = $type->describe($level);
            if (in_array($typeDescription, ['object', 'string'], \true) && in_array($typeDescription, $skipTypeNames, \true)) {
                foreach ($typesToDescribe as $j => $typeToDescribe) {
                    if ($typeToDescribe instanceof \PHPStan\Type\CallableType && $typeToDescribe->isCommonCallable()) {
                        $describedTypes[$i] = 'callable-' . $typeDescription;
                        unset($typesToDescribe[$j]);
                        continue 2;
                    }
                }
            }
            if (in_array($typeDescription, $skipTypeNames, \true)) {
                continue;
            }
            $describedTypes[$i] = $type->describe($level);
        }
        foreach ($typesToDescribe as $i => $typeToDescribe) {
            $describedTypes[$i] = $typeToDescribe->describe($level);
        }
        ksort($describedTypes);
        return implode('&', $describedTypes);
    }
    public function getTemplateType(string $ancestorClassName, string $templateTypeName): \PHPStan\Type\Type
    {
        return $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->getTemplateType($ancestorClassName, $templateTypeName));
    }
    public function isObject(): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isObject());
    }
    public function isEnum(): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isEnum());
    }
    public function canAccessProperties(): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->canAccessProperties());
    }
    public function hasProperty(string $propertyName): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->hasProperty($propertyName));
    }
    public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): ExtendedPropertyReflection
    {
        return $this->getUnresolvedPropertyPrototype($propertyName, $scope)->getTransformedProperty();
    }
    public function getUnresolvedPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
    {
        $propertyPrototypes = [];
        foreach ($this->types as $type) {
            if (!$type->hasProperty($propertyName)->yes()) {
                continue;
            }
            $propertyPrototypes[] = $type->getUnresolvedPropertyPrototype($propertyName, $scope)->withFechedOnType($this);
        }
        $propertiesCount = count($propertyPrototypes);
        if ($propertiesCount === 0) {
            throw new ShouldNotHappenException();
        }
        if ($propertiesCount === 1) {
            return $propertyPrototypes[0];
        }
        return new IntersectionTypeUnresolvedPropertyPrototypeReflection($propertyName, $propertyPrototypes);
    }
    public function canCallMethods(): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->canCallMethods());
    }
    public function hasMethod(string $methodName): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->hasMethod($methodName));
    }
    public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope): ExtendedMethodReflection
    {
        return $this->getUnresolvedMethodPrototype($methodName, $scope)->getTransformedMethod();
    }
    public function getUnresolvedMethodPrototype(string $methodName, ClassMemberAccessAnswerer $scope): UnresolvedMethodPrototypeReflection
    {
        $methodPrototypes = [];
        foreach ($this->types as $type) {
            if (!$type->hasMethod($methodName)->yes()) {
                continue;
            }
            $methodPrototypes[] = $type->getUnresolvedMethodPrototype($methodName, $scope)->withCalledOnType($this);
        }
        $methodsCount = count($methodPrototypes);
        if ($methodsCount === 0) {
            throw new ShouldNotHappenException();
        }
        if ($methodsCount === 1) {
            return $methodPrototypes[0];
        }
        return new IntersectionTypeUnresolvedMethodPrototypeReflection($methodName, $methodPrototypes);
    }
    public function canAccessConstants(): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->canAccessConstants());
    }
    public function hasConstant(string $constantName): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->hasConstant($constantName));
    }
    public function getConstant(string $constantName): ClassConstantReflection
    {
        foreach ($this->types as $type) {
            if ($type->hasConstant($constantName)->yes()) {
                return $type->getConstant($constantName);
            }
        }
        throw new ShouldNotHappenException();
    }
    public function isIterable(): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isIterable());
    }
    public function isIterableAtLeastOnce(): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isIterableAtLeastOnce());
    }
    public function getArraySize(): \PHPStan\Type\Type
    {
        return $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->getArraySize());
    }
    public function getIterableKeyType(): \PHPStan\Type\Type
    {
        return $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->getIterableKeyType());
    }
    public function getFirstIterableKeyType(): \PHPStan\Type\Type
    {
        return $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->getFirstIterableKeyType());
    }
    public function getLastIterableKeyType(): \PHPStan\Type\Type
    {
        return $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->getLastIterableKeyType());
    }
    public function getIterableValueType(): \PHPStan\Type\Type
    {
        return $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->getIterableValueType());
    }
    public function getFirstIterableValueType(): \PHPStan\Type\Type
    {
        return $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->getFirstIterableValueType());
    }
    public function getLastIterableValueType(): \PHPStan\Type\Type
    {
        return $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->getLastIterableValueType());
    }
    public function isArray(): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isArray());
    }
    public function isConstantArray(): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isConstantArray());
    }
    public function isOversizedArray(): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isOversizedArray());
    }
    public function isList(): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isList());
    }
    public function isString(): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isString());
    }
    public function isNumericString(): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isNumericString());
    }
    public function isNonEmptyString(): TrinaryLogic
    {
        if ($this->isCallable()->yes() && $this->isString()->yes()) {
            return TrinaryLogic::createYes();
        }
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isNonEmptyString());
    }
    public function isNonFalsyString(): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isNonFalsyString());
    }
    public function isLiteralString(): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isLiteralString());
    }
    public function isLowercaseString(): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isLowercaseString());
    }
    public function isUppercaseString(): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isUppercaseString());
    }
    public function isClassString(): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isClassString());
    }
    public function getClassStringObjectType(): \PHPStan\Type\Type
    {
        return $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->getClassStringObjectType());
    }
    public function getObjectTypeOrClassStringObjectType(): \PHPStan\Type\Type
    {
        return $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->getObjectTypeOrClassStringObjectType());
    }
    public function isVoid(): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isVoid());
    }
    public function isScalar(): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isScalar());
    }
    public function looseCompare(\PHPStan\Type\Type $type, PhpVersion $phpVersion): \PHPStan\Type\BooleanType
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $innerType): TrinaryLogic => $innerType->looseCompare($type, $phpVersion)->toTrinaryLogic())->toBooleanType();
    }
    public function isOffsetAccessible(): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isOffsetAccessible());
    }
    public function isOffsetAccessLegal(): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isOffsetAccessLegal());
    }
    public function hasOffsetValueType(\PHPStan\Type\Type $offsetType): TrinaryLogic
    {
        if ($this->isList()->yes() && $this->isIterableAtLeastOnce()->yes()) {
            $arrayKeyOffsetType = $offsetType->toArrayKey();
            if ((new ConstantIntegerType(0))->isSuperTypeOf($arrayKeyOffsetType)->yes()) {
                return TrinaryLogic::createYes();
            }
            foreach ($this->types as $type) {
                if (!$type instanceof HasOffsetValueType && !$type instanceof HasOffsetType) {
                    continue;
                }
                foreach ($type->getOffsetType()->getConstantScalarValues() as $constantScalarValue) {
                    if (!is_int($constantScalarValue)) {
                        continue;
                    }
                    if (\PHPStan\Type\IntegerRangeType::fromInterval(0, $constantScalarValue)->isSuperTypeOf($arrayKeyOffsetType)->yes()) {
                        return TrinaryLogic::createYes();
                    }
                }
            }
        }
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->hasOffsetValueType($offsetType));
    }
    public function getOffsetValueType(\PHPStan\Type\Type $offsetType): \PHPStan\Type\Type
    {
        $result = $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->getOffsetValueType($offsetType));
        if ($this->isOversizedArray()->yes()) {
            return \PHPStan\Type\TypeUtils::toBenevolentUnion($result);
        }
        return $result;
    }
    public function setOffsetValueType(?\PHPStan\Type\Type $offsetType, \PHPStan\Type\Type $valueType, bool $unionValues = \true): \PHPStan\Type\Type
    {
        if ($this->isOversizedArray()->yes()) {
            return $this->intersectTypes(static function (\PHPStan\Type\Type $type) use ($offsetType, $valueType, $unionValues): \PHPStan\Type\Type {
                // avoid new HasOffsetValueType being intersected with oversized array
                if (!$type instanceof \PHPStan\Type\ArrayType) {
                    return $type->setOffsetValueType($offsetType, $valueType, $unionValues);
                }
                if (!$offsetType instanceof ConstantStringType && !$offsetType instanceof ConstantIntegerType) {
                    return $type->setOffsetValueType($offsetType, $valueType, $unionValues);
                }
                if (!$offsetType->isSuperTypeOf($type->getKeyType())->yes()) {
                    return $type->setOffsetValueType($offsetType, $valueType, $unionValues);
                }
                return \PHPStan\Type\TypeCombinator::intersect(new \PHPStan\Type\ArrayType(\PHPStan\Type\TypeCombinator::union($type->getKeyType(), $offsetType), \PHPStan\Type\TypeCombinator::union($type->getItemType(), $valueType)), new NonEmptyArrayType());
            });
        }
        $result = $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->setOffsetValueType($offsetType, $valueType, $unionValues));
        if ($offsetType !== null && $this->isList()->yes() && !$result->isList()->yes()) {
            if ($this->isIterableAtLeastOnce()->yes() && (new ConstantIntegerType(1))->isSuperTypeOf($offsetType)->yes()) {
                $result = \PHPStan\Type\TypeCombinator::intersect($result, new AccessoryArrayListType());
            } else {
                foreach ($this->types as $type) {
                    if (!$type instanceof HasOffsetValueType && !$type instanceof HasOffsetType) {
                        continue;
                    }
                    foreach ($type->getOffsetType()->getConstantScalarValues() as $constantScalarValue) {
                        if (!is_int($constantScalarValue)) {
                            continue;
                        }
                        if (\PHPStan\Type\IntegerRangeType::fromInterval(0, $constantScalarValue + 1)->isSuperTypeOf($offsetType)->yes()) {
                            $result = \PHPStan\Type\TypeCombinator::intersect($result, new AccessoryArrayListType());
                            break 2;
                        }
                    }
                }
            }
        }
        return $result;
    }
    public function setExistingOffsetValueType(\PHPStan\Type\Type $offsetType, \PHPStan\Type\Type $valueType): \PHPStan\Type\Type
    {
        return $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->setExistingOffsetValueType($offsetType, $valueType));
    }
    public function unsetOffset(\PHPStan\Type\Type $offsetType): \PHPStan\Type\Type
    {
        return $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->unsetOffset($offsetType));
    }
    public function getKeysArray(): \PHPStan\Type\Type
    {
        return $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->getKeysArray());
    }
    public function getValuesArray(): \PHPStan\Type\Type
    {
        return $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->getValuesArray());
    }
    public function chunkArray(\PHPStan\Type\Type $lengthType, TrinaryLogic $preserveKeys): \PHPStan\Type\Type
    {
        return $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->chunkArray($lengthType, $preserveKeys));
    }
    public function fillKeysArray(\PHPStan\Type\Type $valueType): \PHPStan\Type\Type
    {
        return $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->fillKeysArray($valueType));
    }
    public function flipArray(): \PHPStan\Type\Type
    {
        return $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->flipArray());
    }
    public function intersectKeyArray(\PHPStan\Type\Type $otherArraysType): \PHPStan\Type\Type
    {
        return $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->intersectKeyArray($otherArraysType));
    }
    public function popArray(): \PHPStan\Type\Type
    {
        return $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->popArray());
    }
    public function reverseArray(TrinaryLogic $preserveKeys): \PHPStan\Type\Type
    {
        return $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->reverseArray($preserveKeys));
    }
    public function searchArray(\PHPStan\Type\Type $needleType): \PHPStan\Type\Type
    {
        return $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->searchArray($needleType));
    }
    public function shiftArray(): \PHPStan\Type\Type
    {
        return $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->shiftArray());
    }
    public function shuffleArray(): \PHPStan\Type\Type
    {
        return $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->shuffleArray());
    }
    public function sliceArray(\PHPStan\Type\Type $offsetType, \PHPStan\Type\Type $lengthType, TrinaryLogic $preserveKeys): \PHPStan\Type\Type
    {
        $result = $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->sliceArray($offsetType, $lengthType, $preserveKeys));
        if ($this->isList()->yes() && $this->isIterableAtLeastOnce()->yes() && (new ConstantIntegerType(0))->isSuperTypeOf($offsetType)->yes() && \PHPStan\Type\IntegerRangeType::fromInterval(1, null)->isSuperTypeOf($lengthType)->yes()) {
            $result = \PHPStan\Type\TypeCombinator::intersect($result, new NonEmptyArrayType());
        }
        return $result;
    }
    public function getEnumCases(): array
    {
        $compare = [];
        foreach ($this->types as $type) {
            $oneType = [];
            foreach ($type->getEnumCases() as $enumCase) {
                $oneType[$enumCase->getClassName() . '::' . $enumCase->getEnumCaseName()] = $enumCase;
            }
            $compare[] = $oneType;
        }
        return array_values(array_intersect_key(...$compare));
    }
    public function isCallable(): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isCallable());
    }
    public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array
    {
        if ($this->isCallable()->no()) {
            throw new ShouldNotHappenException();
        }
        return [new TrivialParametersAcceptor()];
    }
    public function isCloneable(): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isCloneable());
    }
    public function isSmallerThan(\PHPStan\Type\Type $otherType, PhpVersion $phpVersion): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isSmallerThan($otherType, $phpVersion));
    }
    public function isSmallerThanOrEqual(\PHPStan\Type\Type $otherType, PhpVersion $phpVersion): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isSmallerThanOrEqual($otherType, $phpVersion));
    }
    public function isNull(): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isNull());
    }
    public function isConstantValue(): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isConstantValue());
    }
    public function isConstantScalarValue(): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isConstantScalarValue());
    }
    public function getConstantScalarTypes(): array
    {
        $scalarTypes = [];
        foreach ($this->types as $type) {
            foreach ($type->getConstantScalarTypes() as $scalarType) {
                $scalarTypes[] = $scalarType;
            }
        }
        return $scalarTypes;
    }
    public function getConstantScalarValues(): array
    {
        $values = [];
        foreach ($this->types as $type) {
            foreach ($type->getConstantScalarValues() as $value) {
                $values[] = $value;
            }
        }
        return $values;
    }
    public function isTrue(): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isTrue());
    }
    public function isFalse(): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isFalse());
    }
    public function isBoolean(): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isBoolean());
    }
    public function isFloat(): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isFloat());
    }
    public function isInteger(): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isInteger());
    }
    public function isGreaterThan(\PHPStan\Type\Type $otherType, PhpVersion $phpVersion): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $otherType->isSmallerThan($type, $phpVersion));
    }
    public function isGreaterThanOrEqual(\PHPStan\Type\Type $otherType, PhpVersion $phpVersion): TrinaryLogic
    {
        return $this->intersectResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $otherType->isSmallerThanOrEqual($type, $phpVersion));
    }
    public function getSmallerType(PhpVersion $phpVersion): \PHPStan\Type\Type
    {
        return $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->getSmallerType($phpVersion));
    }
    public function getSmallerOrEqualType(PhpVersion $phpVersion): \PHPStan\Type\Type
    {
        return $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->getSmallerOrEqualType($phpVersion));
    }
    public function getGreaterType(PhpVersion $phpVersion): \PHPStan\Type\Type
    {
        return $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->getGreaterType($phpVersion));
    }
    public function getGreaterOrEqualType(PhpVersion $phpVersion): \PHPStan\Type\Type
    {
        return $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->getGreaterOrEqualType($phpVersion));
    }
    public function toBoolean(): \PHPStan\Type\BooleanType
    {
        $type = $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\BooleanType => $type->toBoolean());
        if (!$type instanceof \PHPStan\Type\BooleanType) {
            return new \PHPStan\Type\BooleanType();
        }
        return $type;
    }
    public function toNumber(): \PHPStan\Type\Type
    {
        $type = $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->toNumber());
        return $type;
    }
    public function toAbsoluteNumber(): \PHPStan\Type\Type
    {
        $type = $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->toAbsoluteNumber());
        return $type;
    }
    public function toString(): \PHPStan\Type\Type
    {
        $type = $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->toString());
        return $type;
    }
    public function toInteger(): \PHPStan\Type\Type
    {
        $type = $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->toInteger());
        return $type;
    }
    public function toFloat(): \PHPStan\Type\Type
    {
        $type = $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->toFloat());
        return $type;
    }
    public function toArray(): \PHPStan\Type\Type
    {
        $type = $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->toArray());
        return $type;
    }
    public function toArrayKey(): \PHPStan\Type\Type
    {
        if ($this->isNumericString()->yes()) {
            return \PHPStan\Type\TypeCombinator::union(new \PHPStan\Type\IntegerType(), $this);
        }
        if ($this->isString()->yes()) {
            return $this;
        }
        return $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->toArrayKey());
    }
    public function toCoercedArgumentType(bool $strictTypes): \PHPStan\Type\Type
    {
        return $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->toCoercedArgumentType($strictTypes));
    }
    public function inferTemplateTypes(\PHPStan\Type\Type $receivedType): TemplateTypeMap
    {
        $types = TemplateTypeMap::createEmpty();
        foreach ($this->types as $type) {
            $types = $types->intersect($type->inferTemplateTypes($receivedType));
        }
        return $types;
    }
    public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
    {
        $references = [];
        foreach ($this->types as $type) {
            foreach ($type->getReferencedTemplateTypes($positionVariance) as $reference) {
                $references[] = $reference;
            }
        }
        return $references;
    }
    public function traverse(callable $cb): \PHPStan\Type\Type
    {
        $types = [];
        $changed = \false;
        foreach ($this->types as $type) {
            $newType = $cb($type);
            if ($type !== $newType) {
                $changed = \true;
            }
            $types[] = $newType;
        }
        if ($changed) {
            return \PHPStan\Type\TypeCombinator::intersect(...$types);
        }
        return $this;
    }
    public function traverseSimultaneously(\PHPStan\Type\Type $right, callable $cb): \PHPStan\Type\Type
    {
        $types = [];
        $changed = \false;
        if (!$right instanceof self) {
            return $this;
        }
        if (count($this->getTypes()) !== count($right->getTypes())) {
            return $this;
        }
        foreach ($this->getSortedTypes() as $i => $leftType) {
            $rightType = $right->getSortedTypes()[$i];
            $newType = $cb($leftType, $rightType);
            if ($leftType !== $newType) {
                $changed = \true;
            }
            $types[] = $newType;
        }
        if ($changed) {
            return \PHPStan\Type\TypeCombinator::intersect(...$types);
        }
        return $this;
    }
    public function tryRemove(\PHPStan\Type\Type $typeToRemove): ?\PHPStan\Type\Type
    {
        return $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => \PHPStan\Type\TypeCombinator::remove($type, $typeToRemove));
    }
    public function exponentiate(\PHPStan\Type\Type $exponent): \PHPStan\Type\Type
    {
        return $this->intersectTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->exponentiate($exponent));
    }
    public function getFiniteTypes(): array
    {
        $compare = [];
        foreach ($this->types as $type) {
            $oneType = [];
            foreach ($type->getFiniteTypes() as $finiteType) {
                $oneType[md5($finiteType->describe(\PHPStan\Type\VerbosityLevel::typeOnly()))] = $finiteType;
            }
            $compare[] = $oneType;
        }
        $result = array_values(array_intersect_key(...$compare));
        if (count($result) > InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT) {
            return [];
        }
        return $result;
    }
    /**
     * @param callable(Type $type): TrinaryLogic $getResult
     */
    private function intersectResults(callable $getResult): TrinaryLogic
    {
        return TrinaryLogic::lazyMaxMin($this->types, $getResult);
    }
    /**
     * @param callable(Type $type): Type $getType
     */
    private function intersectTypes(callable $getType): \PHPStan\Type\Type
    {
        $operands = array_map($getType, $this->types);
        return \PHPStan\Type\TypeCombinator::intersect(...$operands);
    }
    public function toPhpDocNode(): TypeNode
    {
        $baseTypes = [];
        $typesToDescribe = [];
        $skipTypeNames = [];
        $nonEmptyStr = \false;
        $nonFalsyStr = \false;
        $isList = $this->isList()->yes();
        $isArray = $this->isArray()->yes();
        $isNonEmptyArray = $this->isIterableAtLeastOnce()->yes();
        $describedTypes = [];
        foreach ($this->getSortedTypes() as $i => $type) {
            if ($type instanceof AccessoryNonEmptyStringType || $type instanceof AccessoryLiteralStringType || $type instanceof AccessoryNumericStringType || $type instanceof AccessoryNonFalsyStringType || $type instanceof AccessoryLowercaseStringType || $type instanceof AccessoryUppercaseStringType) {
                if ($type instanceof AccessoryNonFalsyStringType) {
                    $nonFalsyStr = \true;
                }
                if ($type instanceof AccessoryNonEmptyStringType) {
                    $nonEmptyStr = \true;
                }
                if ($nonEmptyStr && $nonFalsyStr) {
                    // prevent redundant 'non-empty-string&non-falsy-string'
                    foreach ($typesToDescribe as $key => $typeToDescribe) {
                        if (!$typeToDescribe instanceof AccessoryNonEmptyStringType) {
                            continue;
                        }
                        unset($typesToDescribe[$key]);
                    }
                }
                $typesToDescribe[$i] = $type;
                $skipTypeNames[] = 'string';
                continue;
            }
            if ($isList || $isArray) {
                if ($type instanceof \PHPStan\Type\ArrayType) {
                    $keyType = $type->getKeyType();
                    $valueType = $type->getItemType();
                    if ($isList) {
                        $isMixedValueType = $valueType instanceof \PHPStan\Type\MixedType && $valueType->describe(\PHPStan\Type\VerbosityLevel::precise()) === 'mixed' && !$valueType->isExplicitMixed();
                        $identifierTypeNode = new IdentifierTypeNode($isNonEmptyArray ? 'non-empty-list' : 'list');
                        if (!$isMixedValueType) {
                            $describedTypes[$i] = new GenericTypeNode($identifierTypeNode, [$valueType->toPhpDocNode()]);
                        } else {
                            $describedTypes[$i] = $identifierTypeNode;
                        }
                    } else {
                        $isMixedKeyType = $keyType instanceof \PHPStan\Type\MixedType && $keyType->describe(\PHPStan\Type\VerbosityLevel::precise()) === 'mixed' && !$keyType->isExplicitMixed();
                        $isMixedValueType = $valueType instanceof \PHPStan\Type\MixedType && $valueType->describe(\PHPStan\Type\VerbosityLevel::precise()) === 'mixed' && !$valueType->isExplicitMixed();
                        $identifierTypeNode = new IdentifierTypeNode($isNonEmptyArray ? 'non-empty-array' : 'array');
                        if (!$isMixedKeyType) {
                            $describedTypes[$i] = new GenericTypeNode($identifierTypeNode, [$keyType->toPhpDocNode(), $valueType->toPhpDocNode()]);
                        } elseif (!$isMixedValueType) {
                            $describedTypes[$i] = new GenericTypeNode($identifierTypeNode, [$valueType->toPhpDocNode()]);
                        } else {
                            $describedTypes[$i] = $identifierTypeNode;
                        }
                    }
                    continue;
                } elseif ($type instanceof ConstantArrayType) {
                    $constantArrayTypeNode = $type->toPhpDocNode();
                    if ($constantArrayTypeNode instanceof ArrayShapeNode) {
                        $newKind = $constantArrayTypeNode->kind;
                        if ($isList) {
                            if ($isNonEmptyArray && !$type->isIterableAtLeastOnce()->yes()) {
                                $newKind = ArrayShapeNode::KIND_NON_EMPTY_LIST;
                            } else {
                                $newKind = ArrayShapeNode::KIND_LIST;
                            }
                        } elseif ($isNonEmptyArray && !$type->isIterableAtLeastOnce()->yes()) {
                            $newKind = ArrayShapeNode::KIND_NON_EMPTY_ARRAY;
                        }
                        if ($newKind !== $constantArrayTypeNode->kind) {
                            if ($constantArrayTypeNode->sealed) {
                                $constantArrayTypeNode = ArrayShapeNode::createSealed($constantArrayTypeNode->items, $newKind);
                            } else {
                                $constantArrayTypeNode = ArrayShapeNode::createUnsealed($constantArrayTypeNode->items, $constantArrayTypeNode->unsealedType, $newKind);
                            }
                        }
                        $describedTypes[$i] = $constantArrayTypeNode;
                        continue;
                    }
                }
                if ($type instanceof NonEmptyArrayType || $type instanceof AccessoryArrayListType) {
                    continue;
                }
            }
            if (!$type instanceof AccessoryType) {
                $baseTypes[$i] = $type;
                continue;
            }
            $accessoryPhpDocNode = $type->toPhpDocNode();
            if ($accessoryPhpDocNode instanceof IdentifierTypeNode && $accessoryPhpDocNode->name === '') {
                continue;
            }
            $typesToDescribe[$i] = $type;
        }
        foreach ($baseTypes as $i => $type) {
            $typeNode = $type->toPhpDocNode();
            if ($typeNode instanceof GenericTypeNode && $typeNode->type->name === 'array') {
                $nonEmpty = \false;
                $typeName = 'array';
                foreach ($typesToDescribe as $j => $typeToDescribe) {
                    if ($typeToDescribe instanceof AccessoryArrayListType) {
                        $typeName = 'list';
                        if (count($typeNode->genericTypes) > 1) {
                            array_shift($typeNode->genericTypes);
                        }
                    } elseif ($typeToDescribe instanceof NonEmptyArrayType) {
                        $nonEmpty = \true;
                    } else {
                        continue;
                    }
                    unset($typesToDescribe[$j]);
                }
                if ($nonEmpty) {
                    $typeName = 'non-empty-' . $typeName;
                }
                $describedTypes[$i] = new GenericTypeNode(new IdentifierTypeNode($typeName), $typeNode->genericTypes);
                continue;
            }
            if ($typeNode instanceof IdentifierTypeNode && in_array($typeNode->name, $skipTypeNames, \true)) {
                continue;
            }
            $describedTypes[$i] = $typeNode;
        }
        foreach ($typesToDescribe as $i => $typeToDescribe) {
            $describedTypes[$i] = $typeToDescribe->toPhpDocNode();
        }
        ksort($describedTypes);
        $describedTypes = array_values($describedTypes);
        if (count($describedTypes) === 1) {
            return $describedTypes[0];
        }
        return new IntersectionTypeNode($describedTypes);
    }
}
