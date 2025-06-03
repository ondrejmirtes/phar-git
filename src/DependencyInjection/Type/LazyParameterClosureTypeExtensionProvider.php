<?php

declare (strict_types=1);
namespace PHPStan\DependencyInjection\Type;

use PHPStan\DependencyInjection\Container;
final class LazyParameterClosureTypeExtensionProvider implements \PHPStan\DependencyInjection\Type\ParameterClosureTypeExtensionProvider
{
    private Container $container;
    public const FUNCTION_TAG = 'phpstan.functionParameterClosureTypeExtension';
    public const METHOD_TAG = 'phpstan.methodParameterClosureTypeExtension';
    public const STATIC_METHOD_TAG = 'phpstan.staticMethodParameterClosureTypeExtension';
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    public function getFunctionParameterClosureTypeExtensions() : array
    {
        return $this->container->getServicesByTag(self::FUNCTION_TAG);
    }
    public function getMethodParameterClosureTypeExtensions() : array
    {
        return $this->container->getServicesByTag(self::METHOD_TAG);
    }
    public function getStaticMethodParameterClosureTypeExtensions() : array
    {
        return $this->container->getServicesByTag(self::STATIC_METHOD_TAG);
    }
}
