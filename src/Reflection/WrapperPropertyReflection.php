<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

interface WrapperPropertyReflection extends \PHPStan\Reflection\ExtendedPropertyReflection
{
    public function getOriginalReflection(): \PHPStan\Reflection\ExtendedPropertyReflection;
}
