<?php

declare (strict_types=1);
namespace PHPStan\Rules\Constants;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
#[\PHPStan\DependencyInjection\AutowiredService]
final class LazyAlwaysUsedClassConstantsExtensionProvider implements \PHPStan\Rules\Constants\AlwaysUsedClassConstantsExtensionProvider
{
    private Container $container;
    /** @var AlwaysUsedClassConstantsExtension[]|null */
    private ?array $extensions = null;
    public function __construct(Container $container)
    {
        $this->container = $container;
    }
    public function getExtensions(): array
    {
        if ($this->extensions === null) {
            $this->extensions = $this->container->getServicesByTag(\PHPStan\Rules\Constants\AlwaysUsedClassConstantsExtensionProvider::EXTENSION_TAG);
        }
        return $this->extensions;
    }
}
