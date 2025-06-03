<?php

declare (strict_types=1);
namespace PHPStan\Reflection\ReflectionProvider;

use PHPStan\Reflection\ReflectionProvider;
final class DirectReflectionProviderProvider implements \PHPStan\Reflection\ReflectionProvider\ReflectionProviderProvider
{
    private ReflectionProvider $reflectionProvider;
    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }
    public function getReflectionProvider(): ReflectionProvider
    {
        return $this->reflectionProvider;
    }
}
