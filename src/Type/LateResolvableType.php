<?php

declare (strict_types=1);
namespace PHPStan\Type;

/** @api */
interface LateResolvableType
{
    public function resolve(): \PHPStan\Type\Type;
    public function isResolvable(): bool;
}
