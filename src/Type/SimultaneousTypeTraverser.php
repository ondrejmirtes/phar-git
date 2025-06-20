<?php

declare (strict_types=1);
namespace PHPStan\Type;

/**
 * @api
 */
final class SimultaneousTypeTraverser
{
    /** @var callable(Type $left, Type $right, callable(Type, Type): Type $traverse): Type */
    private $cb;
    /**
     * @param callable(Type $left, Type $right, callable(Type, Type): Type $traverse): Type $cb
     */
    public static function map(\PHPStan\Type\Type $left, \PHPStan\Type\Type $right, callable $cb): \PHPStan\Type\Type
    {
        $self = new self($cb);
        return $self->mapInternal($left, $right);
    }
    /** @param callable(Type $left, Type $right, callable(Type, Type): Type $traverse): Type $cb */
    private function __construct(callable $cb)
    {
        $this->cb = $cb;
    }
    /** @internal */
    public function mapInternal(\PHPStan\Type\Type $left, \PHPStan\Type\Type $right): \PHPStan\Type\Type
    {
        return ($this->cb)($left, $right, [$this, 'traverseInternal']);
    }
    /** @internal */
    public function traverseInternal(\PHPStan\Type\Type $left, \PHPStan\Type\Type $right): \PHPStan\Type\Type
    {
        return $left->traverseSimultaneously($right, [$this, 'mapInternal']);
    }
}
