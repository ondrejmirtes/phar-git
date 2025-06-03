<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\Type\UnresolvedMethodPrototypeReflection;
use PHPStan\Reflection\Type\UnresolvedPropertyPrototypeReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use PHPStan\Type\Traits\NonGenericTypeTrait;
use PHPStan\Type\Traits\NonRemoveableTypeTrait;
use PHPStan\Type\Traits\UndecidedBooleanTypeTrait;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
/** @api */
class NeverType implements \PHPStan\Type\CompoundType
{
    private bool $isExplicit;
    use UndecidedBooleanTypeTrait;
    use NonGenericTypeTrait;
    use UndecidedComparisonCompoundTypeTrait;
    use NonRemoveableTypeTrait;
    use NonGeneralizableTypeTrait;
    /** @api */
    public function __construct(bool $isExplicit = \false)
    {
        $this->isExplicit = $isExplicit;
    }
    public function isExplicit(): bool
    {
        return $this->isExplicit;
    }
    public function getReferencedClasses(): array
    {
        return [];
    }
    public function getArrays(): array
    {
        return [];
    }
    public function getConstantArrays(): array
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
        return \PHPStan\Type\AcceptsResult::createYes();
    }
    public function isSuperTypeOf(\PHPStan\Type\Type $type): \PHPStan\Type\IsSuperTypeOfResult
    {
        if ($type instanceof self) {
            return \PHPStan\Type\IsSuperTypeOfResult::createYes();
        }
        return \PHPStan\Type\IsSuperTypeOfResult::createNo();
    }
    public function equals(\PHPStan\Type\Type $type): bool
    {
        return $type instanceof self;
    }
    public function isSubTypeOf(\PHPStan\Type\Type $otherType): \PHPStan\Type\IsSuperTypeOfResult
    {
        return \PHPStan\Type\IsSuperTypeOfResult::createYes();
    }
    public function isAcceptedBy(\PHPStan\Type\Type $acceptingType, bool $strictTypes): \PHPStan\Type\AcceptsResult
    {
        return $this->isSubTypeOf($acceptingType)->toAcceptsResult();
    }
    public function describe(\PHPStan\Type\VerbosityLevel $level): string
    {
        return '*NEVER*';
    }
    public function getTemplateType(string $ancestorClassName, string $templateTypeName): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\NeverType();
    }
    public function isObject(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isEnum(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function canAccessProperties(): TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function hasProperty(string $propertyName): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function getProperty(string $propertyName, ClassMemberAccessAnswerer $scope): ExtendedPropertyReflection
    {
        throw new ShouldNotHappenException();
    }
    public function getUnresolvedPropertyPrototype(string $propertyName, ClassMemberAccessAnswerer $scope): UnresolvedPropertyPrototypeReflection
    {
        throw new ShouldNotHappenException();
    }
    public function canCallMethods(): TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function hasMethod(string $methodName): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function getMethod(string $methodName, ClassMemberAccessAnswerer $scope): ExtendedMethodReflection
    {
        throw new ShouldNotHappenException();
    }
    public function getUnresolvedMethodPrototype(string $methodName, ClassMemberAccessAnswerer $scope): UnresolvedMethodPrototypeReflection
    {
        throw new ShouldNotHappenException();
    }
    public function canAccessConstants(): TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function hasConstant(string $constantName): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function getConstant(string $constantName): ClassConstantReflection
    {
        throw new ShouldNotHappenException();
    }
    public function isIterable(): TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function isIterableAtLeastOnce(): TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
    public function getArraySize(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\NeverType();
    }
    public function getIterableKeyType(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\NeverType();
    }
    public function getFirstIterableKeyType(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\NeverType();
    }
    public function getLastIterableKeyType(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\NeverType();
    }
    public function getIterableValueType(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\NeverType();
    }
    public function getFirstIterableValueType(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\NeverType();
    }
    public function getLastIterableValueType(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\NeverType();
    }
    public function isArray(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isConstantArray(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isOversizedArray(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isList(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isOffsetAccessible(): TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function isOffsetAccessLegal(): TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function hasOffsetValueType(\PHPStan\Type\Type $offsetType): TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function getOffsetValueType(\PHPStan\Type\Type $offsetType): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\NeverType();
    }
    public function setOffsetValueType(?\PHPStan\Type\Type $offsetType, \PHPStan\Type\Type $valueType, bool $unionValues = \true): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function setExistingOffsetValueType(\PHPStan\Type\Type $offsetType, \PHPStan\Type\Type $valueType): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function unsetOffset(\PHPStan\Type\Type $offsetType): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\NeverType();
    }
    public function getKeysArray(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\NeverType();
    }
    public function getValuesArray(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\NeverType();
    }
    public function chunkArray(\PHPStan\Type\Type $lengthType, TrinaryLogic $preserveKeys): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\NeverType();
    }
    public function fillKeysArray(\PHPStan\Type\Type $valueType): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\NeverType();
    }
    public function flipArray(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\NeverType();
    }
    public function intersectKeyArray(\PHPStan\Type\Type $otherArraysType): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\NeverType();
    }
    public function popArray(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\NeverType();
    }
    public function reverseArray(TrinaryLogic $preserveKeys): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\NeverType();
    }
    public function searchArray(\PHPStan\Type\Type $needleType): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\NeverType();
    }
    public function shiftArray(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\NeverType();
    }
    public function shuffleArray(): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\NeverType();
    }
    public function sliceArray(\PHPStan\Type\Type $offsetType, \PHPStan\Type\Type $lengthType, TrinaryLogic $preserveKeys): \PHPStan\Type\Type
    {
        return new \PHPStan\Type\NeverType();
    }
    public function isCallable(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array
    {
        throw new ShouldNotHappenException();
    }
    public function isCloneable(): TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function toNumber(): \PHPStan\Type\Type
    {
        return $this;
    }
    public function toAbsoluteNumber(): \PHPStan\Type\Type
    {
        return $this;
    }
    public function toString(): \PHPStan\Type\Type
    {
        return $this;
    }
    public function toInteger(): \PHPStan\Type\Type
    {
        return $this;
    }
    public function toFloat(): \PHPStan\Type\Type
    {
        return $this;
    }
    public function toArray(): \PHPStan\Type\Type
    {
        return $this;
    }
    public function toArrayKey(): \PHPStan\Type\Type
    {
        return $this;
    }
    public function toCoercedArgumentType(bool $strictTypes): \PHPStan\Type\Type
    {
        return $this;
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
    public function isUppercaseString(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isClassString(): TrinaryLogic
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
        return TrinaryLogic::createNo();
    }
    public function looseCompare(\PHPStan\Type\Type $type, PhpVersion $phpVersion): \PHPStan\Type\BooleanType
    {
        return new \PHPStan\Type\BooleanType();
    }
    public function getEnumCases(): array
    {
        return [];
    }
    public function exponentiate(\PHPStan\Type\Type $exponent): \PHPStan\Type\Type
    {
        return $this;
    }
    public function getFiniteTypes(): array
    {
        return [];
    }
    public function toPhpDocNode(): TypeNode
    {
        return new IdentifierTypeNode('never');
    }
}
