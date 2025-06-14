<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Dummy;

use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedFunctionVariant;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\VoidType;
final class DummyConstructorReflection implements ExtendedMethodReflection
{
    private ClassReflection $declaringClass;
    public function __construct(ClassReflection $declaringClass)
    {
        $this->declaringClass = $declaringClass;
    }
    public function getDeclaringClass(): ClassReflection
    {
        return $this->declaringClass;
    }
    public function isStatic(): bool
    {
        return \false;
    }
    public function isPrivate(): bool
    {
        return \false;
    }
    public function isPublic(): bool
    {
        return \true;
    }
    public function getName(): string
    {
        return '__construct';
    }
    public function getPrototype(): ClassMemberReflection
    {
        return $this;
    }
    public function getVariants(): array
    {
        return [new ExtendedFunctionVariant(TemplateTypeMap::createEmpty(), null, [], \false, new VoidType(), new MixedType(), new MixedType(), null)];
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
        return TrinaryLogic::createMaybe();
    }
    public function getDeprecatedDescription(): ?string
    {
        return null;
    }
    public function isFinal(): TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
    public function isInternal(): TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
    public function isBuiltin(): TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
    public function getThrowType(): ?Type
    {
        return null;
    }
    public function hasSideEffects(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function getDocComment(): ?string
    {
        return null;
    }
    public function getAsserts(): Assertions
    {
        return Assertions::createEmpty();
    }
    public function acceptsNamedArguments(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->declaringClass->acceptsNamedArguments());
    }
    public function getSelfOutType(): ?Type
    {
        return null;
    }
    public function returnsByReference(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isFinalByKeyword(): TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
    public function isAbstract(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isPure(): TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function getAttributes(): array
    {
        return [];
    }
}
