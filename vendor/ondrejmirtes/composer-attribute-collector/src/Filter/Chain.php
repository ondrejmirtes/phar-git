<?php

namespace _PHPStan_checksum\olvlvl\ComposerAttributeCollector\Filter;

use _PHPStan_checksum\Composer\IO\IOInterface;
use _PHPStan_checksum\olvlvl\ComposerAttributeCollector\Filter;
final class Chain implements Filter
{
    /**
     * @var iterable<Filter>
     */
    private iterable $filters;
    /**
     * @param iterable<Filter> $filters
     */
    public function __construct(iterable $filters)
    {
        $this->filters = $filters;
    }
    public function filter(string $filepath, string $class, IOInterface $io): bool
    {
        foreach ($this->filters as $filter) {
            if ($filter->filter($filepath, $class, $io) === \false) {
                return \false;
            }
        }
        return \true;
    }
}
