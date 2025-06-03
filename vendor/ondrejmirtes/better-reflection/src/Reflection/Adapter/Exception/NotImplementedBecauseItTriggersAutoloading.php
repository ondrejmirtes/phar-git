<?php

declare (strict_types=1);
namespace PHPStan\BetterReflection\Reflection\Adapter\Exception;

class NotImplementedBecauseItTriggersAutoloading extends \PHPStan\BetterReflection\Reflection\Adapter\Exception\NotImplemented
{
    public static function create(): self
    {
        return new self('Not implemented because it triggers autoloading');
    }
}
