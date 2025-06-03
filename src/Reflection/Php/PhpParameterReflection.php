<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Php;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionParameter;
use PHPStan\Reflection\AttributeReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\PassedByReference;
use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypehintHelper;
final class PhpParameterReflection implements ExtendedParameterReflection
{
    private InitializerExprTypeResolver $initializerExprTypeResolver;
    private ReflectionParameter $reflection;
    private ?Type $phpDocType;
    private ?ClassReflection $declaringClass;
    private ?Type $outType;
    private TrinaryLogic $immediatelyInvokedCallable;
    private ?Type $closureThisType;
    /**
     * @var list<AttributeReflection>
     */
    private array $attributes;
    private ?Type $type = null;
    private ?Type $nativeType = null;
    /**
     * @param list<AttributeReflection> $attributes
     */
    public function __construct(InitializerExprTypeResolver $initializerExprTypeResolver, ReflectionParameter $reflection, ?Type $phpDocType, ?ClassReflection $declaringClass, ?Type $outType, TrinaryLogic $immediatelyInvokedCallable, ?Type $closureThisType, array $attributes)
    {
        $this->initializerExprTypeResolver = $initializerExprTypeResolver;
        $this->reflection = $reflection;
        $this->phpDocType = $phpDocType;
        $this->declaringClass = $declaringClass;
        $this->outType = $outType;
        $this->immediatelyInvokedCallable = $immediatelyInvokedCallable;
        $this->closureThisType = $closureThisType;
        $this->attributes = $attributes;
    }
    public function isOptional() : bool
    {
        return $this->reflection->isOptional();
    }
    public function getName() : string
    {
        return $this->reflection->getName();
    }
    public function getType() : Type
    {
        if ($this->type === null) {
            $phpDocType = $this->phpDocType;
            if ($phpDocType !== null && $this->reflection->isDefaultValueAvailable()) {
                $defaultValueType = $this->initializerExprTypeResolver->getType($this->reflection->getDefaultValueExpression(), InitializerExprContext::fromReflectionParameter($this->reflection));
                if ($defaultValueType->isNull()->yes()) {
                    $phpDocType = TypeCombinator::addNull($phpDocType);
                }
            }
            $this->type = TypehintHelper::decideTypeFromReflection($this->reflection->getType(), $phpDocType, $this->declaringClass, $this->isVariadic());
        }
        return $this->type;
    }
    public function passedByReference() : PassedByReference
    {
        return $this->reflection->isPassedByReference() ? PassedByReference::createCreatesNewVariable() : PassedByReference::createNo();
    }
    public function isVariadic() : bool
    {
        return $this->reflection->isVariadic();
    }
    public function getPhpDocType() : Type
    {
        if ($this->phpDocType !== null) {
            return $this->phpDocType;
        }
        return new MixedType();
    }
    public function hasNativeType() : bool
    {
        return $this->reflection->getType() !== null;
    }
    public function getNativeType() : Type
    {
        if ($this->nativeType === null) {
            $this->nativeType = TypehintHelper::decideTypeFromReflection($this->reflection->getType(), null, $this->declaringClass, $this->isVariadic());
        }
        return $this->nativeType;
    }
    public function getDefaultValue() : ?Type
    {
        if ($this->reflection->isDefaultValueAvailable()) {
            return $this->initializerExprTypeResolver->getType($this->reflection->getDefaultValueExpression(), InitializerExprContext::fromReflectionParameter($this->reflection));
        }
        return null;
    }
    public function getOutType() : ?Type
    {
        return $this->outType;
    }
    public function isImmediatelyInvokedCallable() : TrinaryLogic
    {
        return $this->immediatelyInvokedCallable;
    }
    public function getClosureThisType() : ?Type
    {
        return $this->closureThisType;
    }
    public function getAttributes() : array
    {
        return $this->attributes;
    }
}
