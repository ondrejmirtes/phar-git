<?php

declare (strict_types=1);
namespace PHPStan\BetterReflection\Reflection;

use LogicException;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\BetterReflection\Reflector\Reflector;
use function array_key_exists;
use function assert;
use function sprintf;
use function strtolower;
/** @psalm-immutable */
class ReflectionNamedType extends \PHPStan\BetterReflection\Reflection\ReflectionType
{
    private Reflector $reflector;
    /**
     * @var \PHPStan\BetterReflection\Reflection\ReflectionParameter|\PHPStan\BetterReflection\Reflection\ReflectionMethod|\PHPStan\BetterReflection\Reflection\ReflectionFunction|\PHPStan\BetterReflection\Reflection\ReflectionEnum|\PHPStan\BetterReflection\Reflection\ReflectionProperty|\PHPStan\BetterReflection\Reflection\ReflectionClassConstant
     */
    private $owner;
    /**
     * @var \PhpParser\Node\Identifier|\PhpParser\Node\Name
     */
    private $type;
    private const BUILT_IN_TYPES = ['int' => null, 'float' => null, 'string' => null, 'bool' => null, 'callable' => null, 'self' => null, 'parent' => null, 'array' => null, 'iterable' => null, 'object' => null, 'void' => null, 'mixed' => null, 'static' => null, 'null' => null, 'never' => null, 'false' => null, 'true' => null];
    /** @var non-empty-string */
    private string $name;
    /** @internal
     * @param \PHPStan\BetterReflection\Reflection\ReflectionParameter|\PHPStan\BetterReflection\Reflection\ReflectionMethod|\PHPStan\BetterReflection\Reflection\ReflectionFunction|\PHPStan\BetterReflection\Reflection\ReflectionEnum|\PHPStan\BetterReflection\Reflection\ReflectionProperty|\PHPStan\BetterReflection\Reflection\ReflectionClassConstant $owner
     * @param \PhpParser\Node\Identifier|\PhpParser\Node\Name $type */
    public function __construct(Reflector $reflector, $owner, $type)
    {
        $this->reflector = $reflector;
        $this->owner = $owner;
        $this->type = $type;
        $this->name = $type->toString();
    }
    /** @internal
     * @param \PHPStan\BetterReflection\Reflection\ReflectionParameter|\PHPStan\BetterReflection\Reflection\ReflectionMethod|\PHPStan\BetterReflection\Reflection\ReflectionFunction|\PHPStan\BetterReflection\Reflection\ReflectionEnum|\PHPStan\BetterReflection\Reflection\ReflectionProperty|\PHPStan\BetterReflection\Reflection\ReflectionClassConstant $owner
     * @return static */
    public function withOwner($owner)
    {
        $clone = clone $this;
        $clone->owner = $owner;
        return $clone;
    }
    /** @return non-empty-string */
    public function getName() : string
    {
        return $this->name;
    }
    /**
     * Checks if it is a built-in type (i.e., it's not an object...)
     *
     * @see https://php.net/manual/en/reflectiontype.isbuiltin.php
     */
    public function isBuiltin() : bool
    {
        return array_key_exists(strtolower($this->name), self::BUILT_IN_TYPES);
    }
    public function getClass() : \PHPStan\BetterReflection\Reflection\ReflectionClass
    {
        if (!$this->isBuiltin()) {
            return $this->reflector->reflectClass($this->name);
        }
        if ($this->owner instanceof \PHPStan\BetterReflection\Reflection\ReflectionEnum || $this->owner instanceof \PHPStan\BetterReflection\Reflection\ReflectionFunction || $this->owner instanceof \PHPStan\BetterReflection\Reflection\ReflectionParameter && $this->owner->getDeclaringFunction() instanceof \PHPStan\BetterReflection\Reflection\ReflectionFunction) {
            throw new LogicException(sprintf('The type %s cannot be resolved to class', $this->name));
        }
        $lowercaseName = strtolower($this->name);
        if ($lowercaseName === 'self') {
            $class = $this->owner->getImplementingClass();
            assert($class instanceof \PHPStan\BetterReflection\Reflection\ReflectionClass);
            return $class;
        }
        if ($lowercaseName === 'parent') {
            $class = $this->owner->getDeclaringClass();
            assert($class instanceof \PHPStan\BetterReflection\Reflection\ReflectionClass);
            $parentClass = $class->getParentClass();
            assert($parentClass instanceof \PHPStan\BetterReflection\Reflection\ReflectionClass);
            return $parentClass;
        }
        if ($this->owner instanceof \PHPStan\BetterReflection\Reflection\ReflectionMethod && $lowercaseName === 'static') {
            return $this->owner->getCurrentClass();
        }
        throw new LogicException(sprintf('The type %s cannot be resolved to class', $this->name));
    }
    public function allowsNull() : bool
    {
        switch (strtolower($this->name)) {
            case 'mixed':
                return \true;
            case 'null':
                return \true;
            default:
                return \false;
        }
    }
    public function isIdentifier() : bool
    {
        return $this->type instanceof Identifier;
    }
    /** @return non-empty-string */
    public function __toString() : string
    {
        return $this->getName();
    }
}
