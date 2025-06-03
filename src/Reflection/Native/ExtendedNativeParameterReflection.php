<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Native;

use PHPStan\Reflection\AttributeReflection;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
final class ExtendedNativeParameterReflection implements ExtendedParameterReflection
{
    private string $name;
    private bool $optional;
    private Type $type;
    private Type $phpDocType;
    private Type $nativeType;
    private PassedByReference $passedByReference;
    private bool $variadic;
    private ?Type $defaultValue;
    private ?Type $outType;
    private TrinaryLogic $immediatelyInvokedCallable;
    private ?Type $closureThisType;
    /**
     * @var list<AttributeReflection>
     */
    private array $attributes;
    /**
     * @param list<AttributeReflection> $attributes
     */
    public function __construct(string $name, bool $optional, Type $type, Type $phpDocType, Type $nativeType, PassedByReference $passedByReference, bool $variadic, ?Type $defaultValue, ?Type $outType, TrinaryLogic $immediatelyInvokedCallable, ?Type $closureThisType, array $attributes)
    {
        $this->name = $name;
        $this->optional = $optional;
        $this->type = $type;
        $this->phpDocType = $phpDocType;
        $this->nativeType = $nativeType;
        $this->passedByReference = $passedByReference;
        $this->variadic = $variadic;
        $this->defaultValue = $defaultValue;
        $this->outType = $outType;
        $this->immediatelyInvokedCallable = $immediatelyInvokedCallable;
        $this->closureThisType = $closureThisType;
        $this->attributes = $attributes;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function isOptional(): bool
    {
        return $this->optional;
    }
    public function getType(): Type
    {
        return $this->type;
    }
    public function getPhpDocType(): Type
    {
        return $this->phpDocType;
    }
    public function hasNativeType(): bool
    {
        return !$this->nativeType instanceof MixedType || $this->nativeType->isExplicitMixed();
    }
    public function getNativeType(): Type
    {
        return $this->nativeType;
    }
    public function passedByReference(): PassedByReference
    {
        return $this->passedByReference;
    }
    public function isVariadic(): bool
    {
        return $this->variadic;
    }
    public function getDefaultValue(): ?Type
    {
        return $this->defaultValue;
    }
    public function getOutType(): ?Type
    {
        return $this->outType;
    }
    public function isImmediatelyInvokedCallable(): TrinaryLogic
    {
        return $this->immediatelyInvokedCallable;
    }
    public function getClosureThisType(): ?Type
    {
        return $this->closureThisType;
    }
    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
