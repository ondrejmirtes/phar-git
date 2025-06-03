<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Php;

use PHPStan\Reflection\AttributeReflection;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypehintHelper;
final class PhpParameterFromParserNodeReflection implements ExtendedParameterReflection
{
    private string $name;
    private bool $optional;
    private Type $realType;
    private ?Type $phpDocType;
    private PassedByReference $passedByReference;
    private ?Type $defaultValue;
    private bool $variadic;
    private ?Type $outType;
    private TrinaryLogic $immediatelyInvokedCallable;
    private ?Type $closureThisType;
    /**
     * @var list<AttributeReflection>
     */
    private array $attributes;
    private ?Type $type = null;
    /**
     * @param list<AttributeReflection> $attributes
     */
    public function __construct(string $name, bool $optional, Type $realType, ?Type $phpDocType, PassedByReference $passedByReference, ?Type $defaultValue, bool $variadic, ?Type $outType, TrinaryLogic $immediatelyInvokedCallable, ?Type $closureThisType, array $attributes)
    {
        $this->name = $name;
        $this->optional = $optional;
        $this->realType = $realType;
        $this->phpDocType = $phpDocType;
        $this->passedByReference = $passedByReference;
        $this->defaultValue = $defaultValue;
        $this->variadic = $variadic;
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
        if ($this->type === null) {
            $phpDocType = $this->phpDocType;
            if ($phpDocType !== null && $this->defaultValue !== null) {
                if ($this->defaultValue->isNull()->yes()) {
                    $inferred = $phpDocType->inferTemplateTypes($this->defaultValue);
                    if ($inferred->isEmpty()) {
                        $phpDocType = TypeCombinator::addNull($phpDocType);
                    }
                }
            }
            $this->type = TypehintHelper::decideType($this->realType, $phpDocType);
        }
        return $this->type;
    }
    public function getPhpDocType(): Type
    {
        return $this->phpDocType ?? new MixedType();
    }
    public function hasNativeType(): bool
    {
        return !$this->realType instanceof MixedType || $this->realType->isExplicitMixed();
    }
    public function getNativeType(): Type
    {
        return $this->realType;
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
