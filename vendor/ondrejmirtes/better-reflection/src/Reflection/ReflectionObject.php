<?php

declare (strict_types=1);
namespace PHPStan\BetterReflection\Reflection;

use InvalidArgumentException;
use PhpParser\Builder\Property as PropertyNodeBuilder;
use PhpParser\Node\Stmt\Property as PropertyNode;
use ReflectionException;
use ReflectionObject as CoreReflectionObject;
use ReflectionProperty as CoreReflectionProperty;
use PHPStan\BetterReflection\BetterReflection;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionProperty as ReflectionPropertyAdapter;
use PHPStan\BetterReflection\Reflector\DefaultReflector;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Located\LocatedSource;
use PHPStan\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\AnonymousClassObjectSourceLocator;
use function array_filter;
use function array_map;
use function array_merge;
use function preg_match;
/** @psalm-immutable */
class ReflectionObject extends \PHPStan\BetterReflection\Reflection\ReflectionClass
{
    private Reflector $reflector;
    private \PHPStan\BetterReflection\Reflection\ReflectionClass $reflectionClass;
    private object $object;
    protected function __construct(Reflector $reflector, \PHPStan\BetterReflection\Reflection\ReflectionClass $reflectionClass, object $object)
    {
        $this->reflector = $reflector;
        $this->reflectionClass = $reflectionClass;
        $this->object = $object;
    }
    /**
     * Pass an instance of an object to this method to reflect it
     *
     * @throws ReflectionException
     * @throws IdentifierNotFound
     */
    public static function createFromInstance(object $instance): \PHPStan\BetterReflection\Reflection\ReflectionClass
    {
        $className = get_class($instance);
        $betterReflection = new BetterReflection();
        if (preg_match(\PHPStan\BetterReflection\Reflection\ReflectionClass::ANONYMOUS_CLASS_NAME_PREFIX_REGEXP, $className) === 1) {
            $reflector = new DefaultReflector(new AggregateSourceLocator([$betterReflection->sourceLocator(), new AnonymousClassObjectSourceLocator($instance, $betterReflection->phpParser())]));
        } else {
            $reflector = $betterReflection->reflector();
        }
        return new self($reflector, $reflector->reflectClass($className), $instance);
    }
    /**
     * Reflect on runtime properties for the current instance
     *
     * @see ReflectionClass::getProperties() for the usage of $filter
     *
     * @param int-mask-of<ReflectionPropertyAdapter::IS_*> $filter
     *
     * @return array<non-empty-string, ReflectionProperty>
     */
    private function getRuntimeProperties(int $filter = 0): array
    {
        if (!$this->reflectionClass->isInstance($this->object)) {
            throw new InvalidArgumentException('Cannot reflect runtime properties of a separate class');
        }
        if ($filter !== 0 && !($filter & CoreReflectionProperty::IS_PUBLIC)) {
            return [];
        }
        // Ensure we have already cached existing properties so we can add to them
        $this->reflectionClass->getProperties();
        // Only known current way is to use internal ReflectionObject to get
        // the runtime-declared properties  :/
        $reflectionProperties = (new CoreReflectionObject($this->object))->getProperties();
        $runtimeProperties = [];
        foreach ($reflectionProperties as $property) {
            $propertyName = $property->getName();
            if ($this->reflectionClass->hasProperty($propertyName)) {
                continue;
            }
            $propertyNode = $this->createPropertyNodeFromRuntimePropertyReflection($property, $this->object);
            $runtimeProperties[$propertyName] = \PHPStan\BetterReflection\Reflection\ReflectionProperty::createFromNode($this->reflector, $propertyNode, $propertyNode->props[0], $this, $this, \false, \false);
        }
        return $runtimeProperties;
    }
    /**
     * Create an AST PropertyNode given a reflection
     *
     * Note that we don't copy across DocBlock, protected, private or static
     * because runtime properties can't have these attributes.
     */
    private function createPropertyNodeFromRuntimePropertyReflection(CoreReflectionProperty $property, object $instance): PropertyNode
    {
        $builder = new PropertyNodeBuilder($property->getName());
        $builder->setDefault($property->getValue($instance));
        $builder->makePublic();
        return $builder->getNode();
    }
    public function getShortName(): string
    {
        return $this->reflectionClass->getShortName();
    }
    public function getName(): string
    {
        return $this->reflectionClass->getName();
    }
    public function getNamespaceName(): ?string
    {
        return $this->reflectionClass->getNamespaceName();
    }
    public function inNamespace(): bool
    {
        return $this->reflectionClass->inNamespace();
    }
    /** @return non-empty-string|null */
    public function getExtensionName(): ?string
    {
        return $this->reflectionClass->getExtensionName();
    }
    /**
     * {@inheritDoc}
     */
    public function getMethods(int $filter = 0): array
    {
        return $this->reflectionClass->getMethods($filter);
    }
    /**
     * {@inheritDoc}
     */
    public function getImmediateMethods(int $filter = 0): array
    {
        return $this->reflectionClass->getImmediateMethods($filter);
    }
    /** @param non-empty-string $methodName */
    public function getMethod(string $methodName): ?\PHPStan\BetterReflection\Reflection\ReflectionMethod
    {
        return $this->reflectionClass->getMethod($methodName);
    }
    /** @param non-empty-string $methodName */
    public function hasMethod(string $methodName): bool
    {
        return $this->reflectionClass->hasMethod($methodName);
    }
    /**
     * {@inheritDoc}
     */
    public function getImmediateConstants(int $filter = 0): array
    {
        return $this->reflectionClass->getImmediateConstants($filter);
    }
    /**
     * {@inheritDoc}
     */
    public function getConstants(int $filter = 0): array
    {
        return $this->reflectionClass->getConstants($filter);
    }
    public function hasConstant(string $name): bool
    {
        return $this->reflectionClass->hasConstant($name);
    }
    public function getConstant(string $name): ?\PHPStan\BetterReflection\Reflection\ReflectionClassConstant
    {
        return $this->reflectionClass->getConstant($name);
    }
    public function getConstructor(): ?\PHPStan\BetterReflection\Reflection\ReflectionMethod
    {
        return $this->reflectionClass->getConstructor();
    }
    /**
     * {@inheritDoc}
     */
    public function getProperties(int $filter = 0): array
    {
        return array_merge($this->reflectionClass->getProperties($filter), $this->getRuntimeProperties($filter));
    }
    /**
     * {@inheritDoc}
     */
    public function getImmediateProperties(int $filter = 0): array
    {
        return array_merge($this->reflectionClass->getImmediateProperties($filter), $this->getRuntimeProperties($filter));
    }
    public function getProperty(string $name): ?\PHPStan\BetterReflection\Reflection\ReflectionProperty
    {
        $runtimeProperties = $this->getRuntimeProperties();
        if (isset($runtimeProperties[$name])) {
            return $runtimeProperties[$name];
        }
        return $this->reflectionClass->getProperty($name);
    }
    public function hasProperty(string $name): bool
    {
        $runtimeProperties = $this->getRuntimeProperties();
        return isset($runtimeProperties[$name]) || $this->reflectionClass->hasProperty($name);
    }
    /**
     * {@inheritDoc}
     */
    public function getDefaultProperties(): array
    {
        return array_map(static fn(\PHPStan\BetterReflection\Reflection\ReflectionProperty $property) => $property->getDefaultValue(), array_filter($this->getProperties(), static fn(\PHPStan\BetterReflection\Reflection\ReflectionProperty $property): bool => $property->isDefault()));
    }
    /** @return non-empty-string|null */
    public function getFileName(): ?string
    {
        return $this->reflectionClass->getFileName();
    }
    public function getLocatedSource(): LocatedSource
    {
        return $this->reflectionClass->getLocatedSource();
    }
    public function getStartLine(): int
    {
        return $this->reflectionClass->getStartLine();
    }
    public function getEndLine(): int
    {
        return $this->reflectionClass->getEndLine();
    }
    public function getStartColumn(): int
    {
        return $this->reflectionClass->getStartColumn();
    }
    public function getEndColumn(): int
    {
        return $this->reflectionClass->getEndColumn();
    }
    public function getParentClass(): ?\PHPStan\BetterReflection\Reflection\ReflectionClass
    {
        return $this->reflectionClass->getParentClass();
    }
    /** @return class-string|null */
    public function getParentClassName(): ?string
    {
        return $this->reflectionClass->getParentClassName();
    }
    /**
     * {@inheritDoc}
     */
    public function getParentClassNames(): array
    {
        return $this->reflectionClass->getParentClassNames();
    }
    /** @return non-empty-string|null */
    public function getDocComment(): ?string
    {
        return $this->reflectionClass->getDocComment();
    }
    public function isAnonymous(): bool
    {
        return $this->reflectionClass->isAnonymous();
    }
    public function isInternal(): bool
    {
        return $this->reflectionClass->isInternal();
    }
    public function isUserDefined(): bool
    {
        return $this->reflectionClass->isUserDefined();
    }
    public function isDeprecated(): bool
    {
        return $this->reflectionClass->isDeprecated();
    }
    public function isAbstract(): bool
    {
        return $this->reflectionClass->isAbstract();
    }
    public function isFinal(): bool
    {
        return $this->reflectionClass->isFinal();
    }
    public function isReadOnly(): bool
    {
        return $this->reflectionClass->isReadOnly();
    }
    public function getModifiers(): int
    {
        return $this->reflectionClass->getModifiers();
    }
    public function isTrait(): bool
    {
        return $this->reflectionClass->isTrait();
    }
    public function isInterface(): bool
    {
        return $this->reflectionClass->isInterface();
    }
    /**
     * {@inheritDoc}
     */
    public function getTraitClassNames(): array
    {
        return $this->reflectionClass->getTraitClassNames();
    }
    /**
     * {@inheritDoc}
     */
    public function getTraits(): array
    {
        return $this->reflectionClass->getTraits();
    }
    /**
     * {@inheritDoc}
     */
    public function getTraitNames(): array
    {
        return $this->reflectionClass->getTraitNames();
    }
    /**
     * {@inheritDoc}
     */
    public function getTraitAliases(): array
    {
        return $this->reflectionClass->getTraitAliases();
    }
    /**
     * {@inheritDoc}
     */
    public function getInterfaceClassNames(): array
    {
        return $this->reflectionClass->getInterfaceClassNames();
    }
    /**
     * {@inheritDoc}
     */
    public function getInterfaces(): array
    {
        return $this->reflectionClass->getInterfaces();
    }
    /**
     * {@inheritDoc}
     */
    public function getImmediateInterfaces(): array
    {
        return $this->reflectionClass->getImmediateInterfaces();
    }
    /**
     * {@inheritDoc}
     */
    public function getInterfaceNames(): array
    {
        return $this->reflectionClass->getInterfaceNames();
    }
    public function isInstance(object $object): bool
    {
        return $this->reflectionClass->isInstance($object);
    }
    public function isSubclassOf(string $className): bool
    {
        return $this->reflectionClass->isSubclassOf($className);
    }
    public function implementsInterface(string $interfaceName): bool
    {
        return $this->reflectionClass->implementsInterface($interfaceName);
    }
    public function isInstantiable(): bool
    {
        return $this->reflectionClass->isInstantiable();
    }
    public function isCloneable(): bool
    {
        return $this->reflectionClass->isCloneable();
    }
    public function isIterateable(): bool
    {
        return $this->reflectionClass->isIterateable();
    }
    public function isEnum(): bool
    {
        return $this->reflectionClass->isEnum();
    }
    /**
     * {@inheritDoc}
     */
    public function getStaticProperties(): array
    {
        return $this->reflectionClass->getStaticProperties();
    }
    /**
     * @param mixed $value
     */
    public function setStaticPropertyValue(string $propertyName, $value): void
    {
        $this->reflectionClass->setStaticPropertyValue($propertyName, $value);
    }
    /**
     * @return mixed
     */
    public function getStaticPropertyValue(string $propertyName)
    {
        return $this->reflectionClass->getStaticPropertyValue($propertyName);
    }
    /** @return list<ReflectionAttribute> */
    public function getAttributes(): array
    {
        return $this->reflectionClass->getAttributes();
    }
    /** @return list<ReflectionAttribute> */
    public function getAttributesByName(string $name): array
    {
        return $this->reflectionClass->getAttributesByName($name);
    }
    /**
     * @param class-string $className
     *
     * @return list<ReflectionAttribute>
     */
    public function getAttributesByInstance(string $className): array
    {
        return $this->reflectionClass->getAttributesByInstance($className);
    }
}
