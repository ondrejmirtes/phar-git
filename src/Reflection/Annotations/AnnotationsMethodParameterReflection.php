<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Annotations;

use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
final class AnnotationsMethodParameterReflection implements ExtendedParameterReflection
{
    private string $name;
    private Type $type;
    private PassedByReference $passedByReference;
    private bool $isOptional;
    private bool $isVariadic;
    private ?Type $defaultValue;
    public function __construct(string $name, Type $type, PassedByReference $passedByReference, bool $isOptional, bool $isVariadic, ?Type $defaultValue)
    {
        $this->name = $name;
        $this->type = $type;
        $this->passedByReference = $passedByReference;
        $this->isOptional = $isOptional;
        $this->isVariadic = $isVariadic;
        $this->defaultValue = $defaultValue;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function isOptional(): bool
    {
        return $this->isOptional;
    }
    public function getType(): Type
    {
        return $this->type;
    }
    public function getPhpDocType(): Type
    {
        return $this->type;
    }
    public function hasNativeType(): bool
    {
        return \false;
    }
    public function getNativeType(): Type
    {
        return new MixedType();
    }
    public function getOutType(): ?Type
    {
        return null;
    }
    public function isImmediatelyInvokedCallable(): TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
    public function getClosureThisType(): ?Type
    {
        return null;
    }
    public function passedByReference(): PassedByReference
    {
        return $this->passedByReference;
    }
    public function isVariadic(): bool
    {
        return $this->isVariadic;
    }
    public function getDefaultValue(): ?Type
    {
        return $this->defaultValue;
    }
    public function getAttributes(): array
    {
        return [];
    }
}
