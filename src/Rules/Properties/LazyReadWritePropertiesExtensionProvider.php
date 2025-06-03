<?php

declare (strict_types=1);
namespace PHPStan\Rules\Properties;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
#[AutowiredService]
final class LazyReadWritePropertiesExtensionProvider implements \PHPStan\Rules\Properties\ReadWritePropertiesExtensionProvider
{
    private Container $container;
    /** @var ReadWritePropertiesExtension[]|null */
    private ?array $extensions = null;
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    public function getExtensions(): array
    {
        if ($this->extensions === null) {
            $this->extensions = $this->container->getServicesByTag(\PHPStan\Rules\Properties\ReadWritePropertiesExtensionProvider::EXTENSION_TAG);
        }
        return $this->extensions;
    }
}
