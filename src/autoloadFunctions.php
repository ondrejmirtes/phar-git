<?php

declare (strict_types=1);
namespace PHPStan;

/**
 * @return array<int, callable(string): void>
 */
function autoloadFunctions() : array
{
    return $GLOBALS['__phpstanAutoloadFunctions'] ?? [];
}
