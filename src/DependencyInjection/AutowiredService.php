<?php

declare (strict_types=1);
namespace PHPStan\DependencyInjection;

use Attribute;
/**
 * Registers a service in the DI container.
 *
 * Auto-adds service extension tags based on implemented interfaces.
 *
 * Works thanks to https://github.com/ondrejmirtes/composer-attribute-collector
 * and AutowiredAttributeServicesExtension.
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class AutowiredService
{
    public ?string $name;
    public function __construct(?string $name = null)
    {
        $this->name = $name;
    }
}
