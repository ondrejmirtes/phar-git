<?php

declare (strict_types=1);
namespace PHPStan\Command;

use PHPStan\Analyser\Error;
use PHPStan\Analyser\InternalError;
use PHPStan\Collectors\CollectedData;
use function count;
use function usort;
/**
 * @api
 */
final class AnalysisResult
{
    /**
     * @var list<string>
     */
    private array $notFileSpecificErrors;
    /**
     * @var list<InternalError>
     */
    private array $internalErrors;
    /**
     * @var list<string>
     */
    private array $warnings;
    /**
     * @var list<CollectedData>
     */
    private array $collectedData;
    private bool $defaultLevelUsed;
    private ?string $projectConfigFile;
    private bool $savedResultCache;
    private int $peakMemoryUsageBytes;
    private bool $isResultCacheUsed;
    /**
     * @var array<string, string>
     */
    private array $changedProjectExtensionFilesOutsideOfAnalysedPaths;
    /** @var list<Error> sorted by their file name, line number and message */
    private array $fileSpecificErrors;
    /**
     * @param list<Error> $fileSpecificErrors
     * @param list<string> $notFileSpecificErrors
     * @param list<InternalError> $internalErrors
     * @param list<string> $warnings
     * @param list<CollectedData> $collectedData
     * @param array<string, string> $changedProjectExtensionFilesOutsideOfAnalysedPaths
     */
    public function __construct(array $fileSpecificErrors, array $notFileSpecificErrors, array $internalErrors, array $warnings, array $collectedData, bool $defaultLevelUsed, ?string $projectConfigFile, bool $savedResultCache, int $peakMemoryUsageBytes, bool $isResultCacheUsed, array $changedProjectExtensionFilesOutsideOfAnalysedPaths)
    {
        $this->notFileSpecificErrors = $notFileSpecificErrors;
        $this->internalErrors = $internalErrors;
        $this->warnings = $warnings;
        $this->collectedData = $collectedData;
        $this->defaultLevelUsed = $defaultLevelUsed;
        $this->projectConfigFile = $projectConfigFile;
        $this->savedResultCache = $savedResultCache;
        $this->peakMemoryUsageBytes = $peakMemoryUsageBytes;
        $this->isResultCacheUsed = $isResultCacheUsed;
        $this->changedProjectExtensionFilesOutsideOfAnalysedPaths = $changedProjectExtensionFilesOutsideOfAnalysedPaths;
        usort($fileSpecificErrors, static fn(Error $a, Error $b): int => [$a->getFile(), $a->getLine(), $a->getMessage()] <=> [$b->getFile(), $b->getLine(), $b->getMessage()]);
        $this->fileSpecificErrors = $fileSpecificErrors;
    }
    public function hasErrors(): bool
    {
        return $this->getTotalErrorsCount() > 0;
    }
    public function getTotalErrorsCount(): int
    {
        return count($this->fileSpecificErrors) + count($this->notFileSpecificErrors);
    }
    /**
     * @return list<Error> sorted by their file name, line number and message
     */
    public function getFileSpecificErrors(): array
    {
        return $this->fileSpecificErrors;
    }
    /**
     * @return list<string>
     */
    public function getNotFileSpecificErrors(): array
    {
        return $this->notFileSpecificErrors;
    }
    /**
     * @return list<InternalError>
     */
    public function getInternalErrorObjects(): array
    {
        return $this->internalErrors;
    }
    /**
     * @return list<string>
     */
    public function getWarnings(): array
    {
        return $this->warnings;
    }
    public function hasWarnings(): bool
    {
        return count($this->warnings) > 0;
    }
    /**
     * @return list<CollectedData>
     */
    public function getCollectedData(): array
    {
        return $this->collectedData;
    }
    public function isDefaultLevelUsed(): bool
    {
        return $this->defaultLevelUsed;
    }
    public function getProjectConfigFile(): ?string
    {
        return $this->projectConfigFile;
    }
    public function hasInternalErrors(): bool
    {
        return count($this->internalErrors) > 0;
    }
    public function isResultCacheSaved(): bool
    {
        return $this->savedResultCache;
    }
    public function getPeakMemoryUsageBytes(): int
    {
        return $this->peakMemoryUsageBytes;
    }
    public function isResultCacheUsed(): bool
    {
        return $this->isResultCacheUsed;
    }
    /**
     * @return array<string, string>
     */
    public function getChangedProjectExtensionFilesOutsideOfAnalysedPaths(): array
    {
        return $this->changedProjectExtensionFilesOutsideOfAnalysedPaths;
    }
}
