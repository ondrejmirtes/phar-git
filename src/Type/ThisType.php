<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\PhpDocParser\Ast\Type\ThisTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\ClassReflection;
use function sprintf;
/** @api */
class ThisType extends \PHPStan\Type\StaticType
{
    /**
     * @api
     */
    public function __construct(ClassReflection $classReflection, ?\PHPStan\Type\Type $subtractedType = null)
    {
        parent::__construct($classReflection, $subtractedType);
    }
    public function changeBaseClass(ClassReflection $classReflection): \PHPStan\Type\StaticType
    {
        return new self($classReflection, $this->getSubtractedType());
    }
    public function describe(\PHPStan\Type\VerbosityLevel $level): string
    {
        return sprintf('$this(%s)', $this->getStaticObjectType()->describe($level));
    }
    public function isSuperTypeOf(\PHPStan\Type\Type $type): \PHPStan\Type\IsSuperTypeOfResult
    {
        if ($type instanceof self) {
            return $this->getStaticObjectType()->isSuperTypeOf($type);
        }
        if ($type instanceof \PHPStan\Type\CompoundType) {
            return $type->isSubTypeOf($this);
        }
        $parent = new parent($this->getClassReflection(), $this->getSubtractedType());
        return $parent->isSuperTypeOf($type)->and(\PHPStan\Type\IsSuperTypeOfResult::createMaybe());
    }
    public function changeSubtractedType(?\PHPStan\Type\Type $subtractedType): \PHPStan\Type\Type
    {
        $type = parent::changeSubtractedType($subtractedType);
        if ($type instanceof parent) {
            return new self($type->getClassReflection(), $subtractedType);
        }
        return $type;
    }
    public function traverse(callable $cb): \PHPStan\Type\Type
    {
        $subtractedType = $this->getSubtractedType() !== null ? $cb($this->getSubtractedType()) : null;
        if ($subtractedType !== $this->getSubtractedType()) {
            return new self($this->getClassReflection(), $subtractedType);
        }
        return $this;
    }
    public function traverseSimultaneously(\PHPStan\Type\Type $right, callable $cb): \PHPStan\Type\Type
    {
        if ($this->getSubtractedType() === null) {
            return $this;
        }
        return new self($this->getClassReflection());
    }
    public function toPhpDocNode(): TypeNode
    {
        return new ThisTypeNode();
    }
}
