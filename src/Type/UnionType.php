<?php

declare (strict_types=1);
namespace PHPStan\Type;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Error;
use Exception;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\Type\UnionTypeUnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnionTypeUnresolvedPropertyPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\Generic\TemplateMixedType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TemplateUnionType;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use Throwable;
use function array_diff_assoc;
use function array_fill_keys;
use function array_map;
use function array_merge;
use function array_slice;
use function array_unique;
use function array_values;
use function count;
use function implode;
use function md5;
use function sprintf;
use function str_contains;
/** @api */
class UnionType implements \PHPStan\Type\CompoundType
{
    /**
     * @var Type[]
     */
    private array $types;
    private bool $normalized;
    use NonGeneralizableTypeTrait;
    public const EQUAL_UNION_CLASSES = [DateTimeInterface::class => [DateTimeImmutable::class, DateTime::class], Throwable::class => [Error::class, Exception::class]];
    private bool $sortedTypes = \false;
    /** @var array<int, string> */
    private array $cachedDescriptions = [];
    /**
     * @api
     * @param Type[] $types
     */
    public function __construct(array $types, bool $normalized = \false)
    {
        $this->types = $types;
        $this->normalized = $normalized;
        $throwException = static function () use($types) : void {
            throw new ShouldNotHappenException(sprintf('Cannot create %s with: %s', self::class, implode(', ', array_map(static fn(\PHPStan\Type\Type $type): string => $type->describe(\PHPStan\Type\VerbosityLevel::value()), $types))));
        };
        if (count($types) < 2) {
            $throwException();
        }
        foreach ($types as $type) {
            if (!$type instanceof \PHPStan\Type\UnionType) {
                continue;
            }
            if ($type instanceof TemplateType) {
                continue;
            }
            $throwException();
        }
    }
    /**
     * @return Type[]
     */
    public function getTypes() : array
    {
        return $this->types;
    }
    /**
     * @param callable(Type $type): bool $filterCb
     */
    public function filterTypes(callable $filterCb) : \PHPStan\Type\Type
    {
        $newTypes = [];
        $changed = \false;
        foreach ($this->getTypes() as $innerType) {
            if (!$filterCb($innerType)) {
                $changed = \true;
                continue;
            }
            $newTypes[] = $innerType;
        }
        if (!$changed) {
            return $this;
        }
        return \PHPStan\Type\TypeCombinator::union(...$newTypes);
    }
    public function isNormalized() : bool
    {
        return $this->normalized;
    }
    /**
     * @return Type[]
     */
    protected function getSortedTypes() : array
    {
        if ($this->sortedTypes) {
            return $this->types;
        }
        $this->types = \PHPStan\Type\UnionTypeHelper::sortTypes($this->types);
        $this->sortedTypes = \true;
        return $this->types;
    }
    public function getReferencedClasses() : array
    {
        $classes = [];
        foreach ($this->types as $type) {
            foreach ($type->getReferencedClasses() as $className) {
                $classes[] = $className;
            }
        }
        return $classes;
    }
    public function getObjectClassNames() : array
    {
        return array_values(array_unique($this->pickFromTypes(static fn(\PHPStan\Type\Type $type) => $type->getObjectClassNames(), static fn(\PHPStan\Type\Type $type) => $type->isObject()->yes())));
    }
    public function getObjectClassReflections() : array
    {
        return $this->pickFromTypes(static fn(\PHPStan\Type\Type $type) => $type->getObjectClassReflections(), static fn(\PHPStan\Type\Type $type) => $type->isObject()->yes());
    }
    public function getArrays() : array
    {
        return $this->pickFromTypes(static fn(\PHPStan\Type\Type $type) => $type->getArrays(), static fn(\PHPStan\Type\Type $type) => $type->isArray()->yes());
    }
    public function getConstantArrays() : array
    {
        return $this->pickFromTypes(static fn(\PHPStan\Type\Type $type) => $type->getConstantArrays(), static fn(\PHPStan\Type\Type $type) => $type->isArray()->yes());
    }
    public function getConstantStrings() : array
    {
        return $this->pickFromTypes(static fn(\PHPStan\Type\Type $type) => $type->getConstantStrings(), static fn(\PHPStan\Type\Type $type) => $type->isString()->yes());
    }
    public function accepts(\PHPStan\Type\Type $type, bool $strictTypes) : \PHPStan\Type\AcceptsResult
    {
        foreach (self::EQUAL_UNION_CLASSES as $baseClass => $classes) {
            if (!$type->equals(new \PHPStan\Type\ObjectType($baseClass))) {
                continue;
            }
            $union = \PHPStan\Type\TypeCombinator::union(...array_map(static fn(string $objectClass): \PHPStan\Type\Type => new \PHPStan\Type\ObjectType($objectClass), $classes));
            if ($this->accepts($union, $strictTypes)->yes()) {
                return \PHPStan\Type\AcceptsResult::createYes();
            }
            break;
        }
        $result = \PHPStan\Type\AcceptsResult::createNo();
        foreach ($this->getSortedTypes() as $i => $innerType) {
            $result = $result->or($innerType->accepts($type, $strictTypes)->decorateReasons(static fn(string $reason) => sprintf('Type #%d from the union: %s', $i + 1, $reason)));
        }
        if ($result->yes()) {
            return $result;
        }
        if ($type instanceof \PHPStan\Type\CompoundType && !$type instanceof \PHPStan\Type\CallableType && !$type instanceof TemplateType && !$type instanceof \PHPStan\Type\IntersectionType) {
            return $type->isAcceptedBy($this, $strictTypes);
        }
        if ($type instanceof TemplateUnionType) {
            return $result->or($type->isAcceptedBy($this, $strictTypes));
        }
        if ($type->isEnum()->yes() && !$this->isEnum()->no()) {
            $enumCasesUnion = \PHPStan\Type\TypeCombinator::union(...$type->getEnumCases());
            if (!$type->equals($enumCasesUnion)) {
                return $this->accepts($enumCasesUnion, $strictTypes);
            }
        }
        return $result;
    }
    public function isSuperTypeOf(\PHPStan\Type\Type $otherType) : \PHPStan\Type\IsSuperTypeOfResult
    {
        if ($otherType instanceof self && !$otherType instanceof TemplateUnionType || $otherType instanceof \PHPStan\Type\IterableType || $otherType instanceof \PHPStan\Type\NeverType || $otherType instanceof \PHPStan\Type\ConditionalType || $otherType instanceof \PHPStan\Type\ConditionalTypeForParameter || $otherType instanceof \PHPStan\Type\IntegerRangeType) {
            return $otherType->isSubTypeOf($this);
        }
        $results = [];
        foreach ($this->types as $innerType) {
            $result = $innerType->isSuperTypeOf($otherType);
            if ($result->yes()) {
                return $result;
            }
            $results[] = $result;
        }
        $result = \PHPStan\Type\IsSuperTypeOfResult::createNo()->or(...$results);
        if ($otherType instanceof TemplateUnionType) {
            return $result->or($otherType->isSubTypeOf($this));
        }
        return $result;
    }
    public function isSubTypeOf(\PHPStan\Type\Type $otherType) : \PHPStan\Type\IsSuperTypeOfResult
    {
        return \PHPStan\Type\IsSuperTypeOfResult::extremeIdentity(...array_map(static fn(\PHPStan\Type\Type $innerType) => $otherType->isSuperTypeOf($innerType), $this->types));
    }
    public function isAcceptedBy(\PHPStan\Type\Type $acceptingType, bool $strictTypes) : \PHPStan\Type\AcceptsResult
    {
        return \PHPStan\Type\AcceptsResult::extremeIdentity(...array_map(static fn(\PHPStan\Type\Type $innerType) => $acceptingType->accepts($innerType, $strictTypes), $this->types));
    }
    public function equals(\PHPStan\Type\Type $type) : bool
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
    public function describe(\PHPStan\Type\VerbosityLevel $level) : string
    {
        if (isset($this->cachedDescriptions[$level->getLevelValue()])) {
            return $this->cachedDescriptions[$level->getLevelValue()];
        }
        $joinTypes = static function (array $types) use($level) : string {
            $typeNames = [];
            foreach ($types as $i => $type) {
                if ($type instanceof \PHPStan\Type\ClosureType || $type instanceof \PHPStan\Type\CallableType || $type instanceof TemplateUnionType) {
                    $typeNames[] = sprintf('(%s)', $type->describe($level));
                } elseif ($type instanceof TemplateType) {
                    $isLast = $i >= count($types) - 1;
                    $bound = $type->getBound();
                    if (!$isLast && ($level->isTypeOnly() || $level->isValue()) && !($bound instanceof \PHPStan\Type\MixedType && $bound->getSubtractedType() === null && !$bound instanceof TemplateMixedType)) {
                        $typeNames[] = sprintf('(%s)', $type->describe($level));
                    } else {
                        $typeNames[] = $type->describe($level);
                    }
                } elseif ($type instanceof \PHPStan\Type\IntersectionType) {
                    $intersectionDescription = $type->describe($level);
                    if (str_contains($intersectionDescription, '&')) {
                        $typeNames[] = sprintf('(%s)', $type->describe($level));
                    } else {
                        $typeNames[] = $intersectionDescription;
                    }
                } else {
                    $typeNames[] = $type->describe($level);
                }
            }
            if ($level->isPrecise()) {
                $duplicates = array_diff_assoc($typeNames, array_unique($typeNames));
                if (count($duplicates) > 0) {
                    $indexByDuplicate = array_fill_keys($duplicates, 0);
                    foreach ($typeNames as $key => $typeName) {
                        if (!isset($indexByDuplicate[$typeName])) {
                            continue;
                        }
                        $typeNames[$key] = $typeName . '#' . ++$indexByDuplicate[$typeName];
                    }
                }
            } else {
                $typeNames = array_unique($typeNames);
            }
            if (count($typeNames) > 1024) {
                return implode('|', array_slice($typeNames, 0, 1024)) . "|…";
            }
            return implode('|', $typeNames);
        };
        return $this->cachedDescriptions[$level->getLevelValue()] = $level->handle(function () use($joinTypes) : string {
            $types = \PHPStan\Type\TypeCombinator::union(...array_map(static function (\PHPStan\Type\Type $type) : \PHPStan\Type\Type {
                if ($type->isConstantValue()->yes() && $type->isTrue()->or($type->isFalse())->no()) {
                    return $type->generalize(\PHPStan\Type\GeneralizePrecision::lessSpecific());
                }
                return $type;
            }, $this->getSortedTypes()));
            if ($types instanceof \PHPStan\Type\UnionType) {
                return $joinTypes($types->getSortedTypes());
            }
            return $joinTypes([$types]);
        }, fn(): string => $joinTypes($this->getSortedTypes()));
    }
    /**
     * @param callable(Type $type): TrinaryLogic $canCallback
     * @param callable(Type $type): TrinaryLogic $hasCallback
     */
    private function hasInternal(callable $canCallback, callable $hasCallback) : TrinaryLogic
    {
        return TrinaryLogic::lazyExtremeIdentity($this->types, static function (\PHPStan\Type\Type $type) use($canCallback, $hasCallback) : TrinaryLogic {
            if ($canCallback($type)->no()) {
                return TrinaryLogic::createNo();
            }
            return $hasCallback($type);
        });
    }
    /**
     * @template TObject of object
     * @param callable(Type $type): TrinaryLogic $hasCallback
     * @param callable(Type $type): TObject $getCallback
     * @return TObject
     */
    private function getInternal(callable $hasCallback, callable $getCallback) : object
    {
        /** @var TrinaryLogic|null $result */
        $result = null;
        /** @var TObject|null $object */
        $object = null;
        foreach ($this->types as $type) {
            $has = $hasCallback($type);
            if (!$has->yes()) {
                continue;
            }
            if ($result !== null && $result->compareTo($has) !== $has) {
                continue;
            }
            $get = $getCallback($type);
            $result = $has;
            $object = $get;
        }
        if ($object === null) {
            throw new ShouldNotHappenException();
        }
        return $object;
    }
    public function getTemplateType(string $ancestorClassName, string $templateTypeName) : \PHPStan\Type\Type
    {
        return $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->getTemplateType($ancestorClassName, $templateTypeName));
    }
    public function isObject() : TrinaryLogic
    {
        return $this->unionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isObject());
    }
    public function isEnum() : TrinaryLogic
    {
        return $this->unionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isEnum());
    }
    public function canAccessProperties() : TrinaryLogic
    {
        return $this->unionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->canAccessProperties());
    }
    public function hasProperty(string $propertyName) : TrinaryLogic
    {
        return $this->unionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->hasProperty($propertyName));
    }
    public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope) : ExtendedPropertyReflection
    {
        return $this->getUnresolvedPropertyPrototype($propertyName, $scope)->getTransformedProperty();
    }
    public function getUnresolvedPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope) : UnresolvedPropertyPrototypeReflection
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
        return new UnionTypeUnresolvedPropertyPrototypeReflection($propertyName, $propertyPrototypes);
    }
    public function canCallMethods() : TrinaryLogic
    {
        return $this->unionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->canCallMethods());
    }
    public function hasMethod(string $methodName) : TrinaryLogic
    {
        return $this->unionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->hasMethod($methodName));
    }
    public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope) : ExtendedMethodReflection
    {
        return $this->getUnresolvedMethodPrototype($methodName, $scope)->getTransformedMethod();
    }
    public function getUnresolvedMethodPrototype(string $methodName, ClassMemberAccessAnswerer $scope) : UnresolvedMethodPrototypeReflection
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
        return new UnionTypeUnresolvedMethodPrototypeReflection($methodName, $methodPrototypes);
    }
    public function canAccessConstants() : TrinaryLogic
    {
        return $this->unionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->canAccessConstants());
    }
    public function hasConstant(string $constantName) : TrinaryLogic
    {
        return $this->hasInternal(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->canAccessConstants(), static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->hasConstant($constantName));
    }
    public function getConstant(string $constantName) : ClassConstantReflection
    {
        return $this->getInternal(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->hasConstant($constantName), static fn(\PHPStan\Type\Type $type): ClassConstantReflection => $type->getConstant($constantName));
    }
    public function isIterable() : TrinaryLogic
    {
        return $this->unionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isIterable());
    }
    public function isIterableAtLeastOnce() : TrinaryLogic
    {
        return $this->unionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isIterableAtLeastOnce());
    }
    public function getArraySize() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->getArraySize());
    }
    public function getIterableKeyType() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->getIterableKeyType());
    }
    public function getFirstIterableKeyType() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->getFirstIterableKeyType());
    }
    public function getLastIterableKeyType() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->getLastIterableKeyType());
    }
    public function getIterableValueType() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->getIterableValueType());
    }
    public function getFirstIterableValueType() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->getFirstIterableValueType());
    }
    public function getLastIterableValueType() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->getLastIterableValueType());
    }
    public function isArray() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isArray());
    }
    public function isConstantArray() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isConstantArray());
    }
    public function isOversizedArray() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isOversizedArray());
    }
    public function isList() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isList());
    }
    public function isString() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isString());
    }
    public function isNumericString() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isNumericString());
    }
    public function isNonEmptyString() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isNonEmptyString());
    }
    public function isNonFalsyString() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isNonFalsyString());
    }
    public function isLiteralString() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isLiteralString());
    }
    public function isLowercaseString() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isLowercaseString());
    }
    public function isUppercaseString() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isUppercaseString());
    }
    public function isClassString() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isClassString());
    }
    public function getClassStringObjectType() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->getClassStringObjectType());
    }
    public function getObjectTypeOrClassStringObjectType() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->getObjectTypeOrClassStringObjectType());
    }
    public function isVoid() : TrinaryLogic
    {
        return $this->unionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isVoid());
    }
    public function isScalar() : TrinaryLogic
    {
        return $this->unionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isScalar());
    }
    public function looseCompare(\PHPStan\Type\Type $type, PhpVersion $phpVersion) : \PHPStan\Type\BooleanType
    {
        return $this->notBenevolentUnionResults(static fn(\PHPStan\Type\Type $innerType): TrinaryLogic => $innerType->looseCompare($type, $phpVersion)->toTrinaryLogic())->toBooleanType();
    }
    public function isOffsetAccessible() : TrinaryLogic
    {
        return $this->unionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isOffsetAccessible());
    }
    public function isOffsetAccessLegal() : TrinaryLogic
    {
        return $this->unionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isOffsetAccessLegal());
    }
    public function hasOffsetValueType(\PHPStan\Type\Type $offsetType) : TrinaryLogic
    {
        return $this->unionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->hasOffsetValueType($offsetType));
    }
    public function getOffsetValueType(\PHPStan\Type\Type $offsetType) : \PHPStan\Type\Type
    {
        $types = [];
        foreach ($this->types as $innerType) {
            $valueType = $innerType->getOffsetValueType($offsetType);
            if ($valueType instanceof \PHPStan\Type\ErrorType) {
                continue;
            }
            $types[] = $valueType;
        }
        if (count($types) === 0) {
            return new \PHPStan\Type\ErrorType();
        }
        return \PHPStan\Type\TypeCombinator::union(...$types);
    }
    public function setOffsetValueType(?\PHPStan\Type\Type $offsetType, \PHPStan\Type\Type $valueType, bool $unionValues = \true) : \PHPStan\Type\Type
    {
        return $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->setOffsetValueType($offsetType, $valueType, $unionValues));
    }
    public function setExistingOffsetValueType(\PHPStan\Type\Type $offsetType, \PHPStan\Type\Type $valueType) : \PHPStan\Type\Type
    {
        return $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->setExistingOffsetValueType($offsetType, $valueType));
    }
    public function unsetOffset(\PHPStan\Type\Type $offsetType) : \PHPStan\Type\Type
    {
        return $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->unsetOffset($offsetType));
    }
    public function getKeysArray() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->getKeysArray());
    }
    public function getValuesArray() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->getValuesArray());
    }
    public function chunkArray(\PHPStan\Type\Type $lengthType, TrinaryLogic $preserveKeys) : \PHPStan\Type\Type
    {
        return $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->chunkArray($lengthType, $preserveKeys));
    }
    public function fillKeysArray(\PHPStan\Type\Type $valueType) : \PHPStan\Type\Type
    {
        return $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->fillKeysArray($valueType));
    }
    public function flipArray() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->flipArray());
    }
    public function intersectKeyArray(\PHPStan\Type\Type $otherArraysType) : \PHPStan\Type\Type
    {
        return $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->intersectKeyArray($otherArraysType));
    }
    public function popArray() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->popArray());
    }
    public function reverseArray(TrinaryLogic $preserveKeys) : \PHPStan\Type\Type
    {
        return $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->reverseArray($preserveKeys));
    }
    public function searchArray(\PHPStan\Type\Type $needleType) : \PHPStan\Type\Type
    {
        return $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->searchArray($needleType));
    }
    public function shiftArray() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->shiftArray());
    }
    public function shuffleArray() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->shuffleArray());
    }
    public function sliceArray(\PHPStan\Type\Type $offsetType, \PHPStan\Type\Type $lengthType, TrinaryLogic $preserveKeys) : \PHPStan\Type\Type
    {
        return $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->sliceArray($offsetType, $lengthType, $preserveKeys));
    }
    public function getEnumCases() : array
    {
        return $this->pickFromTypes(static fn(\PHPStan\Type\Type $type) => $type->getEnumCases(), static fn(\PHPStan\Type\Type $type) => $type->isObject()->yes());
    }
    public function isCallable() : TrinaryLogic
    {
        return $this->unionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isCallable());
    }
    public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope) : array
    {
        $acceptors = [];
        foreach ($this->types as $type) {
            if ($type->isCallable()->no()) {
                continue;
            }
            $acceptors = array_merge($acceptors, $type->getCallableParametersAcceptors($scope));
        }
        if (count($acceptors) === 0) {
            throw new ShouldNotHappenException();
        }
        return $acceptors;
    }
    public function isCloneable() : TrinaryLogic
    {
        return $this->unionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isCloneable());
    }
    public function isSmallerThan(\PHPStan\Type\Type $otherType, PhpVersion $phpVersion) : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isSmallerThan($otherType, $phpVersion));
    }
    public function isSmallerThanOrEqual(\PHPStan\Type\Type $otherType, PhpVersion $phpVersion) : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isSmallerThanOrEqual($otherType, $phpVersion));
    }
    public function isNull() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isNull());
    }
    public function isConstantValue() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isConstantValue());
    }
    public function isConstantScalarValue() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isConstantScalarValue());
    }
    public function getConstantScalarTypes() : array
    {
        return $this->notBenevolentPickFromTypes(static fn(\PHPStan\Type\Type $type) => $type->getConstantScalarTypes());
    }
    public function getConstantScalarValues() : array
    {
        return $this->notBenevolentPickFromTypes(static fn(\PHPStan\Type\Type $type) => $type->getConstantScalarValues());
    }
    public function isTrue() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isTrue());
    }
    public function isFalse() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isFalse());
    }
    public function isBoolean() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isBoolean());
    }
    public function isFloat() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isFloat());
    }
    public function isInteger() : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $type->isInteger());
    }
    public function getSmallerType(PhpVersion $phpVersion) : \PHPStan\Type\Type
    {
        return $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->getSmallerType($phpVersion));
    }
    public function getSmallerOrEqualType(PhpVersion $phpVersion) : \PHPStan\Type\Type
    {
        return $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->getSmallerOrEqualType($phpVersion));
    }
    public function getGreaterType(PhpVersion $phpVersion) : \PHPStan\Type\Type
    {
        return $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->getGreaterType($phpVersion));
    }
    public function getGreaterOrEqualType(PhpVersion $phpVersion) : \PHPStan\Type\Type
    {
        return $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->getGreaterOrEqualType($phpVersion));
    }
    public function isGreaterThan(\PHPStan\Type\Type $otherType, PhpVersion $phpVersion) : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $otherType->isSmallerThan($type, $phpVersion));
    }
    public function isGreaterThanOrEqual(\PHPStan\Type\Type $otherType, PhpVersion $phpVersion) : TrinaryLogic
    {
        return $this->notBenevolentUnionResults(static fn(\PHPStan\Type\Type $type): TrinaryLogic => $otherType->isSmallerThanOrEqual($type, $phpVersion));
    }
    public function toBoolean() : \PHPStan\Type\BooleanType
    {
        /** @var BooleanType $type */
        $type = $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\BooleanType => $type->toBoolean());
        return $type;
    }
    public function toNumber() : \PHPStan\Type\Type
    {
        $type = $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->toNumber());
        return $type;
    }
    public function toAbsoluteNumber() : \PHPStan\Type\Type
    {
        $type = $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->toAbsoluteNumber());
        return $type;
    }
    public function toString() : \PHPStan\Type\Type
    {
        $type = $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->toString());
        return $type;
    }
    public function toInteger() : \PHPStan\Type\Type
    {
        $type = $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->toInteger());
        return $type;
    }
    public function toFloat() : \PHPStan\Type\Type
    {
        $type = $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->toFloat());
        return $type;
    }
    public function toArray() : \PHPStan\Type\Type
    {
        $type = $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->toArray());
        return $type;
    }
    public function toArrayKey() : \PHPStan\Type\Type
    {
        return $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->toArrayKey());
    }
    public function toCoercedArgumentType(bool $strictTypes) : \PHPStan\Type\Type
    {
        return $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->toCoercedArgumentType($strictTypes));
    }
    public function inferTemplateTypes(\PHPStan\Type\Type $receivedType) : TemplateTypeMap
    {
        $types = TemplateTypeMap::createEmpty();
        if ($receivedType instanceof \PHPStan\Type\UnionType) {
            $myTypes = [];
            $remainingReceivedTypes = [];
            foreach ($receivedType->getTypes() as $receivedInnerType) {
                foreach ($this->types as $type) {
                    if ($type->isSuperTypeOf($receivedInnerType)->yes()) {
                        $types = $types->union($type->inferTemplateTypes($receivedInnerType));
                        continue 2;
                    }
                    $myTypes[] = $type;
                }
                $remainingReceivedTypes[] = $receivedInnerType;
            }
            if (count($remainingReceivedTypes) === 0) {
                return $types;
            }
            $receivedType = \PHPStan\Type\TypeCombinator::union(...$remainingReceivedTypes);
        } else {
            $myTypes = $this->types;
        }
        foreach ($myTypes as $type) {
            if ($type instanceof TemplateType || $type instanceof GenericClassStringType && $type->getGenericType() instanceof TemplateType) {
                continue;
            }
            $types = $types->union($type->inferTemplateTypes($receivedType));
        }
        if (!$types->isEmpty()) {
            return $types;
        }
        foreach ($myTypes as $type) {
            $types = $types->union($type->inferTemplateTypes($receivedType));
        }
        return $types;
    }
    public function inferTemplateTypesOn(\PHPStan\Type\Type $templateType) : TemplateTypeMap
    {
        $types = TemplateTypeMap::createEmpty();
        foreach ($this->types as $type) {
            $types = $types->union($templateType->inferTemplateTypes($type));
        }
        return $types;
    }
    public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance) : array
    {
        $references = [];
        foreach ($this->types as $type) {
            foreach ($type->getReferencedTemplateTypes($positionVariance) as $reference) {
                $references[] = $reference;
            }
        }
        return $references;
    }
    public function traverse(callable $cb) : \PHPStan\Type\Type
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
            return \PHPStan\Type\TypeCombinator::union(...$types);
        }
        return $this;
    }
    public function traverseSimultaneously(\PHPStan\Type\Type $right, callable $cb) : \PHPStan\Type\Type
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
            return \PHPStan\Type\TypeCombinator::union(...$types);
        }
        return $this;
    }
    public function tryRemove(\PHPStan\Type\Type $typeToRemove) : ?\PHPStan\Type\Type
    {
        return $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => \PHPStan\Type\TypeCombinator::remove($type, $typeToRemove));
    }
    public function exponentiate(\PHPStan\Type\Type $exponent) : \PHPStan\Type\Type
    {
        return $this->unionTypes(static fn(\PHPStan\Type\Type $type): \PHPStan\Type\Type => $type->exponentiate($exponent));
    }
    public function getFiniteTypes() : array
    {
        $types = $this->notBenevolentPickFromTypes(static fn(\PHPStan\Type\Type $type) => $type->getFiniteTypes());
        $uniquedTypes = [];
        foreach ($types as $type) {
            $uniquedTypes[md5($type->describe(\PHPStan\Type\VerbosityLevel::cache()))] = $type;
        }
        if (count($uniquedTypes) > InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT) {
            return [];
        }
        return array_values($uniquedTypes);
    }
    /**
     * @param callable(Type $type): TrinaryLogic $getResult
     */
    protected function unionResults(callable $getResult) : TrinaryLogic
    {
        return TrinaryLogic::lazyExtremeIdentity($this->types, $getResult);
    }
    /**
     * @param callable(Type $type): TrinaryLogic $getResult
     */
    private function notBenevolentUnionResults(callable $getResult) : TrinaryLogic
    {
        return TrinaryLogic::lazyExtremeIdentity($this->types, $getResult);
    }
    /**
     * @param callable(Type $type): Type $getType
     */
    protected function unionTypes(callable $getType) : \PHPStan\Type\Type
    {
        return \PHPStan\Type\TypeCombinator::union(...array_map($getType, $this->types));
    }
    /**
     * @template T
     * @param callable(Type $type): list<T> $getValues
     * @param callable(Type $type): bool $criteria
     * @return list<T>
     */
    protected function pickFromTypes(callable $getValues, callable $criteria) : array
    {
        $values = [];
        foreach ($this->types as $type) {
            $innerValues = $getValues($type);
            if ($innerValues === []) {
                return [];
            }
            foreach ($innerValues as $innerType) {
                $values[] = $innerType;
            }
        }
        return $values;
    }
    public function toPhpDocNode() : TypeNode
    {
        return new UnionTypeNode(array_map(static fn(\PHPStan\Type\Type $type) => $type->toPhpDocNode(), $this->getSortedTypes()));
    }
    /**
     * @template T
     * @param callable(Type $type): list<T> $getValues
     * @return list<T>
     */
    private function notBenevolentPickFromTypes(callable $getValues) : array
    {
        $values = [];
        foreach ($this->types as $type) {
            $innerValues = $getValues($type);
            if ($innerValues === []) {
                return [];
            }
            foreach ($innerValues as $innerType) {
                $values[] = $innerType;
            }
        }
        return $values;
    }
}
