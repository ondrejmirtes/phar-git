<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc;

final class TypeNodeResolverExtensionAwareRegistry implements \PHPStan\PhpDoc\TypeNodeResolverExtensionRegistry
{
    /**
     * @var TypeNodeResolverExtension[]
     */
    private array $extensions;
    /**
     * @param TypeNodeResolverExtension[] $extensions
     */
    public function __construct(\PHPStan\PhpDoc\TypeNodeResolver $typeNodeResolver, array $extensions)
    {
        $this->extensions = $extensions;
        foreach ($extensions as $extension) {
            if (!$extension instanceof \PHPStan\PhpDoc\TypeNodeResolverAwareExtension) {
                continue;
            }
            $extension->setTypeNodeResolver($typeNodeResolver);
        }
    }
    /**
     * @return TypeNodeResolverExtension[]
     */
    public function getExtensions() : array
    {
        return $this->extensions;
    }
}
