<?php

declare (strict_types=1);
namespace PHPStan\Rules\Methods;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
#[\PHPStan\DependencyInjection\AutowiredService]
final class LazyAlwaysUsedMethodExtensionProvider implements \PHPStan\Rules\Methods\AlwaysUsedMethodExtensionProvider
{
    private Container $container;
    /** @var AlwaysUsedMethodExtension[]|null */
    private ?array $extensions = null;
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    public function getExtensions() : array
    {
        return $this->extensions ??= $this->container->getServicesByTag(static::EXTENSION_TAG);
    }
}
