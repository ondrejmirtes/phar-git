<?php

declare (strict_types=1);
namespace PHPStan\BetterReflection\SourceLocator\Type;

use Iterator;
use PHPStan\BetterReflection\Identifier\Identifier;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Reflection;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\Exception\InvalidFileInfo;
use PHPStan\BetterReflection\SourceLocator\Exception\InvalidFileLocation;
use SplFileInfo;
use function array_filter;
use function array_map;
use function array_values;
use function iterator_to_array;
/**
 * This source locator loads all php files from \FileSystemIterator
 */
class FileIteratorSourceLocator implements \PHPStan\BetterReflection\SourceLocator\Type\SourceLocator
{
    private Locator $astLocator;
    /**
     * @var \PHPStan\BetterReflection\SourceLocator\Type\AggregateSourceLocator|null
     */
    private $aggregateSourceLocator = null;
    /** @var Iterator<SplFileInfo> */
    private Iterator $fileSystemIterator;
    /**
     * @param Iterator<SplFileInfo> $fileInfoIterator note: only SplFileInfo allowed in this iterator
     *
     * @throws InvalidFileInfo In case of iterator not contains only SplFileInfo.
     */
    public function __construct(Iterator $fileInfoIterator, Locator $astLocator)
    {
        $this->astLocator = $astLocator;
        foreach ($fileInfoIterator as $fileInfo) {
            /** @phpstan-ignore instanceof.alwaysTrue */
            if (!$fileInfo instanceof SplFileInfo) {
                throw InvalidFileInfo::fromNonSplFileInfo($fileInfo);
            }
        }
        $this->fileSystemIterator = $fileInfoIterator;
    }
    /** @throws InvalidFileLocation */
    private function getAggregatedSourceLocator(): \PHPStan\BetterReflection\SourceLocator\Type\AggregateSourceLocator
    {
        // @infection-ignore-all Coalesce: There's no difference, it's just optimization
        return $this->aggregateSourceLocator ?? $this->aggregateSourceLocator = new \PHPStan\BetterReflection\SourceLocator\Type\AggregateSourceLocator(array_values(array_filter(array_map(function (SplFileInfo $item): ?\PHPStan\BetterReflection\SourceLocator\Type\SingleFileSourceLocator {
            if (!($item->isFile() && $item->getExtension() === 'php')) {
                return null;
            }
            return new \PHPStan\BetterReflection\SourceLocator\Type\SingleFileSourceLocator($item->getRealPath(), $this->astLocator);
        }, iterator_to_array($this->fileSystemIterator)))));
    }
    /**
     * {@inheritDoc}
     *
     * @throws InvalidFileLocation
     */
    public function locateIdentifier(Reflector $reflector, Identifier $identifier): ?\PHPStan\BetterReflection\Reflection\Reflection
    {
        return $this->getAggregatedSourceLocator()->locateIdentifier($reflector, $identifier);
    }
    /**
     * {@inheritDoc}
     *
     * @throws InvalidFileLocation
     */
    public function locateIdentifiersByType(Reflector $reflector, IdentifierType $identifierType): array
    {
        return $this->getAggregatedSourceLocator()->locateIdentifiersByType($reflector, $identifierType);
    }
}
