<?php

declare (strict_types=1);
namespace PHPStan\BetterReflection\Reflection\Adapter;

use ArgumentCountError;
use OutOfBoundsException;
use PhpParser\Node\Expr;
use PropertyHookType;
use ReflectionException as CoreReflectionException;
use ReflectionMethod as CoreReflectionMethod;
use ReflectionProperty as CoreReflectionProperty;
use ReflectionType as CoreReflectionType;
use ReturnTypeWillChange;
use PHPStan\BetterReflection\Reflection\Exception\NoObjectProvided;
use PHPStan\BetterReflection\Reflection\Exception\NotAnObject;
use PHPStan\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use PHPStan\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use PHPStan\BetterReflection\Reflection\ReflectionProperty as BetterReflectionProperty;
use PHPStan\BetterReflection\Reflection\ReflectionPropertyHookType as BetterReflectionPropertyHookType;
use Throwable;
use TypeError;
use ValueError;
use function array_map;
use function gettype;
use function sprintf;
/** @psalm-suppress PropertyNotSetInConstructor */
final class ReflectionProperty extends CoreReflectionProperty
{
    private BetterReflectionProperty $betterReflectionProperty;
    /**
     * @internal
     *
     * @see CoreReflectionProperty::IS_FINAL
     */
    public const IS_FINAL_COMPATIBILITY = 32;
    /**
     * @internal
     *
     * @see CoreReflectionProperty::IS_ABSTRACT
     */
    public const IS_ABSTRACT_COMPATIBILITY = 64;
    /**
     * @internal
     *
     * @see CoreReflectionProperty::IS_VIRTUAL
     */
    public const IS_VIRTUAL_COMPATIBILITY = 512;
    /**
     * @internal
     *
     * @see CoreReflectionProperty::IS_PROTECTED_SET
     */
    public const IS_PROTECTED_SET_COMPATIBILITY = 2048;
    /**
     * @internal
     *
     * @see CoreReflectionProperty::IS_PRIVATE_SET
     */
    public const IS_PRIVATE_SET_COMPATIBILITY = 4096;
    /** @internal */
    public const IS_READONLY_COMPATIBILITY = 128;
    public function __construct(BetterReflectionProperty $betterReflectionProperty)
    {
        $this->betterReflectionProperty = $betterReflectionProperty;
        unset($this->name);
        unset($this->class);
    }
    /** @return non-empty-string */
    public function __toString(): string
    {
        return $this->betterReflectionProperty->__toString();
    }
    public function getName(): string
    {
        return $this->betterReflectionProperty->getName();
    }
    /**
     * {@inheritDoc}
     * @return mixed
     */
    #[ReturnTypeWillChange]
    public function getValue($object = null)
    {
        try {
            return $this->betterReflectionProperty->getValue($object);
        } catch (NoObjectProvided $exception) {
            return null;
        } catch (Throwable $e) {
            throw new CoreReflectionException($e->getMessage(), 0, $e);
        }
    }
    /** @psalm-suppress MethodSignatureMismatch
     * @param mixed $objectOrValue
     * @param mixed $value */
    public function setValue($objectOrValue, $value = null): void
    {
        try {
            $this->betterReflectionProperty->setValue($objectOrValue, $value);
        } catch (NoObjectProvided $exception) {
            throw new ArgumentCountError('ReflectionProperty::setValue() expects exactly 2 arguments, 1 given');
        } catch (NotAnObject $exception) {
            throw new TypeError(sprintf('ReflectionProperty::setValue(): Argument #1 ($objectOrValue) must be of type object, %s given', gettype($objectOrValue)));
        } catch (Throwable $e) {
            throw new CoreReflectionException($e->getMessage(), 0, $e);
        }
    }
    /**
     * @param mixed $value
     */
    public function setRawValueWithoutLazyInitialization(object $object, $value): void
    {
        throw \PHPStan\BetterReflection\Reflection\Adapter\Exception\NotImplementedBecauseItTriggersAutoloading::create();
    }
    /** @return never */
    public function isLazy(object $object): bool
    {
        throw \PHPStan\BetterReflection\Reflection\Adapter\Exception\NotImplementedBecauseItTriggersAutoloading::create();
    }
    public function skipLazyInitialization(object $object): void
    {
        throw \PHPStan\BetterReflection\Reflection\Adapter\Exception\NotImplementedBecauseItTriggersAutoloading::create();
    }
    /** @psalm-mutation-free */
    public function hasType(): bool
    {
        return $this->betterReflectionProperty->hasType();
    }
    /**
     * @return ReflectionUnionType|ReflectionNamedType|ReflectionIntersectionType|null
     */
    public function getType(): ?\ReflectionType
    {
        return \PHPStan\BetterReflection\Reflection\Adapter\ReflectionType::fromTypeOrNull($this->betterReflectionProperty->getType());
    }
    public function isPublic(): bool
    {
        return $this->betterReflectionProperty->isPublic();
    }
    public function isPrivate(): bool
    {
        return $this->betterReflectionProperty->isPrivate();
    }
    /** @psalm-mutation-free */
    public function isPrivateSet(): bool
    {
        return $this->betterReflectionProperty->isPrivateSet();
    }
    /** @psalm-mutation-free */
    public function isProtected(): bool
    {
        return $this->betterReflectionProperty->isProtected();
    }
    /** @psalm-mutation-free */
    public function isProtectedSet(): bool
    {
        return $this->betterReflectionProperty->isProtectedSet();
    }
    /** @psalm-mutation-free */
    public function isStatic(): bool
    {
        return $this->betterReflectionProperty->isStatic();
    }
    /** @psalm-mutation-free */
    public function isFinal(): bool
    {
        return $this->betterReflectionProperty->isFinal();
    }
    /** @psalm-mutation-free */
    public function isAbstract(): bool
    {
        return $this->betterReflectionProperty->isAbstract();
    }
    /** @psalm-mutation-free */
    public function isDefault(): bool
    {
        return $this->betterReflectionProperty->isDefault();
    }
    /** @psalm-mutation-free */
    public function isDynamic(): bool
    {
        return $this->betterReflectionProperty->isDynamic();
    }
    /**
     * @return int-mask-of<self::IS_*>
     *
     * @psalm-mutation-free
     */
    public function getModifiers(): int
    {
        return $this->betterReflectionProperty->getModifiers();
    }
    public function getDeclaringClass(): \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass
    {
        return new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass($this->betterReflectionProperty->getImplementingClass());
    }
    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function getDocComment()
    {
        return $this->betterReflectionProperty->getDocComment() ?? \false;
    }
    /**
     * {@inheritDoc}
     * @codeCoverageIgnore
     * @infection-ignore-all
     */
    public function setAccessible($accessible): void
    {
    }
    public function hasDefaultValue(): bool
    {
        return $this->betterReflectionProperty->hasDefaultValue();
    }
    /**
     * @return mixed
     */
    #[ReturnTypeWillChange]
    public function getDefaultValue()
    {
        return $this->betterReflectionProperty->getDefaultValue();
    }
    public function getDefaultValueExpression(): Expr
    {
        return $this->betterReflectionProperty->getDefaultValueExpression();
    }
    /**
     * {@inheritDoc}
     */
    #[ReturnTypeWillChange]
    public function isInitialized($object = null)
    {
        try {
            return $this->betterReflectionProperty->isInitialized($object);
        } catch (Throwable $e) {
            throw new CoreReflectionException($e->getMessage(), 0, $e);
        }
    }
    public function isPromoted(): bool
    {
        return $this->betterReflectionProperty->isPromoted();
    }
    /**
     * @param class-string|null $name
     *
     * @return list<ReflectionAttribute|FakeReflectionAttribute>
     */
    public function getAttributes(?string $name = null, int $flags = 0): array
    {
        if ($flags !== 0 && $flags !== \PHPStan\BetterReflection\Reflection\Adapter\ReflectionAttribute::IS_INSTANCEOF) {
            throw new ValueError('Argument #2 ($flags) must be a valid attribute filter flag');
        }
        if ($name !== null && $flags !== 0) {
            $attributes = $this->betterReflectionProperty->getAttributesByInstance($name);
        } elseif ($name !== null) {
            $attributes = $this->betterReflectionProperty->getAttributesByName($name);
        } else {
            $attributes = $this->betterReflectionProperty->getAttributes();
        }
        return array_map(static fn(BetterReflectionAttribute $betterReflectionAttribute) => \PHPStan\BetterReflection\Reflection\Adapter\ReflectionAttributeFactory::create($betterReflectionAttribute), $attributes);
    }
    public function isReadOnly(): bool
    {
        return $this->betterReflectionProperty->isReadOnly();
    }
    /** @psalm-mutation-free */
    public function isVirtual(): bool
    {
        return $this->betterReflectionProperty->isVirtual();
    }
    public function hasHooks(): bool
    {
        return $this->betterReflectionProperty->hasHooks();
    }
    public function hasHook(PropertyHookType $type): bool
    {
        return $this->betterReflectionProperty->hasHook(BetterReflectionPropertyHookType::fromCoreReflectionPropertyHookType($type));
    }
    public function getHook(PropertyHookType $type): ?\PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod
    {
        $hook = $this->betterReflectionProperty->getHook(BetterReflectionPropertyHookType::fromCoreReflectionPropertyHookType($type));
        if ($hook === null) {
            return null;
        }
        return new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod($hook);
    }
    /** @return array{get?: ReflectionMethod, set?: ReflectionMethod} */
    public function getHooks(): array
    {
        return array_map(static fn(BetterReflectionMethod $betterReflectionMethod): CoreReflectionMethod => new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod($betterReflectionMethod), $this->betterReflectionProperty->getHooks());
    }
    /**
     * @return ReflectionUnionType|ReflectionNamedType|ReflectionIntersectionType|null
     */
    public function getSettableType(): ?\ReflectionType
    {
        $setHook = $this->betterReflectionProperty->getHook(BetterReflectionPropertyHookType::Set);
        if ($setHook !== null) {
            return \PHPStan\BetterReflection\Reflection\Adapter\ReflectionType::fromTypeOrNull($setHook->getParameters()[0]->getType());
        }
        if ($this->isVirtual()) {
            return new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionNamedType('never');
        }
        return $this->getType();
    }
    /* @return never public function getRawValue(object $object): mixed
       {
           throw Exception\NotImplementedBecauseItTriggersAutoloading::create();
       }*/
    /**
     * @param mixed $value
     */
    public function setRawValue(object $object, $value): void
    {
        if ($this->hasHooks()) {
            throw \PHPStan\BetterReflection\Reflection\Adapter\Exception\NotImplementedBecauseItTriggersAutoloading::create();
        }
        $this->setValue($object, $value);
    }
    /**
     * @return mixed
     */
    public function __get(string $name)
    {
        if ($name === 'name') {
            return $this->betterReflectionProperty->getName();
        }
        if ($name === 'class') {
            return $this->betterReflectionProperty->getImplementingClass()->getName();
        }
        throw new OutOfBoundsException(sprintf('Property %s::$%s does not exist.', self::class, $name));
    }
    public function getBetterReflection(): BetterReflectionProperty
    {
        return $this->betterReflectionProperty;
    }
}
