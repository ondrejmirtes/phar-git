<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PHPStan\Reflection\Php\ExtendedDummyParameter;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use function array_map;
final class WrappedExtendedMethodReflection implements \PHPStan\Reflection\ExtendedMethodReflection
{
    private \PHPStan\Reflection\MethodReflection $method;
    public function __construct(\PHPStan\Reflection\MethodReflection $method)
    {
        $this->method = $method;
    }
    public function getDeclaringClass() : \PHPStan\Reflection\ClassReflection
    {
        return $this->method->getDeclaringClass();
    }
    public function isStatic() : bool
    {
        return $this->method->isStatic();
    }
    public function isPrivate() : bool
    {
        return $this->method->isPrivate();
    }
    public function isPublic() : bool
    {
        return $this->method->isPublic();
    }
    public function getDocComment() : ?string
    {
        return $this->method->getDocComment();
    }
    public function getName() : string
    {
        return $this->method->getName();
    }
    public function getPrototype() : \PHPStan\Reflection\ClassMemberReflection
    {
        return $this->method->getPrototype();
    }
    public function getVariants() : array
    {
        $variants = [];
        foreach ($this->method->getVariants() as $variant) {
            if ($variant instanceof \PHPStan\Reflection\ExtendedParametersAcceptor) {
                $variants[] = $variant;
                continue;
            }
            $variants[] = new \PHPStan\Reflection\ExtendedFunctionVariant($variant->getTemplateTypeMap(), $variant->getResolvedTemplateTypeMap(), array_map(static fn(\PHPStan\Reflection\ParameterReflection $parameter): \PHPStan\Reflection\ExtendedParameterReflection => $parameter instanceof \PHPStan\Reflection\ExtendedParameterReflection ? $parameter : new ExtendedDummyParameter($parameter->getName(), $parameter->getType(), $parameter->isOptional(), $parameter->passedByReference(), $parameter->isVariadic(), $parameter->getDefaultValue(), new MixedType(), $parameter->getType(), null, TrinaryLogic::createMaybe(), null, []), $variant->getParameters()), $variant->isVariadic(), $variant->getReturnType(), $variant->getReturnType(), new MixedType(), TemplateTypeVarianceMap::createEmpty());
        }
        return $variants;
    }
    public function getOnlyVariant() : \PHPStan\Reflection\ExtendedParametersAcceptor
    {
        return $this->getVariants()[0];
    }
    public function getNamedArgumentsVariants() : ?array
    {
        return null;
    }
    public function isDeprecated() : TrinaryLogic
    {
        return $this->method->isDeprecated();
    }
    public function getDeprecatedDescription() : ?string
    {
        return $this->method->getDeprecatedDescription();
    }
    public function isFinal() : TrinaryLogic
    {
        return $this->method->isFinal();
    }
    public function isFinalByKeyword() : TrinaryLogic
    {
        return $this->isFinal();
    }
    public function isInternal() : TrinaryLogic
    {
        return $this->method->isInternal();
    }
    public function isBuiltin() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function getThrowType() : ?Type
    {
        return $this->method->getThrowType();
    }
    public function hasSideEffects() : TrinaryLogic
    {
        return $this->method->hasSideEffects();
    }
    public function isPure() : TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
    public function getAsserts() : \PHPStan\Reflection\Assertions
    {
        return \PHPStan\Reflection\Assertions::createEmpty();
    }
    public function acceptsNamedArguments() : TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->getDeclaringClass()->acceptsNamedArguments());
    }
    public function getSelfOutType() : ?Type
    {
        return null;
    }
    public function returnsByReference() : TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
    public function isAbstract() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function getAttributes() : array
    {
        return [];
    }
}
