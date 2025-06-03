<?php

declare (strict_types=1);
namespace PHPStan\BetterReflection\Reflection\Adapter;

use OutOfBoundsException;
use ReflectionClass as CoreReflectionClass;
use ReflectionException as CoreReflectionException;
use ReflectionExtension as CoreReflectionExtension;
use ReflectionObject as CoreReflectionObject;
use ReturnTypeWillChange;
use PHPStan\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use PHPStan\BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionClassConstant as BetterReflectionClassConstant;
use PHPStan\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use PHPStan\BetterReflection\Reflection\ReflectionObject as BetterReflectionObject;
use PHPStan\BetterReflection\Reflection\ReflectionProperty as BetterReflectionProperty;
use PHPStan\BetterReflection\Util\FileHelper;
use ValueError;
use function array_combine;
use function array_map;
use function array_values;
use function func_num_args;
use function sprintf;
use function strtolower;
/** @psalm-suppress PropertyNotSetInConstructor */
final class ReflectionObject extends CoreReflectionObject
{
    private BetterReflectionObject $betterReflectionObject;
    public function __construct(BetterReflectionObject $betterReflectionObject)
    {
        $this->betterReflectionObject = $betterReflectionObject;
        unset($this->name);
    }
    /** @return non-empty-string */
    public function __toString(): string
    {
        return $this->betterReflectionObject->__toString();
    }
    public function getName(): string
    {
        return $this->betterReflectionObject->getName();
    }
    public function getBetterReflection(): BetterReflectionObject
    {
        return $this->betterReflectionObject;
    }
    public function isInternal(): bool
    {
        return $this->betterReflectionObject->isInternal();
    }
    public function isUserDefined(): bool
    {
        return $this->betterReflectionObject->isUserDefined();
    }
    public function isInstantiable(): bool
    {
        return $this->betterReflectionObject->isInstantiable();
    }
    public function isCloneable(): bool
    {
        return $this->betterReflectionObject->isCloneable();
    }
    /**
     * {@inheritDoc}
     * @return non-empty-string|false
     */
    #[\ReturnTypeWillChange]
    public function getFileName()
    {
        $fileName = $this->betterReflectionObject->getFileName();
        return $fileName !== null ? FileHelper::normalizeSystemPath($fileName) : \false;
    }
    /**
     * {@inheritDoc}
     * @psalm-mutation-free
     */
    #[\ReturnTypeWillChange]
    public function getStartLine()
    {
        return $this->betterReflectionObject->getStartLine();
    }
    /**
     * {@inheritDoc}
     * @psalm-mutation-free
     */
    #[\ReturnTypeWillChange]
    public function getEndLine()
    {
        return $this->betterReflectionObject->getEndLine();
    }
    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function getDocComment()
    {
        return $this->betterReflectionObject->getDocComment() ?? \false;
    }
    public function getConstructor(): ?\PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod
    {
        $constructor = $this->betterReflectionObject->getConstructor();
        if ($constructor === null) {
            return null;
        }
        return new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod($constructor);
    }
    /**
     * {@inheritDoc}
     */
    public function hasMethod($name): bool
    {
        if ($name === '') {
            return \false;
        }
        return $this->betterReflectionObject->hasMethod($this->getMethodRealName($name));
    }
    /**
     * {@inheritDoc}
     */
    public function getMethod($name): \PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod
    {
        $method = $name !== '' ? $this->betterReflectionObject->getMethod($this->getMethodRealName($name)) : null;
        if ($method === null) {
            throw new CoreReflectionException(sprintf('Method %s::%s() does not exist', $this->betterReflectionObject->getName(), $name));
        }
        return new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod($method);
    }
    /**
     * @param non-empty-string $name
     *
     * @return non-empty-string
     */
    private function getMethodRealName(string $name): string
    {
        $realMethodNames = array_map(static fn(BetterReflectionMethod $method): string => $method->getName(), $this->betterReflectionObject->getMethods());
        $methodNames = array_combine(array_map(static fn(string $methodName): string => strtolower($methodName), $realMethodNames), $realMethodNames);
        $lowercasedName = strtolower($name);
        return $methodNames[$lowercasedName] ?? $name;
    }
    /**
     * @param int-mask-of<ReflectionMethod::IS_*>|null $filter
     * @return list<ReflectionMethod>
     */
    public function getMethods($filter = null): array
    {
        return array_values(array_map(static fn(BetterReflectionMethod $method): \PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod => new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod($method), $this->betterReflectionObject->getMethods($filter ?? 0)));
    }
    /**
     * {@inheritDoc}
     */
    public function hasProperty($name): bool
    {
        if ($name === '') {
            return \false;
        }
        return $this->betterReflectionObject->hasProperty($name);
    }
    /**
     * @param string $name
     * @return ReflectionProperty
     */
    public function getProperty($name): \ReflectionProperty
    {
        $property = $name !== '' ? $this->betterReflectionObject->getProperty($name) : null;
        if ($property === null) {
            throw new CoreReflectionException(sprintf('Property %s::$%s does not exist', $this->betterReflectionObject->getName(), $name));
        }
        return new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionProperty($property);
    }
    /**
     * @param int-mask-of<ReflectionProperty::IS_*>|null $filter
     * @return list<ReflectionProperty>
     */
    public function getProperties($filter = null): array
    {
        return array_values(array_map(static fn(BetterReflectionProperty $property): \PHPStan\BetterReflection\Reflection\Adapter\ReflectionProperty => new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionProperty($property), $this->betterReflectionObject->getProperties($filter ?? 0)));
    }
    /**
     * {@inheritDoc}
     */
    public function hasConstant($name): bool
    {
        if ($name === '') {
            return \false;
        }
        return $this->betterReflectionObject->hasConstant($name);
    }
    /**
     * @param int-mask-of<ReflectionClassConstant::IS_*>|null $filter
     *
     * @return array<non-empty-string, mixed>
     */
    public function getConstants(?int $filter = null): array
    {
        return array_map(static fn(BetterReflectionClassConstant $betterConstant) => $betterConstant->getValue(), $this->betterReflectionObject->getConstants($filter ?? 0));
    }
    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function getConstant($name)
    {
        if ($name === '') {
            return \false;
        }
        $betterReflectionConstant = $this->betterReflectionObject->getConstant($name);
        if ($betterReflectionConstant === null) {
            return \false;
        }
        return $betterReflectionConstant->getValue();
    }
    /**
     * @param string $name
     * @return ReflectionClassConstant|false
     */
    #[\ReturnTypeWillChange]
    public function getReflectionConstant($name)
    {
        if ($name === '') {
            return \false;
        }
        $betterReflectionConstant = $this->betterReflectionObject->getConstant($name);
        if ($betterReflectionConstant === null) {
            return \false;
        }
        return new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClassConstant($betterReflectionConstant);
    }
    /**
     * @param int-mask-of<ReflectionClassConstant::IS_*>|null $filter
     *
     * @return list<ReflectionClassConstant>
     */
    public function getReflectionConstants(?int $filter = null): array
    {
        return array_values(array_map(static fn(BetterReflectionClassConstant $betterConstant): \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClassConstant => new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClassConstant($betterConstant), $this->betterReflectionObject->getConstants($filter ?? 0)));
    }
    /** @return array<class-string, ReflectionClass> */
    public function getInterfaces(): array
    {
        return array_map(static fn(BetterReflectionClass $interface): \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass => new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass($interface), $this->betterReflectionObject->getInterfaces());
    }
    /** @return list<class-string> */
    public function getInterfaceNames(): array
    {
        return $this->betterReflectionObject->getInterfaceNames();
    }
    public function isInterface(): bool
    {
        return $this->betterReflectionObject->isInterface();
    }
    /** @return array<trait-string, ReflectionClass> */
    public function getTraits(): array
    {
        $traits = $this->betterReflectionObject->getTraits();
        /** @var list<trait-string> $traitNames */
        $traitNames = array_map(static fn(BetterReflectionClass $trait): string => $trait->getName(), $traits);
        return array_combine($traitNames, array_map(static fn(BetterReflectionClass $trait): \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass => new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass($trait), $traits));
    }
    /** @return list<trait-string> */
    public function getTraitNames(): array
    {
        return $this->betterReflectionObject->getTraitNames();
    }
    /** @return array<non-empty-string, non-empty-string> */
    public function getTraitAliases(): array
    {
        return $this->betterReflectionObject->getTraitAliases();
    }
    public function isTrait(): bool
    {
        return $this->betterReflectionObject->isTrait();
    }
    public function isAbstract(): bool
    {
        return $this->betterReflectionObject->isAbstract();
    }
    public function isFinal(): bool
    {
        return $this->betterReflectionObject->isFinal();
    }
    public function isReadOnly(): bool
    {
        return $this->betterReflectionObject->isReadOnly();
    }
    public function getModifiers(): int
    {
        return $this->betterReflectionObject->getModifiers();
    }
    /**
     * {@inheritDoc}
     */
    public function isInstance($object): bool
    {
        return $this->betterReflectionObject->isInstance($object);
    }
    /**
     * @param mixed $arg
     * @param mixed ...$args
     *
     * @return object
     */
    #[\ReturnTypeWillChange]
    public function newInstance($arg = null, ...$args)
    {
        throw \PHPStan\BetterReflection\Reflection\Adapter\Exception\NotImplementedBecauseItTriggersAutoloading::create();
    }
    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function newInstanceWithoutConstructor()
    {
        throw \PHPStan\BetterReflection\Reflection\Adapter\Exception\NotImplementedBecauseItTriggersAutoloading::create();
    }
    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function newInstanceArgs(?array $args = null)
    {
        throw \PHPStan\BetterReflection\Reflection\Adapter\Exception\NotImplementedBecauseItTriggersAutoloading::create();
    }
    /**
     * @param int-mask-of<ReflectionClass::SKIP_*> $options
     *
     * @return never
     */
    public function newLazyGhost(callable $initializer, int $options = 0): object
    {
        throw \PHPStan\BetterReflection\Reflection\Adapter\Exception\NotImplementedBecauseItTriggersAutoloading::create();
    }
    /**
     * @param int-mask-of<ReflectionClass::SKIP_*> $options
     *
     * @return never
     */
    public function newLazyProxy(callable $factory, int $options = 0): object
    {
        throw \PHPStan\BetterReflection\Reflection\Adapter\Exception\NotImplementedBecauseItTriggersAutoloading::create();
    }
    /** @return never */
    public function markLazyObjectAsInitialized(object $object): object
    {
        throw \PHPStan\BetterReflection\Reflection\Adapter\Exception\NotImplementedBecauseItTriggersAutoloading::create();
    }
    public function getLazyInitializer(object $object): ?callable
    {
        throw \PHPStan\BetterReflection\Reflection\Adapter\Exception\NotImplementedBecauseItTriggersAutoloading::create();
    }
    /** @return never */
    public function initializeLazyObject(object $object): object
    {
        throw \PHPStan\BetterReflection\Reflection\Adapter\Exception\NotImplementedBecauseItTriggersAutoloading::create();
    }
    /** @return never */
    public function isUninitializedLazyObject(object $object): bool
    {
        throw \PHPStan\BetterReflection\Reflection\Adapter\Exception\NotImplementedBecauseItTriggersAutoloading::create();
    }
    /** @param int-mask-of<ReflectionClass::SKIP_*> $options */
    public function resetAsLazyGhost(object $object, callable $initializer, int $options = 0): void
    {
        throw \PHPStan\BetterReflection\Reflection\Adapter\Exception\NotImplementedBecauseItTriggersAutoloading::create();
    }
    /** @param int-mask-of<ReflectionClass::SKIP_*> $options */
    public function resetAsLazyProxy(object $object, callable $factory, int $options = 0): void
    {
        throw \PHPStan\BetterReflection\Reflection\Adapter\Exception\NotImplementedBecauseItTriggersAutoloading::create();
    }
    /**
     * {@inheritDoc}
     * @psalm-mutation-free
     * @return ReflectionClass|false
     */
    #[\ReturnTypeWillChange]
    public function getParentClass()
    {
        $parentClass = $this->betterReflectionObject->getParentClass();
        if ($parentClass === null) {
            return \false;
        }
        return new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass($parentClass);
    }
    /**
     * {@inheritDoc}
     */
    public function isSubclassOf($class): bool
    {
        $realParentClassNames = $this->betterReflectionObject->getParentClassNames();
        $parentClassNames = array_combine(array_map(static fn(string $parentClassName): string => strtolower($parentClassName), $realParentClassNames), $realParentClassNames);
        $className = $class instanceof CoreReflectionClass ? $class->getName() : $class;
        $lowercasedClassName = strtolower($className);
        $realParentClassName = $parentClassNames[$lowercasedClassName] ?? $className;
        return $this->betterReflectionObject->isSubclassOf($realParentClassName);
    }
    /**
     * @return array<string, mixed>
     *
     * @psalm-suppress LessSpecificImplementedReturnType
     */
    public function getStaticProperties(): array
    {
        return $this->betterReflectionObject->getStaticProperties();
    }
    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function getStaticPropertyValue($name, $default = null)
    {
        $betterReflectionProperty = $name !== '' ? $this->betterReflectionObject->getProperty($name) : null;
        if ($betterReflectionProperty === null) {
            if (func_num_args() === 2) {
                return $default;
            }
            throw new CoreReflectionException(sprintf('Property %s::$%s does not exist', $this->betterReflectionObject->getName(), $name));
        }
        $property = new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionProperty($betterReflectionProperty);
        if (!$property->isStatic()) {
            throw new CoreReflectionException(sprintf('Property %s::$%s does not exist', $this->betterReflectionObject->getName(), $name));
        }
        return $property->getValue();
    }
    /**
     * {@inheritDoc}
     */
    public function setStaticPropertyValue($name, $value): void
    {
        $betterReflectionProperty = $name !== '' ? $this->betterReflectionObject->getProperty($name) : null;
        if ($betterReflectionProperty === null) {
            throw new CoreReflectionException(sprintf('Class %s does not have a property named %s', $this->betterReflectionObject->getName(), $name));
        }
        $property = new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionProperty($betterReflectionProperty);
        if (!$property->isStatic()) {
            throw new CoreReflectionException(sprintf('Class %s does not have a property named %s', $this->betterReflectionObject->getName(), $name));
        }
        $property->setValue($value);
    }
    /** @return array<string, scalar|array<scalar>|null> */
    public function getDefaultProperties(): array
    {
        return $this->betterReflectionObject->getDefaultProperties();
    }
    public function isIterateable(): bool
    {
        return $this->betterReflectionObject->isIterateable();
    }
    public function isIterable(): bool
    {
        return $this->isIterateable();
    }
    /**
     * @param \ReflectionClass|string $interface
     */
    public function implementsInterface($interface): bool
    {
        $realInterfaceNames = $this->betterReflectionObject->getInterfaceNames();
        $interfaceNames = array_combine(array_map(static fn(string $interfaceName): string => strtolower($interfaceName), $realInterfaceNames), $realInterfaceNames);
        $interfaceName = $interface instanceof CoreReflectionClass ? $interface->getName() : $interface;
        $lowercasedInterfaceName = strtolower($interfaceName);
        $realInterfaceName = $interfaceNames[$lowercasedInterfaceName] ?? $interfaceName;
        return $this->betterReflectionObject->implementsInterface($realInterfaceName);
    }
    public function getExtension(): ?CoreReflectionExtension
    {
        throw new \PHPStan\BetterReflection\Reflection\Adapter\Exception\NotImplemented('Not implemented');
    }
    /**
     * {@inheritDoc}
     */
    #[\ReturnTypeWillChange]
    public function getExtensionName()
    {
        return $this->betterReflectionObject->getExtensionName() ?? \false;
    }
    public function inNamespace(): bool
    {
        return $this->betterReflectionObject->inNamespace();
    }
    public function getNamespaceName(): string
    {
        return $this->betterReflectionObject->getNamespaceName() ?? '';
    }
    public function getShortName(): string
    {
        return $this->betterReflectionObject->getShortName();
    }
    public function isAnonymous(): bool
    {
        return $this->betterReflectionObject->isAnonymous();
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
            $attributes = $this->betterReflectionObject->getAttributesByInstance($name);
        } elseif ($name !== null) {
            $attributes = $this->betterReflectionObject->getAttributesByName($name);
        } else {
            $attributes = $this->betterReflectionObject->getAttributes();
        }
        return array_map(static fn(BetterReflectionAttribute $betterReflectionAttribute) => \PHPStan\BetterReflection\Reflection\Adapter\ReflectionAttributeFactory::create($betterReflectionAttribute), $attributes);
    }
    public function isEnum(): bool
    {
        return $this->betterReflectionObject->isEnum();
    }
    /**
     * @return mixed
     */
    public function __get(string $name)
    {
        if ($name === 'name') {
            return $this->betterReflectionObject->getName();
        }
        throw new OutOfBoundsException(sprintf('Property %s::$%s does not exist.', self::class, $name));
    }
}
