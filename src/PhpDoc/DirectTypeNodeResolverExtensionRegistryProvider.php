<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc;

final class DirectTypeNodeResolverExtensionRegistryProvider implements \PHPStan\PhpDoc\TypeNodeResolverExtensionRegistryProvider
{
    private \PHPStan\PhpDoc\TypeNodeResolverExtensionRegistry $registry;
    public function __construct(\PHPStan\PhpDoc\TypeNodeResolverExtensionRegistry $registry)
    {
        $this->registry = $registry;
    }
    public function getRegistry() : \PHPStan\PhpDoc\TypeNodeResolverExtensionRegistry
    {
        return $this->registry;
    }
}
