<?php

declare (strict_types=1);
namespace PHPStan\BetterReflection\Reflection;

use Closure;
use Error;
use OutOfBoundsException;
use PhpParser\Node;
use PhpParser\Node\Stmt\Property as PropertyNode;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\FindingVisitor;
use ReflectionException;
use ReflectionProperty as CoreReflectionProperty;
use PHPStan\BetterReflection\NodeCompiler\CompiledValue;
use PHPStan\BetterReflection\NodeCompiler\CompileNodeToValue;
use PHPStan\BetterReflection\NodeCompiler\CompilerContext;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionProperty as ReflectionPropertyAdapter;
use PHPStan\BetterReflection\Reflection\Attribute\ReflectionAttributeHelper;
use PHPStan\BetterReflection\Reflection\Deprecated\DeprecatedHelper;
use PHPStan\BetterReflection\Reflection\Exception\ClassDoesNotExist;
use PHPStan\BetterReflection\Reflection\Exception\CodeLocationMissing;
use PHPStan\BetterReflection\Reflection\Exception\NoObjectProvided;
use PHPStan\BetterReflection\Reflection\Exception\NotAnObject;
use PHPStan\BetterReflection\Reflection\Exception\ObjectNotInstanceOfClass;
use PHPStan\BetterReflection\Reflection\StringCast\ReflectionPropertyStringCast;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\Util\CalculateReflectionColumn;
use PHPStan\BetterReflection\Util\ClassExistenceChecker;
use PHPStan\BetterReflection\Util\Exception\NoNodePosition;
use PHPStan\BetterReflection\Util\GetLastDocComment;
use function array_map;
use function assert;
use function func_num_args;
use function is_object;
use function sprintf;
use function str_contains;
/** @psalm-immutable */
class ReflectionProperty
{
    private Reflector $reflector;
    private \PHPStan\BetterReflection\Reflection\ReflectionClass $declaringClass;
    private \PHPStan\BetterReflection\Reflection\ReflectionClass $implementingClass;
    private bool $isPromoted;
    private bool $declaredAtCompileTime;
    /** @var non-empty-string */
    private string $name;
    /** @var int-mask-of<ReflectionPropertyAdapter::IS_*> */
    private int $modifiers;
    /**
     * @var \PHPStan\BetterReflection\Reflection\ReflectionNamedType|\PHPStan\BetterReflection\Reflection\ReflectionUnionType|\PHPStan\BetterReflection\Reflection\ReflectionIntersectionType|null
     */
    private $type;
    /**
     * @var \PhpParser\Node\Expr|null
     */
    private $default;
    /** @var non-empty-string|null */
    private $docComment;
    /** @var list<ReflectionAttribute> */
    private array $attributes;
    /** @var positive-int|null */
    private $startLine;
    /** @var positive-int|null */
    private $endLine;
    /** @var positive-int|null */
    private $startColumn;
    /** @var positive-int|null */
    private $endColumn;
    private bool $immediateVirtual;
    /** @var array{get?: ReflectionMethod, set?: ReflectionMethod} */
    private array $immediateHooks;
    /**
     * @var array{get?: ReflectionMethod, set?: ReflectionMethod}|null
     * @psalm-allow-private-mutation
     */
    private $cachedHooks = null;
    /** @psalm-allow-private-mutation
     * @var bool|null */
    private $cachedVirtual = null;
    /** @psalm-allow-private-mutation
     * @var \PHPStan\BetterReflection\NodeCompiler\CompiledValue|null */
    private $compiledDefaultValue = null;
    private function __construct(Reflector $reflector, PropertyNode $node, Node\PropertyItem $propertyNode, \PHPStan\BetterReflection\Reflection\ReflectionClass $declaringClass, \PHPStan\BetterReflection\Reflection\ReflectionClass $implementingClass, bool $isPromoted, bool $declaredAtCompileTime)
    {
        $this->reflector = $reflector;
        $this->declaringClass = $declaringClass;
        $this->implementingClass = $implementingClass;
        $this->isPromoted = $isPromoted;
        $this->declaredAtCompileTime = $declaredAtCompileTime;
        $this->name = $propertyNode->name->name;
        $this->modifiers = $this->computeModifiers($node);
        $this->type = $this->createType($node);
        $this->default = $propertyNode->default;
        $this->docComment = GetLastDocComment::forNode($node);
        $this->attributes = ReflectionAttributeHelper::createAttributes($reflector, $this, $node->attrGroups);
        $this->immediateVirtual = $this->computeImmediateVirtual($node);
        $this->immediateHooks = $this->createImmediateHooks($node);
        $startLine = $node->getStartLine();
        if ($startLine === -1) {
            $startLine = null;
        }
        $endLine = $node->getEndLine();
        if ($endLine === -1) {
            $endLine = null;
        }
        /** @psalm-suppress InvalidPropertyAssignmentValue */
        $this->startLine = $startLine;
        /** @psalm-suppress InvalidPropertyAssignmentValue */
        $this->endLine = $endLine;
        try {
            $this->startColumn = CalculateReflectionColumn::getStartColumn($declaringClass->getLocatedSource()->getSource(), $node);
        } catch (NoNodePosition $exception) {
            $this->startColumn = null;
        }
        try {
            $this->endColumn = CalculateReflectionColumn::getEndColumn($declaringClass->getLocatedSource()->getSource(), $node);
        } catch (NoNodePosition $exception) {
            $this->endColumn = null;
        }
    }
    /**
     * Create a reflection of an instance's property by its name
     *
     * @param non-empty-string $propertyName
     *
     * @throws ReflectionException
     * @throws IdentifierNotFound
     * @throws OutOfBoundsException
     */
    public static function createFromInstance(object $instance, string $propertyName) : self
    {
        $property = \PHPStan\BetterReflection\Reflection\ReflectionClass::createFromInstance($instance)->getProperty($propertyName);
        if ($property === null) {
            throw new OutOfBoundsException(sprintf('Could not find property: %s', $propertyName));
        }
        return $property;
    }
    /** @internal */
    public function withImplementingClass(\PHPStan\BetterReflection\Reflection\ReflectionClass $implementingClass) : self
    {
        $clone = clone $this;
        $clone->implementingClass = $implementingClass;
        if ($clone->type !== null) {
            $clone->type = $clone->type->withOwner($clone);
        }
        $clone->attributes = array_map(static fn(\PHPStan\BetterReflection\Reflection\ReflectionAttribute $attribute): \PHPStan\BetterReflection\Reflection\ReflectionAttribute => $attribute->withOwner($clone), $this->attributes);
        $this->compiledDefaultValue = null;
        return $clone;
    }
    /** @return non-empty-string */
    public function __toString() : string
    {
        return ReflectionPropertyStringCast::toString($this);
    }
    /**
     * @internal
     *
     * @param PropertyNode $node Node has to be processed by the PhpParser\NodeVisitor\NameResolver
     */
    public static function createFromNode(Reflector $reflector, PropertyNode $node, Node\PropertyItem $propertyProperty, \PHPStan\BetterReflection\Reflection\ReflectionClass $declaringClass, \PHPStan\BetterReflection\Reflection\ReflectionClass $implementingClass, bool $isPromoted = \false, bool $declaredAtCompileTime = \true) : self
    {
        return new self($reflector, $node, $propertyProperty, $declaringClass, $implementingClass, $isPromoted, $declaredAtCompileTime);
    }
    /**
     * Has the property been declared at compile-time?
     *
     * Note that unless the property is static, this is hard coded to return
     * true, because we are unable to reflect instances of classes, therefore
     * we can be sure that all properties are always declared at compile-time.
     */
    public function isDefault() : bool
    {
        return $this->declaredAtCompileTime;
    }
    public function isDynamic() : bool
    {
        return !$this->isDefault();
    }
    /**
     * Get the core-reflection-compatible modifier values.
     *
     * @return int-mask-of<ReflectionPropertyAdapter::IS_*>
     */
    public function getModifiers() : int
    {
        /** @var int-mask-of<ReflectionPropertyAdapter::IS_*> $modifiers */
        $modifiers = $this->modifiers + ($this->isVirtual() ? ReflectionPropertyAdapter::IS_VIRTUAL_COMPATIBILITY : 0);
        return $modifiers;
    }
    /**
     * Get the name of the property.
     *
     * @return non-empty-string
     */
    public function getName() : string
    {
        return $this->name;
    }
    /**
     * Is the property private?
     */
    public function isPrivate() : bool
    {
        return (bool) ($this->modifiers & CoreReflectionProperty::IS_PRIVATE);
    }
    public function isPrivateSet() : bool
    {
        return (bool) ($this->modifiers & ReflectionPropertyAdapter::IS_PRIVATE_SET_COMPATIBILITY);
    }
    /**
     * Is the property protected?
     */
    public function isProtected() : bool
    {
        return (bool) ($this->modifiers & CoreReflectionProperty::IS_PROTECTED);
    }
    public function isProtectedSet() : bool
    {
        return (bool) ($this->modifiers & ReflectionPropertyAdapter::IS_PROTECTED_SET_COMPATIBILITY);
    }
    /**
     * Is the property public?
     */
    public function isPublic() : bool
    {
        return (bool) ($this->modifiers & CoreReflectionProperty::IS_PUBLIC);
    }
    /**
     * Is the property static?
     */
    public function isStatic() : bool
    {
        return (bool) ($this->modifiers & CoreReflectionProperty::IS_STATIC);
    }
    public function isFinal() : bool
    {
        return (bool) ($this->modifiers & ReflectionPropertyAdapter::IS_FINAL_COMPATIBILITY);
    }
    public function isAbstract() : bool
    {
        return (bool) ($this->modifiers & ReflectionPropertyAdapter::IS_ABSTRACT_COMPATIBILITY) || $this->declaringClass->isInterface();
    }
    public function isPromoted() : bool
    {
        return $this->isPromoted;
    }
    public function isInitialized(?object $object = null) : bool
    {
        if ($object === null && $this->isStatic()) {
            return !$this->hasType() || $this->hasDefaultValue();
        }
        try {
            $this->getValue($object);
            return \true;
            /** @phpstan-ignore catch.neverThrown */
        } catch (Error $e) {
            if (\strpos($e->getMessage(), 'must not be accessed before initialization') !== \false) {
                return \false;
            }
            throw $e;
        }
    }
    public function isReadOnly() : bool
    {
        return $this->modifiers & ReflectionPropertyAdapter::IS_READONLY_COMPATIBILITY || $this->getDeclaringClass()->isReadOnly();
    }
    public function getDeclaringClass() : \PHPStan\BetterReflection\Reflection\ReflectionClass
    {
        return $this->declaringClass;
    }
    public function getImplementingClass() : \PHPStan\BetterReflection\Reflection\ReflectionClass
    {
        return $this->implementingClass;
    }
    /** @return non-empty-string|null */
    public function getDocComment() : ?string
    {
        return $this->docComment;
    }
    public function hasDefaultValue() : bool
    {
        return !$this->hasType() || $this->default !== null;
    }
    public function getDefaultValueExpression() : ?\PhpParser\Node\Expr
    {
        return $this->default;
    }
    /**
     * Get the default value of the property (as defined before constructor is
     * called, when the property is defined)
     * @return mixed
     */
    public function getDefaultValue()
    {
        if ($this->default === null) {
            return null;
        }
        if ($this->compiledDefaultValue === null) {
            $this->compiledDefaultValue = (new CompileNodeToValue())->__invoke($this->default, new CompilerContext($this->reflector, $this));
        }
        /** @psalm-var scalar|array<scalar>|null $value */
        $value = $this->compiledDefaultValue->value;
        return $value;
    }
    public function isDeprecated() : bool
    {
        return DeprecatedHelper::isDeprecated($this);
    }
    /**
     * Get the line number that this property starts on.
     *
     * @return positive-int
     *
     * @throws CodeLocationMissing
     */
    public function getStartLine() : int
    {
        if ($this->startLine === null) {
            throw CodeLocationMissing::create(sprintf('Was looking for property "$%s" in "%s".', $this->name, $this->implementingClass->getName()));
        }
        return $this->startLine;
    }
    /**
     * Get the line number that this property ends on.
     *
     * @return positive-int
     *
     * @throws CodeLocationMissing
     */
    public function getEndLine() : int
    {
        if ($this->endLine === null) {
            throw CodeLocationMissing::create(sprintf('Was looking for property "$%s" in "%s".', $this->name, $this->implementingClass->getName()));
        }
        return $this->endLine;
    }
    /**
     * @return positive-int
     *
     * @throws CodeLocationMissing
     */
    public function getStartColumn() : int
    {
        if ($this->startColumn === null) {
            throw CodeLocationMissing::create(sprintf('Was looking for property "$%s" in "%s".', $this->name, $this->implementingClass->getName()));
        }
        return $this->startColumn;
    }
    /**
     * @return positive-int
     *
     * @throws CodeLocationMissing
     */
    public function getEndColumn() : int
    {
        if ($this->endColumn === null) {
            throw CodeLocationMissing::create(sprintf('Was looking for property "$%s" in "%s".', $this->name, $this->implementingClass->getName()));
        }
        return $this->endColumn;
    }
    /** @return list<ReflectionAttribute> */
    public function getAttributes() : array
    {
        return $this->attributes;
    }
    /** @return list<ReflectionAttribute> */
    public function getAttributesByName(string $name) : array
    {
        return ReflectionAttributeHelper::filterAttributesByName($this->getAttributes(), $name);
    }
    /**
     * @param class-string $className
     *
     * @return list<ReflectionAttribute>
     */
    public function getAttributesByInstance(string $className) : array
    {
        return ReflectionAttributeHelper::filterAttributesByInstance($this->getAttributes(), $className);
    }
    /**
     * @throws ClassDoesNotExist
     * @throws NoObjectProvided
     * @throws ObjectNotInstanceOfClass
     * @return mixed
     */
    public function getValue(?object $object = null)
    {
        $implementingClassName = $this->getImplementingClass()->getName();
        if ($this->isStatic()) {
            $this->assertClassExist($implementingClassName);
            $closure = Closure::bind(fn(string $implementingClassName, string $propertyName) => $implementingClassName::${$propertyName}, null, $implementingClassName);
            /** @phpstan-ignore function.alreadyNarrowedType, instanceof.alwaysTrue */
            assert($closure instanceof Closure);
            return $closure->__invoke($implementingClassName, $this->getName());
        }
        $instance = $this->assertObject($object);
        $closure = Closure::bind(fn(object $instance, string $propertyName) => $instance->{$propertyName}, $instance, $implementingClassName);
        /** @phpstan-ignore function.alreadyNarrowedType, instanceof.alwaysTrue */
        assert($closure instanceof Closure);
        return $closure->__invoke($instance, $this->getName());
    }
    /**
     * @throws ClassDoesNotExist
     * @throws NoObjectProvided
     * @throws NotAnObject
     * @throws ObjectNotInstanceOfClass
     * @param mixed $object
     * @param mixed $value
     */
    public function setValue($object, $value = null) : void
    {
        $implementingClassName = $this->getImplementingClass()->getName();
        if ($this->isStatic()) {
            $this->assertClassExist($implementingClassName);
            $closure = Closure::bind(function (string $_implementingClassName, string $_propertyName, $value) : void {
                /** @psalm-suppress MixedAssignment */
                $_implementingClassName::${$_propertyName} = $value;
            }, null, $implementingClassName);
            /** @phpstan-ignore function.alreadyNarrowedType, instanceof.alwaysTrue */
            assert($closure instanceof Closure);
            $closure->__invoke($implementingClassName, $this->getName(), func_num_args() === 2 ? $value : $object);
            return;
        }
        $instance = $this->assertObject($object);
        $closure = Closure::bind(function (object $instance, string $propertyName, $value) : void {
            $instance->{$propertyName} = $value;
        }, $instance, $implementingClassName);
        /** @phpstan-ignore function.alreadyNarrowedType, instanceof.alwaysTrue */
        assert($closure instanceof Closure);
        $closure->__invoke($instance, $this->getName(), $value);
    }
    /**
     * Does this property allow null?
     */
    public function allowsNull() : bool
    {
        return $this->type === null || $this->type->allowsNull();
    }
    /**
     * @return \PHPStan\BetterReflection\Reflection\ReflectionNamedType|\PHPStan\BetterReflection\Reflection\ReflectionUnionType|\PHPStan\BetterReflection\Reflection\ReflectionIntersectionType|null
     */
    private function createType(PropertyNode $node)
    {
        $type = $node->type;
        if ($type === null) {
            return null;
        }
        assert($type instanceof Node\Identifier || $type instanceof Node\Name || $type instanceof Node\NullableType || $type instanceof Node\UnionType || $type instanceof Node\IntersectionType);
        return \PHPStan\BetterReflection\Reflection\ReflectionType::createFromNode($this->reflector, $this, $type);
    }
    /**
     * Get the ReflectionType instance representing the type declaration for
     * this property
     *
     * (note: this has nothing to do with DocBlocks).
     * @return \PHPStan\BetterReflection\Reflection\ReflectionNamedType|\PHPStan\BetterReflection\Reflection\ReflectionUnionType|\PHPStan\BetterReflection\Reflection\ReflectionIntersectionType|null
     */
    public function getType()
    {
        return $this->type;
    }
    /**
     * Does this property have a type declaration?
     *
     * (note: this has nothing to do with DocBlocks).
     */
    public function hasType() : bool
    {
        return $this->type !== null;
    }
    public function isVirtual() : bool
    {
        $this->cachedVirtual ??= $this->createCachedVirtual();
        return $this->cachedVirtual;
    }
    public function hasHooks() : bool
    {
        return $this->getHooks() !== [];
    }
    /**
     * @param ReflectionPropertyHookType::* $hookType
     */
    public function hasHook(string $hookType) : bool
    {
        return isset($this->getHooks()[$hookType]);
    }
    /**
     * @param ReflectionPropertyHookType::* $hookType
     */
    public function getHook(string $hookType) : ?\PHPStan\BetterReflection\Reflection\ReflectionMethod
    {
        return $this->getHooks()[$hookType] ?? null;
    }
    /** @return array{get?: ReflectionMethod, set?: ReflectionMethod} */
    public function getHooks() : array
    {
        $this->cachedHooks ??= $this->createCachedHooks();
        return $this->cachedHooks;
    }
    /**
     * @param class-string $className
     *
     * @throws ClassDoesNotExist
     */
    private function assertClassExist(string $className) : void
    {
        if (!ClassExistenceChecker::classExists($className, \true) && !ClassExistenceChecker::traitExists($className, \true)) {
            throw new ClassDoesNotExist('Property cannot be retrieved as the class does not exist');
        }
    }
    /**
     * @throws NoObjectProvided
     * @throws NotAnObject
     * @throws ObjectNotInstanceOfClass
     *
     * @psalm-assert object $object
     * @param mixed $object
     */
    private function assertObject($object) : object
    {
        if ($object === null) {
            throw NoObjectProvided::create();
        }
        if (!is_object($object)) {
            throw NotAnObject::fromNonObject($object);
        }
        $implementingClassName = $this->getImplementingClass()->getName();
        if (\get_class($object) !== $implementingClassName) {
            throw ObjectNotInstanceOfClass::fromClassName($implementingClassName);
        }
        return $object;
    }
    /** @return int-mask-of<ReflectionPropertyAdapter::IS_*> */
    private function computeModifiers(PropertyNode $node) : int
    {
        $modifiers = $node->isReadonly() ? ReflectionPropertyAdapter::IS_READONLY_COMPATIBILITY : 0;
        $modifiers += $node->isStatic() ? CoreReflectionProperty::IS_STATIC : 0;
        $modifiers += $node->isPrivate() ? CoreReflectionProperty::IS_PRIVATE : 0;
        $modifiers += !$node->isPrivate() && $node->isPrivateSet() ? ReflectionPropertyAdapter::IS_PRIVATE_SET_COMPATIBILITY : 0;
        $modifiers += $node->isProtected() ? CoreReflectionProperty::IS_PROTECTED : 0;
        $modifiers += !$node->isProtected() && $node->isProtectedSet() ? ReflectionPropertyAdapter::IS_PROTECTED_SET_COMPATIBILITY : 0;
        $modifiers += $node->isPublic() ? CoreReflectionProperty::IS_PUBLIC : 0;
        $modifiers += $node->isFinal() ? ReflectionPropertyAdapter::IS_FINAL_COMPATIBILITY : 0;
        $modifiers += $node->isAbstract() ? ReflectionPropertyAdapter::IS_ABSTRACT_COMPATIBILITY : 0;
        if (!($modifiers & ReflectionPropertyAdapter::IS_FINAL_COMPATIBILITY) && $modifiers & ReflectionPropertyAdapter::IS_PRIVATE_SET_COMPATIBILITY) {
            $modifiers += ReflectionPropertyAdapter::IS_FINAL_COMPATIBILITY;
        }
        if (!($modifiers & (ReflectionPropertyAdapter::IS_PROTECTED_SET_COMPATIBILITY | ReflectionPropertyAdapter::IS_PRIVATE_SET_COMPATIBILITY)) && !$node->isPublicSet() && $node->isPublic() && $modifiers & ReflectionPropertyAdapter::IS_READONLY_COMPATIBILITY) {
            $modifiers += ReflectionPropertyAdapter::IS_PROTECTED_SET_COMPATIBILITY;
        }
        /** @phpstan-ignore return.type */
        return $modifiers;
    }
    private function computeImmediateVirtual(PropertyNode $node) : bool
    {
        if ($node->hooks === []) {
            return \false;
        }
        $setHook = null;
        $getHook = null;
        foreach ($node->hooks as $hook) {
            if ($hook->name->name === 'set') {
                $setHook = $hook;
            } elseif ($hook->name->name === 'get') {
                $getHook = $hook;
            }
        }
        if ($setHook !== null && !$this->computeImmediateVirtualBasedOnHook($setHook)) {
            return \false;
        }
        if ($getHook === null) {
            return \true;
        }
        return $this->computeImmediateVirtualBasedOnHook($getHook);
    }
    private function computeImmediateVirtualBasedOnHook(Node\PropertyHook $hook) : bool
    {
        $hookBody = $hook->getStmts();
        // Abstract property or property in interface
        if ($hookBody === null) {
            return \true;
        }
        $visitor = new FindingVisitor(static fn(Node $node): bool => $node instanceof Node\Expr\PropertyFetch);
        $traverser = new NodeTraverser($visitor);
        $traverser->traverse($hookBody);
        foreach ($visitor->getFoundNodes() as $propertyFetchNode) {
            assert($propertyFetchNode instanceof Node\Expr\PropertyFetch);
            if ($propertyFetchNode->var instanceof Node\Expr\Variable && $propertyFetchNode->var->name === 'this' && $propertyFetchNode->name instanceof Node\Identifier && $propertyFetchNode->name->name === $this->name) {
                return \false;
            }
        }
        return \true;
    }
    /** @return array{get?: ReflectionMethod, set?: ReflectionMethod} */
    private function createImmediateHooks(PropertyNode $node) : array
    {
        $hooks = [];
        foreach ($node->hooks as $hook) {
            $hookName = $hook->name->name;
            assert($hookName === 'get' || $hookName === 'set');
            $hookType = $node->type;
            assert($hookType === null || $hookType instanceof Node\Identifier || $hookType instanceof Node\Name || $hookType instanceof Node\NullableType || $hookType instanceof Node\UnionType || $hookType instanceof Node\IntersectionType);
            $hooks[$hookName] = \PHPStan\BetterReflection\Reflection\ReflectionMethod::createFromPropertyHook($this->reflector, $hook, $this->getDeclaringClass()->getLocatedSource(), sprintf('$%s::%s', $this->name, $hookName), $hookType, $this->getDeclaringClass(), $this->getImplementingClass(), $this->getDeclaringClass(), $this);
        }
        return $hooks;
    }
    private function createCachedVirtual() : bool
    {
        if (!$this->immediateVirtual) {
            return \false;
        }
        return (($nullsafeVariable1 = $this->getParentProperty()) ? $nullsafeVariable1->isVirtual() : null) ?? \true;
    }
    /** @return array{get?: ReflectionMethod, set?: ReflectionMethod} */
    private function createCachedHooks() : array
    {
        $hooks = $this->immediateHooks;
        // Just optimization - we don't need to check parent property when both hooks are defined in this class
        if (isset($hooks['get'], $hooks['set'])) {
            return $hooks;
        }
        $parentHooks = (($nullsafeVariable2 = $this->getParentProperty()) ? $nullsafeVariable2->getHooks() : null) ?? [];
        foreach ($parentHooks as $hookName => $parentHook) {
            if (isset($hooks[$hookName])) {
                continue;
            }
            $hooks[$hookName] = $parentHook;
        }
        return $hooks;
    }
    private function getParentProperty() : ?\PHPStan\BetterReflection\Reflection\ReflectionProperty
    {
        return ($nullsafeVariable3 = $this->getDeclaringClass()->getParentClass()) ? $nullsafeVariable3->getProperty($this->name) : null;
    }
}
