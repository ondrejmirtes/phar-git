<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Type;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedPropertyReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_map;
use function count;
use function implode;
final class IntersectionTypePropertyReflection implements ExtendedPropertyReflection
{
    /**
     * @var ExtendedPropertyReflection[]
     */
    private array $properties;
    /**
     * @param ExtendedPropertyReflection[] $properties
     */
    public function __construct(array $properties)
    {
        $this->properties = $properties;
    }
    public function getName(): string
    {
        return $this->properties[0]->getName();
    }
    public function getDeclaringClass(): ClassReflection
    {
        return $this->properties[0]->getDeclaringClass();
    }
    public function isStatic(): bool
    {
        return $this->computeResult(static fn(ExtendedPropertyReflection $property) => $property->isStatic());
    }
    public function isPrivate(): bool
    {
        return $this->computeResult(static fn(ExtendedPropertyReflection $property) => $property->isPrivate());
    }
    public function isPublic(): bool
    {
        return $this->computeResult(static fn(ExtendedPropertyReflection $property) => $property->isPublic());
    }
    public function isDeprecated(): TrinaryLogic
    {
        return TrinaryLogic::lazyMaxMin($this->properties, static fn(ExtendedPropertyReflection $propertyReflection): TrinaryLogic => $propertyReflection->isDeprecated());
    }
    public function getDeprecatedDescription(): ?string
    {
        $descriptions = [];
        foreach ($this->properties as $property) {
            if (!$property->isDeprecated()->yes()) {
                continue;
            }
            $description = $property->getDeprecatedDescription();
            if ($description === null) {
                continue;
            }
            $descriptions[] = $description;
        }
        if (count($descriptions) === 0) {
            return null;
        }
        return implode(' ', $descriptions);
    }
    public function isInternal(): TrinaryLogic
    {
        return TrinaryLogic::lazyMaxMin($this->properties, static fn(ExtendedPropertyReflection $propertyReflection): TrinaryLogic => $propertyReflection->isInternal());
    }
    public function getDocComment(): ?string
    {
        return null;
    }
    public function hasPhpDocType(): bool
    {
        return $this->computeResult(static fn(ExtendedPropertyReflection $property) => $property->hasPhpDocType());
    }
    public function getPhpDocType(): Type
    {
        return TypeCombinator::intersect(...array_map(static fn(ExtendedPropertyReflection $property): Type => $property->getPhpDocType(), $this->properties));
    }
    public function hasNativeType(): bool
    {
        return $this->computeResult(static fn(ExtendedPropertyReflection $property) => $property->hasNativeType());
    }
    public function getNativeType(): Type
    {
        return TypeCombinator::intersect(...array_map(static fn(ExtendedPropertyReflection $property): Type => $property->getNativeType(), $this->properties));
    }
    public function getReadableType(): Type
    {
        return TypeCombinator::intersect(...array_map(static fn(ExtendedPropertyReflection $property): Type => $property->getReadableType(), $this->properties));
    }
    public function getWritableType(): Type
    {
        return TypeCombinator::intersect(...array_map(static fn(ExtendedPropertyReflection $property): Type => $property->getWritableType(), $this->properties));
    }
    public function canChangeTypeAfterAssignment(): bool
    {
        return $this->computeResult(static fn(ExtendedPropertyReflection $property) => $property->canChangeTypeAfterAssignment());
    }
    public function isReadable(): bool
    {
        return $this->computeResult(static fn(ExtendedPropertyReflection $property) => $property->isReadable());
    }
    public function isWritable(): bool
    {
        return $this->computeResult(static fn(ExtendedPropertyReflection $property) => $property->isWritable());
    }
    /**
     * @param callable(ExtendedPropertyReflection): bool $cb
     */
    private function computeResult(callable $cb): bool
    {
        $result = \false;
        foreach ($this->properties as $property) {
            $result = $result || $cb($property);
        }
        return $result;
    }
    public function isAbstract(): TrinaryLogic
    {
        return TrinaryLogic::lazyMaxMin($this->properties, static fn(ExtendedPropertyReflection $propertyReflection): TrinaryLogic => $propertyReflection->isAbstract());
    }
    public function isFinalByKeyword(): TrinaryLogic
    {
        return TrinaryLogic::lazyMaxMin($this->properties, static fn(ExtendedPropertyReflection $propertyReflection): TrinaryLogic => $propertyReflection->isFinalByKeyword());
    }
    public function isFinal(): TrinaryLogic
    {
        return TrinaryLogic::lazyMaxMin($this->properties, static fn(ExtendedPropertyReflection $propertyReflection): TrinaryLogic => $propertyReflection->isFinal());
    }
    public function isVirtual(): TrinaryLogic
    {
        return TrinaryLogic::lazyMaxMin($this->properties, static fn(ExtendedPropertyReflection $propertyReflection): TrinaryLogic => $propertyReflection->isVirtual());
    }
    public function hasHook(string $hookType): bool
    {
        return $this->computeResult(static fn(ExtendedPropertyReflection $property) => $property->hasHook($hookType));
    }
    public function getHook(string $hookType): ExtendedMethodReflection
    {
        $hooks = [];
        foreach ($this->properties as $property) {
            if (!$property->hasHook($hookType)) {
                continue;
            }
            $hooks[] = $property->getHook($hookType);
        }
        if (count($hooks) === 0) {
            throw new ShouldNotHappenException();
        }
        if (count($hooks) === 1) {
            return $hooks[0];
        }
        return new \PHPStan\Reflection\Type\IntersectionTypeMethodReflection($hooks[0]->getName(), $hooks);
    }
    public function isProtectedSet(): bool
    {
        return $this->computeResult(static fn(ExtendedPropertyReflection $property) => $property->isProtectedSet());
    }
    public function isPrivateSet(): bool
    {
        return $this->computeResult(static fn(ExtendedPropertyReflection $property) => $property->isPrivateSet());
    }
    public function getAttributes(): array
    {
        return $this->properties[0]->getAttributes();
    }
}
