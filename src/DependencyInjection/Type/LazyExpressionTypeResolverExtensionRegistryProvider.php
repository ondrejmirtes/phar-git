<?php

declare (strict_types=1);
namespace PHPStan\DependencyInjection\Type;

use PHPStan\Broker\BrokerFactory;
use PHPStan\DependencyInjection\Container;
use PHPStan\Type\ExpressionTypeResolverExtensionRegistry;
final class LazyExpressionTypeResolverExtensionRegistryProvider implements \PHPStan\DependencyInjection\Type\ExpressionTypeResolverExtensionRegistryProvider
{
    private Container $container;
    private ?ExpressionTypeResolverExtensionRegistry $registry = null;
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    public function getRegistry() : ExpressionTypeResolverExtensionRegistry
    {
        if ($this->registry === null) {
            $this->registry = new ExpressionTypeResolverExtensionRegistry($this->container->getServicesByTag(BrokerFactory::EXPRESSION_TYPE_RESOLVER_EXTENSION_TAG));
        }
        return $this->registry;
    }
}
