<?php

declare (strict_types=1);
namespace PHPStan\Type;

/** @api */
class ErrorType extends \PHPStan\Type\MixedType
{
    /** @api */
    public function __construct()
    {
        parent::__construct();
    }
    public function describe(\PHPStan\Type\VerbosityLevel $level) : string
    {
        return $level->handle(fn(): string => parent::describe($level), fn(): string => parent::describe($level), static fn(): string => '*ERROR*');
    }
    public function getIterableKeyType() : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function getIterableValueType() : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ErrorType();
    }
    public function subtract(\PHPStan\Type\Type $type) : \PHPStan\Type\Type
    {
        return new self();
    }
    public function equals(\PHPStan\Type\Type $type) : bool
    {
        return $type instanceof self;
    }
}
