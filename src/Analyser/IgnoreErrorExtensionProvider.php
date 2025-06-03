<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
#[AutowiredService]
final class IgnoreErrorExtensionProvider
{
    private Container $container;
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    /**
     * @return IgnoreErrorExtension[]
     */
    public function getExtensions(): array
    {
        return $this->container->getServicesByTag(\PHPStan\Analyser\IgnoreErrorExtension::EXTENSION_TAG);
    }
}
