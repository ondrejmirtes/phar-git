<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Php;

use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedFunctionVariant;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
final class EnumCasesMethodReflection implements ExtendedMethodReflection
{
    private ClassReflection $declaringClass;
    private Type $returnType;
    public function __construct(ClassReflection $declaringClass, Type $returnType)
    {
        $this->declaringClass = $declaringClass;
        $this->returnType = $returnType;
    }
    public function getDeclaringClass() : ClassReflection
    {
        return $this->declaringClass;
    }
    public function isStatic() : bool
    {
        return \true;
    }
    public function isPrivate() : bool
    {
        return \false;
    }
    public function isPublic() : bool
    {
        return \true;
    }
    public function getDocComment() : ?string
    {
        return null;
    }
    public function getName() : string
    {
        return 'cases';
    }
    public function getPrototype() : ClassMemberReflection
    {
        $unitEnum = $this->declaringClass->getAncestorWithClassName('UnitEnum');
        if ($unitEnum === null) {
            throw new ShouldNotHappenException();
        }
        return $unitEnum->getNativeMethod('cases');
    }
    public function getVariants() : array
    {
        return [new ExtendedFunctionVariant(TemplateTypeMap::createEmpty(), TemplateTypeMap::createEmpty(), [], \false, $this->returnType, new MixedType(), $this->returnType)];
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
        return TrinaryLogic::createYes();
    }
    public function isFinalByKeyword() : TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function isInternal() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isBuiltin() : TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function getThrowType() : ?Type
    {
        return null;
    }
    public function hasSideEffects() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
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
        return TrinaryLogic::createNo();
    }
    public function isAbstract() : TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isPure() : TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function getAttributes() : array
    {
        return [];
    }
}
