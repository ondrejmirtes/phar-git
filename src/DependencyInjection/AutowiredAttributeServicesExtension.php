<?php

declare (strict_types=1);
namespace PHPStan\DependencyInjection;

use _PHPStan_checksum\Nette\DI\CompilerExtension;
use _PHPStan_checksum\olvlvl\ComposerAttributeCollector\Attributes;
use ReflectionClass;
final class AutowiredAttributeServicesExtension extends CompilerExtension
{
    public function loadConfiguration() : void
    {
        require_once __DIR__ . '/../../vendor/attributes.php';
        $autowiredServiceClasses = Attributes::findTargetClasses(\PHPStan\DependencyInjection\AutowiredService::class);
        $builder = $this->getContainerBuilder();
        foreach ($autowiredServiceClasses as $class) {
            $reflection = new ReflectionClass($class->name);
            $attribute = $class->attribute;
            $definition = $builder->addDefinition($attribute->name)->setType($class->name)->setAutowired();
            foreach (\PHPStan\DependencyInjection\ValidateServiceTagsExtension::INTERFACE_TAG_MAPPING as $interface => $tag) {
                if (!$reflection->implementsInterface($interface)) {
                    continue;
                }
                $definition->addTag($tag);
            }
        }
    }
}
