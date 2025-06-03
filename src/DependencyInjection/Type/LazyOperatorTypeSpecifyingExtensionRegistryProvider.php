<?php

declare (strict_types=1);
namespace PHPStan\DependencyInjection\Type;

use PHPStan\Broker\BrokerFactory;
use PHPStan\DependencyInjection\Container;
use PHPStan\Type\OperatorTypeSpecifyingExtensionRegistry;
final class LazyOperatorTypeSpecifyingExtensionRegistryProvider implements \PHPStan\DependencyInjection\Type\OperatorTypeSpecifyingExtensionRegistryProvider
{
    private Container $container;
    private ?OperatorTypeSpecifyingExtensionRegistry $registry = null;
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    public function getRegistry(): OperatorTypeSpecifyingExtensionRegistry
    {
        if ($this->registry === null) {
            $this->registry = new OperatorTypeSpecifyingExtensionRegistry($this->container->getServicesByTag(BrokerFactory::OPERATOR_TYPE_SPECIFYING_EXTENSION_TAG));
        }
        return $this->registry;
    }
}
