<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Php;

use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\Type;
class DummyParameter implements ParameterReflection
{
    private string $name;
    private Type $type;
    private bool $optional;
    private bool $variadic;
    private ?Type $defaultValue;
    private PassedByReference $passedByReference;
    public function __construct(string $name, Type $type, bool $optional, ?PassedByReference $passedByReference, bool $variadic, ?Type $defaultValue)
    {
        $this->name = $name;
        $this->type = $type;
        $this->optional = $optional;
        $this->variadic = $variadic;
        $this->defaultValue = $defaultValue;
        $this->passedByReference = $passedByReference ?? PassedByReference::createNo();
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
}
