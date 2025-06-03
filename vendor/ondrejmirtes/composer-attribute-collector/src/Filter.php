<?php

namespace _PHPStan_checksum\olvlvl\ComposerAttributeCollector;

use _PHPStan_checksum\Composer\IO\IOInterface;
/**
 * @internal
 */
interface Filter
{
    /**
     * @param class-string $class
     */
    public function filter(string $filepath, string $class, IOInterface $io): bool;
}
