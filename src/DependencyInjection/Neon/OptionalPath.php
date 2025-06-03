<?php

declare (strict_types=1);
namespace PHPStan\DependencyInjection\Neon;

final class OptionalPath
{
    /**
     * @readonly
     */
    public string $path;
    public function __construct(string $path)
    {
        $this->path = $path;
    }
}
