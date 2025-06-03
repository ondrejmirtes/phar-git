<?php

declare (strict_types=1);
namespace PHPStan\BetterReflection\SourceLocator\Located;

/**
 * @internal
 *
 * @psalm-immutable
 */
class AnonymousLocatedSource extends \PHPStan\BetterReflection\SourceLocator\Located\LocatedSource
{
    public function __construct(string $source, string $filename)
    {
        parent::__construct($source, null, $filename);
    }
}
