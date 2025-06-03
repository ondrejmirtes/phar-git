<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Annotations;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
final class AnnotationPropertyReflection implements ExtendedPropertyReflection
{
    private string $name;
    private ClassReflection $declaringClass;
    private Type $readableType;
    private Type $writableType;
    private bool $readable;
    private bool $writable;
    public function __construct(string $name, ClassReflection $declaringClass, Type $readableType, Type $writableType, bool $readable, bool $writable)
    {
        $this->name = $name;
        $this->declaringClass = $declaringClass;
        $this->readableType = $readableType;
        $this->writableType = $writableType;
        $this->readable = $readable;
        $this->writable = $writable;
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function getDeclaringClass(): ClassReflection
    {
        return $this->declaringClass;
    }
    public function isStatic(): bool
    {
        return \false;
    }
    public function isPrivate(): bool
    {
        return \false;
    }
    public function isPublic(): bool
    {
        return \true;
    }
    public function hasPhpDocType(): bool
    {
        return \true;
    }
    public function getPhpDocType(): Type
    {
        return $this->readableType;
    }
    public function hasNativeType(): bool
    {
        return \false;
    }
    public function getNativeType(): Type
    {
        return new MixedType();
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
        return $this->readableType->equals($this->writableType);
    }
    public function isReadable(): bool
    {
        return $this->readable;
    }
    public function isWritable(): bool
    {
        return $this->writable;
    }
    public function isDeprecated(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function getDeprecatedDescription(): ?string
    {
        return null;
    }
    public function isInternal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function getDocComment(): ?string
    {
        return null;
    }
    public function isAbstract(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isFinalByKeyword(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isFinal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function isVirtual(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function hasHook(string $hookType): bool
    {
        return \false;
    }
    public function getHook(string $hookType): ExtendedMethodReflection
    {
        throw new ShouldNotHappenException();
    }
    public function isProtectedSet(): bool
    {
        return \false;
    }
    public function isPrivateSet(): bool
    {
        return \false;
    }
    public function getAttributes(): array
    {
        return [];
    }
}
