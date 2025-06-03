<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use PHPStan\Php\ComposerPhpVersionFactory;
use PHPStan\Reflection\ReflectionProvider\ReflectionProviderProvider;
#[\PHPStan\DependencyInjection\AutowiredService]
final class ConstantResolverFactory
{
    private ReflectionProviderProvider $reflectionProviderProvider;
    private Container $container;
    public function __construct(ReflectionProviderProvider $reflectionProviderProvider, Container $container)
    {
        $this->reflectionProviderProvider = $reflectionProviderProvider;
        $this->container = $container;
    }
    public function create(): \PHPStan\Analyser\ConstantResolver
    {
        $composerFactory = $this->container->getByType(ComposerPhpVersionFactory::class);
        return new \PHPStan\Analyser\ConstantResolver($this->reflectionProviderProvider, $this->container->getParameter('dynamicConstantNames'), $this->container->getParameter('phpVersion'), $composerFactory);
    }
}
