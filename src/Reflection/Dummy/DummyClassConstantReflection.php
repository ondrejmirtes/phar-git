<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Dummy;

use PhpParser\Node\Expr;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use stdClass;
final class DummyClassConstantReflection implements ClassConstantReflection
{
    private string $name;
    public function __construct(string $name)
    {
        $this->name = $name;
    }
    public function getDeclaringClass() : ClassReflection
    {
        $reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
        return $reflectionProvider->getClass(stdClass::class);
    }
    public function isFinal() : bool
    {
        return \false;
    }
    public function getFileName() : ?string
    {
        return null;
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
    public function getName() : string
    {
        return $this->name;
    }
    public function getValueType() : Type
    {
        return new MixedType();
    }
    public function getValueExpr() : Expr
    {
        return new TypeExpr(new MixedType());
    }
    public function isDeprecated() : TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
    public function getDeprecatedDescription() : ?string
    {
        return null;
    }
    public function isInternal() : TrinaryLogic
    {
        return TrinaryLogic::createMaybe();
    }
    public function getDocComment() : ?string
    {
        return null;
    }
    public function hasPhpDocType() : bool
    {
        return \false;
    }
    public function getPhpDocType() : ?Type
    {
        return null;
    }
    public function hasNativeType() : bool
    {
        return \false;
    }
    public function getNativeType() : ?Type
    {
        return null;
    }
    public function getAttributes() : array
    {
        return [];
    }
}
