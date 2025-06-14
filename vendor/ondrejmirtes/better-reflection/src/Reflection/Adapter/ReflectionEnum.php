<?php

declare (strict_types=1);
namespace PHPStan\BetterReflection\Reflection\Adapter;

use OutOfBoundsException;
use ReflectionClass as CoreReflectionClass;
use ReflectionEnum as CoreReflectionEnum;
use ReflectionException as CoreReflectionException;
use ReflectionExtension as CoreReflectionExtension;
use ReflectionMethod as CoreReflectionMethod;
use PHPStan\BetterReflection\Reflection\ReflectionAttribute as BetterReflectionAttribute;
use PHPStan\BetterReflection\Reflection\ReflectionClass as BetterReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionClassConstant as BetterReflectionClassConstant;
use PHPStan\BetterReflection\Reflection\ReflectionEnum as BetterReflectionEnum;
use PHPStan\BetterReflection\Reflection\ReflectionEnumCase as BetterReflectionEnumCase;
use PHPStan\BetterReflection\Reflection\ReflectionMethod as BetterReflectionMethod;
use PHPStan\BetterReflection\Reflection\ReflectionProperty as BetterReflectionProperty;
use PHPStan\BetterReflection\Util\FileHelper;
use ValueError;
use function array_combine;
use function array_map;
use function array_values;
use function constant;
use function sprintf;
use function strtolower;
/**
 * @psalm-suppress PropertyNotSetInConstructor
 * @psalm-immutable
 */
final class ReflectionEnum extends CoreReflectionEnum
{
    public function __construct(private BetterReflectionEnum $betterReflectionEnum)
    {
        unset($this->name);
    }
    /** @return non-empty-string */
    public function __toString(): string
    {
        return $this->betterReflectionEnum->__toString();
    }
    public function __get(string $name): mixed
    {
        if ($name === 'name') {
            return $this->betterReflectionEnum->getName();
        }
        throw new OutOfBoundsException(sprintf('Property %s::$%s does not exist.', self::class, $name));
    }
    /** @return class-string */
    public function getName(): string
    {
        return $this->betterReflectionEnum->getName();
    }
    public function getBetterReflection(): BetterReflectionEnum
    {
        return $this->betterReflectionEnum;
    }
    public function isAnonymous(): bool
    {
        return $this->betterReflectionEnum->isAnonymous();
    }
    public function isInternal(): bool
    {
        return $this->betterReflectionEnum->isInternal();
    }
    public function isUserDefined(): bool
    {
        return $this->betterReflectionEnum->isUserDefined();
    }
    public function isInstantiable(): bool
    {
        return $this->betterReflectionEnum->isInstantiable();
    }
    public function isCloneable(): bool
    {
        return $this->betterReflectionEnum->isCloneable();
    }
    /** @return non-empty-string|false */
    public function getFileName(): string|false
    {
        $fileName = $this->betterReflectionEnum->getFileName();
        return $fileName !== null ? FileHelper::normalizeSystemPath($fileName) : \false;
    }
    public function getStartLine(): int
    {
        return $this->betterReflectionEnum->getStartLine();
    }
    public function getEndLine(): int
    {
        return $this->betterReflectionEnum->getEndLine();
    }
    public function getDocComment(): string|false
    {
        return $this->betterReflectionEnum->getDocComment() ?? \false;
    }
    /** @return ReflectionMethod|null */
    public function getConstructor(): ?CoreReflectionMethod
    {
        $constructor = $this->betterReflectionEnum->getConstructor();
        if ($constructor === null) {
            return null;
        }
        return new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod($constructor);
    }
    public function hasMethod(string $name): bool
    {
        if ($name === '') {
            return \false;
        }
        return $this->betterReflectionEnum->hasMethod($name);
    }
    public function getMethod(string $name): \PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod
    {
        $method = $name !== '' ? $this->betterReflectionEnum->getMethod($name) : null;
        if ($method === null) {
            throw new CoreReflectionException(sprintf('Method %s::%s() does not exist', $this->betterReflectionEnum->getName(), $name));
        }
        return new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod($method);
    }
    /**
     * @param int-mask-of<ReflectionMethod::IS_*>|null $filter
     *
     * @return list<ReflectionMethod>
     */
    public function getMethods(int|null $filter = null): array
    {
        /** @psalm-suppress ImpureFunctionCall */
        return array_values(array_map(static fn(BetterReflectionMethod $method): \PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod => new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionMethod($method), $this->betterReflectionEnum->getMethods($filter ?? 0)));
    }
    public function hasProperty(string $name): bool
    {
        if ($name === '') {
            return \false;
        }
        return $this->betterReflectionEnum->hasProperty($name);
    }
    public function getProperty(string $name): \PHPStan\BetterReflection\Reflection\Adapter\ReflectionProperty
    {
        $betterReflectionProperty = $name !== '' ? $this->betterReflectionEnum->getProperty($name) : null;
        if ($betterReflectionProperty === null) {
            throw new CoreReflectionException(sprintf('Property %s::$%s does not exist', $this->betterReflectionEnum->getName(), $name));
        }
        return new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionProperty($betterReflectionProperty);
    }
    /**
     * @param int-mask-of<ReflectionProperty::IS_*>|null $filter
     *
     * @return list<ReflectionProperty>
     */
    public function getProperties(int|null $filter = null): array
    {
        /** @psalm-suppress ImpureFunctionCall */
        return array_values(array_map(static fn(BetterReflectionProperty $property): \PHPStan\BetterReflection\Reflection\Adapter\ReflectionProperty => new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionProperty($property), $this->betterReflectionEnum->getProperties($filter ?? 0)));
    }
    public function hasConstant(string $name): bool
    {
        if ($name === '') {
            return \false;
        }
        return $this->betterReflectionEnum->hasCase($name) || $this->betterReflectionEnum->hasConstant($name);
    }
    /**
     * @param int-mask-of<ReflectionClassConstant::IS_*>|null $filter
     *
     * @return array<non-empty-string, mixed>
     */
    public function getConstants(int|null $filter = null): array
    {
        /** @psalm-suppress ImpureFunctionCall */
        return array_map(fn(BetterReflectionClassConstant|BetterReflectionEnumCase $betterConstantOrEnumCase): mixed => $this->getConstantValue($betterConstantOrEnumCase), $this->filterBetterReflectionClassConstants($filter));
    }
    public function getConstant(string $name): mixed
    {
        if ($name === '') {
            return \false;
        }
        $enumCase = $this->betterReflectionEnum->getCase($name);
        if ($enumCase !== null) {
            return $this->getConstantValue($enumCase);
        }
        $betterReflectionConstant = $this->betterReflectionEnum->getConstant($name);
        if ($betterReflectionConstant === null) {
            return \false;
        }
        return $betterReflectionConstant->getValue();
    }
    private function getConstantValue(BetterReflectionClassConstant|BetterReflectionEnumCase $betterConstantOrEnumCase): mixed
    {
        if ($betterConstantOrEnumCase instanceof BetterReflectionEnumCase) {
            throw new \PHPStan\BetterReflection\Reflection\Adapter\Exception\NotImplemented('Not implemented');
        }
        return $betterConstantOrEnumCase->getValue();
    }
    public function getReflectionConstant(string $name): \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClassConstant|false
    {
        if ($name === '') {
            return \false;
        }
        // @infection-ignore-all Coalesce: There's no difference
        $betterReflectionConstantOrEnumCase = $this->betterReflectionEnum->getCase($name) ?? $this->betterReflectionEnum->getConstant($name);
        if ($betterReflectionConstantOrEnumCase === null) {
            return \false;
        }
        return new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClassConstant($betterReflectionConstantOrEnumCase);
    }
    /**
     * @param int-mask-of<ReflectionClassConstant::IS_*>|null $filter
     *
     * @return list<ReflectionClassConstant>
     */
    public function getReflectionConstants(int|null $filter = null): array
    {
        return array_values(array_map(static fn(BetterReflectionClassConstant|BetterReflectionEnumCase $betterConstantOrEnum): \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClassConstant => new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClassConstant($betterConstantOrEnum), $this->filterBetterReflectionClassConstants($filter)));
    }
    /**
     * @param int-mask-of<ReflectionClassConstant::IS_*>|null $filter
     *
     * @return array<non-empty-string, BetterReflectionClassConstant|BetterReflectionEnumCase>
     */
    private function filterBetterReflectionClassConstants(int|null $filter): array
    {
        $reflectionConstants = $this->betterReflectionEnum->getConstants($filter ?? 0);
        if ($filter === null || $filter & \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClassConstant::IS_PUBLIC_COMPATIBILITY) {
            $reflectionConstants += $this->betterReflectionEnum->getCases();
        }
        return $reflectionConstants;
    }
    /** @return array<class-string, ReflectionClass> */
    public function getInterfaces(): array
    {
        /** @psalm-suppress ImpureFunctionCall */
        return array_map(static fn(BetterReflectionClass $interface): \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass => new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass($interface), $this->betterReflectionEnum->getInterfaces());
    }
    /** @return list<class-string> */
    public function getInterfaceNames(): array
    {
        return $this->betterReflectionEnum->getInterfaceNames();
    }
    public function isInterface(): bool
    {
        return $this->betterReflectionEnum->isInterface();
    }
    /** @return array<trait-string, ReflectionClass> */
    public function getTraits(): array
    {
        $traits = $this->betterReflectionEnum->getTraits();
        /** @var list<trait-string> $traitNames */
        $traitNames = array_map(static fn(BetterReflectionClass $trait): string => $trait->getName(), $traits);
        /** @psalm-suppress ImpureFunctionCall */
        return array_combine($traitNames, array_map(static fn(BetterReflectionClass $trait): \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass => new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass($trait), $traits));
    }
    /** @return list<trait-string> */
    public function getTraitNames(): array
    {
        return $this->betterReflectionEnum->getTraitNames();
    }
    /** @return array<non-empty-string, non-empty-string> */
    public function getTraitAliases(): array
    {
        return $this->betterReflectionEnum->getTraitAliases();
    }
    public function isTrait(): bool
    {
        return $this->betterReflectionEnum->isTrait();
    }
    public function isAbstract(): bool
    {
        return $this->betterReflectionEnum->isAbstract();
    }
    public function isFinal(): bool
    {
        return $this->betterReflectionEnum->isFinal();
    }
    public function isReadOnly(): bool
    {
        return $this->betterReflectionEnum->isReadOnly();
    }
    public function getModifiers(): int
    {
        return $this->betterReflectionEnum->getModifiers();
    }
    public function isInstance(object $object): bool
    {
        return $this->betterReflectionEnum->isInstance($object);
    }
    /** @return never */
    public function newInstance(mixed ...$args): object
    {
        throw \PHPStan\BetterReflection\Reflection\Adapter\Exception\NotImplementedBecauseItTriggersAutoloading::create();
    }
    /** @return never */
    public function newInstanceWithoutConstructor(): object
    {
        throw \PHPStan\BetterReflection\Reflection\Adapter\Exception\NotImplementedBecauseItTriggersAutoloading::create();
    }
    /** @return never */
    public function newInstanceArgs(array|null $args = null): object
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
    /** @return never */
    public function getLazyInitializer(object $object): callable|null
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
    public function getParentClass(): \PHPStan\BetterReflection\Reflection\Adapter\ReflectionClass|false
    {
        return \false;
    }
    public function isSubclassOf(CoreReflectionClass|string $class): bool
    {
        $realParentClassNames = $this->betterReflectionEnum->getParentClassNames();
        $parentClassNames = array_combine(array_map(static fn(string $parentClassName): string => strtolower($parentClassName), $realParentClassNames), $realParentClassNames);
        $className = $class instanceof CoreReflectionClass ? $class->getName() : $class;
        $lowercasedClassName = strtolower($className);
        $realParentClassName = $parentClassNames[$lowercasedClassName] ?? $className;
        if ($this->betterReflectionEnum->isSubclassOf($realParentClassName)) {
            return \true;
        }
        return $this->implementsInterface($className);
    }
    /**
     * @return array<string, mixed>
     *
     * @psalm-suppress LessSpecificImplementedReturnType
     */
    public function getStaticProperties(): array
    {
        return $this->betterReflectionEnum->getStaticProperties();
    }
    public function getStaticPropertyValue(string $name, mixed $default = null): mixed
    {
        throw new CoreReflectionException(sprintf('Property %s::$%s does not exist', $this->betterReflectionEnum->getName(), $name));
    }
    public function setStaticPropertyValue(string $name, mixed $value): void
    {
        throw new CoreReflectionException(sprintf('Class %s does not have a property named %s', $this->betterReflectionEnum->getName(), $name));
    }
    /** @return array<non-empty-string, mixed> */
    public function getDefaultProperties(): array
    {
        return $this->betterReflectionEnum->getDefaultProperties();
    }
    public function isIterateable(): bool
    {
        return $this->betterReflectionEnum->isIterateable();
    }
    public function isIterable(): bool
    {
        return $this->isIterateable();
    }
    public function implementsInterface(CoreReflectionClass|string $interface): bool
    {
        $realInterfaceNames = $this->betterReflectionEnum->getInterfaceNames();
        $interfaceNames = array_combine(array_map(static fn(string $interfaceName): string => strtolower($interfaceName), $realInterfaceNames), $realInterfaceNames);
        $interfaceName = $interface instanceof CoreReflectionClass ? $interface->getName() : $interface;
        $lowercasedInterfaceName = strtolower($interfaceName);
        $realInterfaceName = $interfaceNames[$lowercasedInterfaceName] ?? $interfaceName;
        return $this->betterReflectionEnum->implementsInterface($realInterfaceName);
    }
    public function getExtension(): ?CoreReflectionExtension
    {
        throw new \PHPStan\BetterReflection\Reflection\Adapter\Exception\NotImplemented('Not implemented');
    }
    /** @return non-empty-string|false */
    public function getExtensionName(): string|false
    {
        return $this->betterReflectionEnum->getExtensionName() ?? \false;
    }
    public function inNamespace(): bool
    {
        return $this->betterReflectionEnum->inNamespace();
    }
    public function getNamespaceName(): string
    {
        return $this->betterReflectionEnum->getNamespaceName() ?? '';
    }
    public function getShortName(): string
    {
        return $this->betterReflectionEnum->getShortName();
    }
    /**
     * @param class-string|null $name
     *
     * @return list<ReflectionAttribute|FakeReflectionAttribute>
     */
    public function getAttributes(string|null $name = null, int $flags = 0): array
    {
        if ($flags !== 0 && $flags !== \PHPStan\BetterReflection\Reflection\Adapter\ReflectionAttribute::IS_INSTANCEOF) {
            throw new ValueError('Argument #2 ($flags) must be a valid attribute filter flag');
        }
        if ($name !== null && $flags !== 0) {
            $attributes = $this->betterReflectionEnum->getAttributesByInstance($name);
        } elseif ($name !== null) {
            $attributes = $this->betterReflectionEnum->getAttributesByName($name);
        } else {
            $attributes = $this->betterReflectionEnum->getAttributes();
        }
        /** @psalm-suppress ImpureFunctionCall */
        return array_map(static fn(BetterReflectionAttribute $betterReflectionAttribute): \PHPStan\BetterReflection\Reflection\Adapter\ReflectionAttribute|\PHPStan\BetterReflection\Reflection\Adapter\FakeReflectionAttribute => \PHPStan\BetterReflection\Reflection\Adapter\ReflectionAttributeFactory::create($betterReflectionAttribute), $attributes);
    }
    public function isEnum(): bool
    {
        return $this->betterReflectionEnum->isEnum();
    }
    public function hasCase(string $name): bool
    {
        if ($name === '') {
            return \false;
        }
        return $this->betterReflectionEnum->hasCase($name);
    }
    public function getCase(string $name): \PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumUnitCase|\PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumBackedCase
    {
        $case = $name !== '' ? $this->betterReflectionEnum->getCase($name) : null;
        if ($case === null) {
            throw new CoreReflectionException(sprintf('Case %s::%s does not exist', $this->betterReflectionEnum->getName(), $name));
        }
        if ($this->betterReflectionEnum->isBacked()) {
            return new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumBackedCase($case);
        }
        return new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumUnitCase($case);
    }
    /** @return list<ReflectionEnumUnitCase|ReflectionEnumBackedCase> */
    public function getCases(): array
    {
        /** @psalm-suppress ImpureFunctionCall */
        return array_map(function (BetterReflectionEnumCase $case): \PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumUnitCase|\PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumBackedCase {
            if ($this->betterReflectionEnum->isBacked()) {
                return new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumBackedCase($case);
            }
            return new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionEnumUnitCase($case);
        }, array_values($this->betterReflectionEnum->getCases()));
    }
    public function isBacked(): bool
    {
        return $this->betterReflectionEnum->isBacked();
    }
    public function getBackingType(): \PHPStan\BetterReflection\Reflection\Adapter\ReflectionNamedType|null
    {
        if ($this->betterReflectionEnum->isBacked()) {
            return new \PHPStan\BetterReflection\Reflection\Adapter\ReflectionNamedType($this->betterReflectionEnum->getBackingType(), \false);
        }
        return null;
    }
}
