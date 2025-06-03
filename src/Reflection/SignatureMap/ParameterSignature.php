<?php

declare (strict_types=1);
namespace PHPStan\Reflection\SignatureMap;

use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\Type;
final class ParameterSignature
{
    private string $name;
    private bool $optional;
    private Type $type;
    private Type $nativeType;
    private PassedByReference $passedByReference;
    private bool $variadic;
    private ?Type $defaultValue;
    private ?Type $outType;
    public function __construct(string $name, bool $optional, Type $type, Type $nativeType, PassedByReference $passedByReference, bool $variadic, ?Type $defaultValue, ?Type $outType)
    {
        $this->name = $name;
        $this->optional = $optional;
        $this->type = $type;
        $this->nativeType = $nativeType;
        $this->passedByReference = $passedByReference;
        $this->variadic = $variadic;
        $this->defaultValue = $defaultValue;
        $this->outType = $outType;
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
}
