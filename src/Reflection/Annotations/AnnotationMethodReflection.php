<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Annotations;

use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedFunctionVariant;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\ThisType;
use PHPStan\Type\Type;
final class AnnotationMethodReflection implements ExtendedMethodReflection
{
    private string $name;
    private ClassReflection $declaringClass;
    private Type $returnType;
    /**
     * @var list<AnnotationsMethodParameterReflection>
     */
    private array $parameters;
    private bool $isStatic;
    private bool $isVariadic;
    private ?Type $throwType;
    private TemplateTypeMap $templateTypeMap;
    /** @var list<ExtendedFunctionVariant>|null */
    private ?array $variants = null;
    /**
     * @param list<AnnotationsMethodParameterReflection> $parameters
     */
    public function __construct(string $name, ClassReflection $declaringClass, Type $returnType, array $parameters, bool $isStatic, bool $isVariadic, ?Type $throwType, TemplateTypeMap $templateTypeMap)
    {
        $this->name = $name;
        $this->declaringClass = $declaringClass;
        $this->returnType = $returnType;
        $this->parameters = $parameters;
        $this->isStatic = $isStatic;
        $this->isVariadic = $isVariadic;
        $this->throwType = $throwType;
        $this->templateTypeMap = $templateTypeMap;
    }
    public function getDeclaringClass() : ClassReflection
    {
        return $this->declaringClass;
    }
    public function getPrototype() : ClassMemberReflection
    {
        return $this;
    }
    public function isStatic() : bool
    {
        return $this->isStatic;
    }
    public function isPrivate() : bool
    {
        return \false;
    }
    public function isPublic() : bool
    {
        return \true;
    }
    public function getName() : string
    {
        return $this->name;
    }
    public function getVariants() : array
    {
        if ($this->variants === null) {
            $this->variants = [new ExtendedFunctionVariant($this->templateTypeMap, null, $this->parameters, $this->isVariadic, $this->returnType, $this->returnType, new MixedType())];
        }
        return $this->variants;
    }
    public function getOnlyVariant() : ExtendedParametersAcceptor
    {
        return $this->getVariants()[0];
    }
    public function getNamedArgumentsVariants() : ?array
    {
        return null;
    }
    public function isDeprecated() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function getDeprecatedDescription() : ?string
    {
        return null;
    }
    public function isFinal() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isFinalByKeyword() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isInternal() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isBuiltin() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function getThrowType() : ?Type
    {
        return $this->throwType;
    }
    public function hasSideEffects() : TrinaryLogic
    {
        if ($this->returnType->isVoid()->yes()) {
            return TrinaryLogic::createYes();
        }
        if ((new ThisType($this->declaringClass))->isSuperTypeOf($this->returnType)->yes()) {
            return TrinaryLogic::createYes();
        }
        return TrinaryLogic::createMaybe();
    }
    public function getDocComment() : ?string
    {
        return null;
    }
    public function getAsserts() : Assertions
    {
        return Assertions::createEmpty();
    }
    public function acceptsNamedArguments() : TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->declaringClass->acceptsNamedArguments());
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
    public function isPure() : TrinaryLogic
    {
        if ($this->hasSideEffects()->yes()) {
            return TrinaryLogic::createNo();
        }
        return TrinaryLogic::createMaybe();
    }
    public function getAttributes() : array
    {
        return [];
    }
}
