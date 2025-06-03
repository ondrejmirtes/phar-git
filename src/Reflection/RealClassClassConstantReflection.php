<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PhpParser\Node\Expr;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClassConstant;
use PHPStan\Internal\DeprecatedAttributeHelper;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use PHPStan\Type\TypehintHelper;
final class RealClassClassConstantReflection implements \PHPStan\Reflection\ClassConstantReflection
{
    private \PHPStan\Reflection\InitializerExprTypeResolver $initializerExprTypeResolver;
    private \PHPStan\Reflection\ClassReflection $declaringClass;
    private ReflectionClassConstant $reflection;
    private ?Type $nativeType;
    private ?Type $phpDocType;
    private ?string $deprecatedDescription;
    private bool $isDeprecated;
    private bool $isInternal;
    private bool $isFinal;
    /**
     * @var list<AttributeReflection>
     */
    private array $attributes;
    private ?Type $valueType = null;
    /**
     * @param list<AttributeReflection> $attributes
     */
    public function __construct(\PHPStan\Reflection\InitializerExprTypeResolver $initializerExprTypeResolver, \PHPStan\Reflection\ClassReflection $declaringClass, ReflectionClassConstant $reflection, ?Type $nativeType, ?Type $phpDocType, ?string $deprecatedDescription, bool $isDeprecated, bool $isInternal, bool $isFinal, array $attributes)
    {
        $this->initializerExprTypeResolver = $initializerExprTypeResolver;
        $this->declaringClass = $declaringClass;
        $this->reflection = $reflection;
        $this->nativeType = $nativeType;
        $this->phpDocType = $phpDocType;
        $this->deprecatedDescription = $deprecatedDescription;
        $this->isDeprecated = $isDeprecated;
        $this->isInternal = $isInternal;
        $this->isFinal = $isFinal;
        $this->attributes = $attributes;
    }
    public function getName() : string
    {
        return $this->reflection->getName();
    }
    public function getFileName() : ?string
    {
        return $this->declaringClass->getFileName();
    }
    public function getValueExpr() : Expr
    {
        return $this->reflection->getValueExpression();
    }
    public function hasPhpDocType() : bool
    {
        return $this->phpDocType !== null;
    }
    public function getPhpDocType() : ?Type
    {
        return $this->phpDocType;
    }
    public function hasNativeType() : bool
    {
        return $this->nativeType !== null;
    }
    public function getNativeType() : ?Type
    {
        return $this->nativeType;
    }
    public function getValueType() : Type
    {
        if ($this->valueType === null) {
            if ($this->phpDocType !== null) {
                if ($this->nativeType !== null) {
                    return $this->valueType = TypehintHelper::decideType($this->nativeType, $this->phpDocType);
                }
                return $this->phpDocType;
            } elseif ($this->nativeType !== null) {
                return $this->nativeType;
            }
            $this->valueType = $this->initializerExprTypeResolver->getType($this->getValueExpr(), \PHPStan\Reflection\InitializerExprContext::fromClassReflection($this->declaringClass));
        }
        return $this->valueType;
    }
    public function getDeclaringClass() : \PHPStan\Reflection\ClassReflection
    {
        return $this->declaringClass;
    }
    public function isStatic() : bool
    {
        return \true;
    }
    public function isPrivate() : bool
    {
        return $this->reflection->isPrivate();
    }
    public function isPublic() : bool
    {
        return $this->reflection->isPublic();
    }
    public function isFinal() : bool
    {
        return $this->isFinal || $this->reflection->isFinal();
    }
    public function isDeprecated() : TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->isDeprecated || $this->reflection->isDeprecated());
    }
    public function getDeprecatedDescription() : ?string
    {
        if ($this->isDeprecated) {
            return $this->deprecatedDescription;
        }
        if ($this->reflection->isDeprecated()) {
            $attributes = $this->reflection->getBetterReflection()->getAttributes();
            return DeprecatedAttributeHelper::getDeprecatedDescription($attributes);
        }
        return null;
    }
    public function isInternal() : TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->isInternal);
    }
    public function getDocComment() : ?string
    {
        $docComment = $this->reflection->getDocComment();
        if ($docComment === \false) {
            return null;
        }
        return $docComment;
    }
    public function getAttributes() : array
    {
        return $this->attributes;
    }
}
