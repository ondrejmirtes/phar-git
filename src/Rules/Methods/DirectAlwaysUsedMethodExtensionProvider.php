<?php

declare (strict_types=1);
namespace PHPStan\Rules\Methods;

final class DirectAlwaysUsedMethodExtensionProvider implements \PHPStan\Rules\Methods\AlwaysUsedMethodExtensionProvider
{
    /**
     * @var AlwaysUsedMethodExtension[]
     */
    private array $extensions;
    /**
     * @param AlwaysUsedMethodExtension[] $extensions
     */
    public function __construct(array $extensions)
    {
        $this->extensions = $extensions;
    }
    public function getExtensions() : array
    {
        return $this->extensions;
    }
}
