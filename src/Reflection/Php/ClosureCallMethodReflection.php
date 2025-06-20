<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Php;

use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedFunctionVariant;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ClosureType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\Type;
use function array_map;
use function array_unshift;
use function is_bool;
final class ClosureCallMethodReflection implements ExtendedMethodReflection
{
    private ExtendedMethodReflection $nativeMethodReflection;
    private ClosureType $closureType;
    public function __construct(ExtendedMethodReflection $nativeMethodReflection, ClosureType $closureType)
    {
        $this->nativeMethodReflection = $nativeMethodReflection;
        $this->closureType = $closureType;
    }
    public function getDeclaringClass(): ClassReflection
    {
        return $this->nativeMethodReflection->getDeclaringClass();
    }
    public function isStatic(): bool
    {
        return $this->nativeMethodReflection->isStatic();
    }
    public function isPrivate(): bool
    {
        return $this->nativeMethodReflection->isPrivate();
    }
    public function isPublic(): bool
    {
        return $this->nativeMethodReflection->isPublic();
    }
    public function getDocComment(): ?string
    {
        return $this->nativeMethodReflection->getDocComment();
    }
    public function getName(): string
    {
        return $this->nativeMethodReflection->getName();
    }
    public function getPrototype(): ClassMemberReflection
    {
        return $this->nativeMethodReflection->getPrototype();
    }
    public function getVariants(): array
    {
        $parameters = $this->closureType->getParameters();
        $newThis = new NativeParameterReflection('newThis', \false, new ObjectWithoutClassType(), PassedByReference::createNo(), \false, null);
        array_unshift($parameters, $newThis);
        return [new ExtendedFunctionVariant($this->closureType->getTemplateTypeMap(), $this->closureType->getResolvedTemplateTypeMap(), array_map(static fn(ParameterReflection $parameter): ExtendedParameterReflection => new \PHPStan\Reflection\Php\ExtendedDummyParameter($parameter->getName(), $parameter->getType(), $parameter->isOptional(), $parameter->passedByReference(), $parameter->isVariadic(), $parameter->getDefaultValue(), new MixedType(), $parameter->getType(), null, TrinaryLogic::createMaybe(), null, []), $parameters), $this->closureType->isVariadic(), $this->closureType->getReturnType(), $this->closureType->getReturnType(), new MixedType(), $this->closureType->getCallSiteVarianceMap())];
    }
    public function getOnlyVariant(): ExtendedParametersAcceptor
    {
        return $this->getVariants()[0];
    }
    public function getNamedArgumentsVariants(): ?array
    {
        return null;
    }
    public function isDeprecated(): TrinaryLogic
    {
        return $this->nativeMethodReflection->isDeprecated();
    }
    public function getDeprecatedDescription(): ?string
    {
        return $this->nativeMethodReflection->getDeprecatedDescription();
    }
    public function isFinal(): TrinaryLogic
    {
        return $this->nativeMethodReflection->isFinal();
    }
    public function isFinalByKeyword(): TrinaryLogic
    {
        return $this->nativeMethodReflection->isFinalByKeyword();
    }
    public function isInternal(): TrinaryLogic
    {
        return $this->nativeMethodReflection->isInternal();
    }
    public function isBuiltin(): TrinaryLogic
    {
        $builtin = $this->nativeMethodReflection->isBuiltin();
        if (is_bool($builtin)) {
            return TrinaryLogic::createFromBoolean($builtin);
        }
        return $builtin;
    }
    public function getThrowType(): ?Type
    {
        return $this->nativeMethodReflection->getThrowType();
    }
    public function hasSideEffects(): TrinaryLogic
    {
        return $this->nativeMethodReflection->hasSideEffects();
    }
    public function getAsserts(): Assertions
    {
        return $this->nativeMethodReflection->getAsserts();
    }
    public function acceptsNamedArguments(): TrinaryLogic
    {
        return $this->nativeMethodReflection->acceptsNamedArguments();
    }
    public function getSelfOutType(): ?Type
    {
        return $this->nativeMethodReflection->getSelfOutType();
    }
    public function returnsByReference(): TrinaryLogic
    {
        return $this->nativeMethodReflection->returnsByReference();
    }
    public function isAbstract(): TrinaryLogic
    {
        $abstract = $this->nativeMethodReflection->isAbstract();
        if (is_bool($abstract)) {
            return TrinaryLogic::createFromBoolean($abstract);
        }
        return $abstract;
    }
    public function isPure(): TrinaryLogic
    {
        return $this->nativeMethodReflection->isPure();
    }
    public function getAttributes(): array
    {
        return $this->nativeMethodReflection->getAttributes();
    }
}
