<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Dummy;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\Reflection\WrapperPropertyReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
final class ChangedTypePropertyReflection implements WrapperPropertyReflection
{
    private ClassReflection $declaringClass;
    private ExtendedPropertyReflection $reflection;
    private Type $readableType;
    private Type $writableType;
    private Type $phpDocType;
    private Type $nativeType;
    public function __construct(ClassReflection $declaringClass, ExtendedPropertyReflection $reflection, Type $readableType, Type $writableType, Type $phpDocType, Type $nativeType)
    {
        $this->declaringClass = $declaringClass;
        $this->reflection = $reflection;
        $this->readableType = $readableType;
        $this->writableType = $writableType;
        $this->phpDocType = $phpDocType;
        $this->nativeType = $nativeType;
    }
    public function getName(): string
    {
        return $this->reflection->getName();
    }
    public function getDeclaringClass(): ClassReflection
    {
        return $this->declaringClass;
    }
    public function isStatic(): bool
    {
        return $this->reflection->isStatic();
    }
    public function isPrivate(): bool
    {
        return $this->reflection->isPrivate();
    }
    public function isPublic(): bool
    {
        return $this->reflection->isPublic();
    }
    public function getDocComment(): ?string
    {
        return $this->reflection->getDocComment();
    }
    public function hasPhpDocType(): bool
    {
        return $this->reflection->hasPhpDocType();
    }
    public function getPhpDocType(): Type
    {
        return $this->phpDocType;
    }
    public function hasNativeType(): bool
    {
        return $this->reflection->hasNativeType();
    }
    public function getNativeType(): Type
    {
        return $this->nativeType;
    }
    public function getReadableType(): Type
    {
        return $this->readableType;
    }
    public function getWritableType(): Type
    {
        return $this->writableType;
    }
    public function canChangeTypeAfterAssignment(): bool
    {
        return $this->reflection->canChangeTypeAfterAssignment();
    }
    public function isReadable(): bool
    {
        return $this->reflection->isReadable();
    }
    public function isWritable(): bool
    {
        return $this->reflection->isWritable();
    }
    public function isDeprecated(): TrinaryLogic
    {
        return $this->reflection->isDeprecated();
    }
    public function getDeprecatedDescription(): ?string
    {
        return $this->reflection->getDeprecatedDescription();
    }
    public function isInternal(): TrinaryLogic
    {
        return $this->reflection->isInternal();
    }
    public function getOriginalReflection(): ExtendedPropertyReflection
    {
        return $this->reflection;
    }
    public function isAbstract(): TrinaryLogic
    {
        return $this->reflection->isAbstract();
    }
    public function isFinalByKeyword(): TrinaryLogic
    {
        return $this->reflection->isFinalByKeyword();
    }
    public function isFinal(): TrinaryLogic
    {
        return $this->reflection->isFinal();
    }
    public function isVirtual(): TrinaryLogic
    {
        return $this->reflection->isVirtual();
    }
    public function hasHook(string $hookType): bool
    {
        return $this->reflection->hasHook($hookType);
    }
    public function getHook(string $hookType): ExtendedMethodReflection
    {
        return $this->reflection->getHook($hookType);
    }
    public function isProtectedSet(): bool
    {
        return $this->reflection->isProtectedSet();
    }
    public function isPrivateSet(): bool
    {
        return $this->reflection->isPrivateSet();
    }
    public function getAttributes(): array
    {
        return $this->reflection->getAttributes();
    }
}
