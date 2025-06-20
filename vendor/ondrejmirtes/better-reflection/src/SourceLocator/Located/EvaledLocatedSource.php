<?php

declare (strict_types=1);
namespace PHPStan\BetterReflection\SourceLocator\Located;

/**
 * @internal
 *
 * @psalm-immutable
 */
class EvaledLocatedSource extends \PHPStan\BetterReflection\SourceLocator\Located\LocatedSource
{
    public function isEvaled(): bool
    {
        return \true;
    }
}
