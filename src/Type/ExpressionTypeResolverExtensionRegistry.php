<?php

declare (strict_types=1);
namespace PHPStan\Type;

final class ExpressionTypeResolverExtensionRegistry
{
    /**
     * @var array<ExpressionTypeResolverExtension>
     */
    private array $extensions;
    /**
     * @param array<ExpressionTypeResolverExtension> $extensions
     */
    public function __construct(array $extensions)
    {
        $this->extensions = $extensions;
    }
    /**
     * @return array<ExpressionTypeResolverExtension>
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }
}
