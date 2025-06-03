<?php

declare (strict_types=1);
namespace PHPStan\Analyser\ResultCache;

use PHPStan\Analyser\Error;
use PHPStan\Analyser\FileAnalyserResult;
use PHPStan\Collectors\CollectedData;
use PHPStan\Dependency\RootExportedNode;
/**
 * @phpstan-import-type LinesToIgnore from FileAnalyserResult
 * @phpstan-import-type CollectorData from CollectedData
 */
final class ResultCache
{
    /**
     * @var string[]
     */
    private array $filesToAnalyse;
    private bool $fullAnalysis;
    private int $lastFullAnalysisTime;
    /**
     * @var mixed[]
     */
    private array $meta;
    /**
     * @var array<string, list<Error>>
     */
    private array $errors;
    /**
     * @var array<string, list<Error>>
     */
    private array $locallyIgnoredErrors;
    /**
     * @var array<string, LinesToIgnore>
     */
    private array $linesToIgnore;
    /**
     * @var array<string, LinesToIgnore>
     */
    private array $unmatchedLineIgnores;
    /**
     * @var CollectorData
     */
    private array $collectedData;
    /**
     * @var array<string, array<string>>
     */
    private array $dependencies;
    /**
     * @var array<string, array<string>>
     */
    private array $usedTraitDependencies;
    /**
     * @var array<string, array<RootExportedNode>>
     */
    private array $exportedNodes;
    /**
     * @var array<string, array{string, bool, string}>
     */
    private array $projectExtensionFiles;
    /**
     * @var array<string, string>
     */
    private array $currentFileHashes;
    /**
     * @param string[] $filesToAnalyse
     * @param mixed[] $meta
     * @param array<string, list<Error>> $errors
     * @param array<string, list<Error>> $locallyIgnoredErrors
     * @param array<string, LinesToIgnore> $linesToIgnore
     * @param array<string, LinesToIgnore> $unmatchedLineIgnores
     * @param CollectorData $collectedData
     * @param array<string, array<string>> $dependencies
     * @param array<string, array<string>> $usedTraitDependencies
     * @param array<string, array<RootExportedNode>> $exportedNodes
     * @param array<string, array{string, bool, string}> $projectExtensionFiles
     * @param array<string, string> $currentFileHashes
     */
    public function __construct(array $filesToAnalyse, bool $fullAnalysis, int $lastFullAnalysisTime, array $meta, array $errors, array $locallyIgnoredErrors, array $linesToIgnore, array $unmatchedLineIgnores, array $collectedData, array $dependencies, array $usedTraitDependencies, array $exportedNodes, array $projectExtensionFiles, array $currentFileHashes)
    {
        $this->filesToAnalyse = $filesToAnalyse;
        $this->fullAnalysis = $fullAnalysis;
        $this->lastFullAnalysisTime = $lastFullAnalysisTime;
        $this->meta = $meta;
        $this->errors = $errors;
        $this->locallyIgnoredErrors = $locallyIgnoredErrors;
        $this->linesToIgnore = $linesToIgnore;
        $this->unmatchedLineIgnores = $unmatchedLineIgnores;
        $this->collectedData = $collectedData;
        $this->dependencies = $dependencies;
        $this->usedTraitDependencies = $usedTraitDependencies;
        $this->exportedNodes = $exportedNodes;
        $this->projectExtensionFiles = $projectExtensionFiles;
        $this->currentFileHashes = $currentFileHashes;
    }
    /**
     * @return string[]
     */
    public function getFilesToAnalyse(): array
    {
        return $this->filesToAnalyse;
    }
    public function isFullAnalysis(): bool
    {
        return $this->fullAnalysis;
    }
    public function getLastFullAnalysisTime(): int
    {
        return $this->lastFullAnalysisTime;
    }
    /**
     * @return mixed[]
     */
    public function getMeta(): array
    {
        return $this->meta;
    }
    /**
     * @return array<string, list<Error>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    /**
     * @return array<string, list<Error>>
     */
    public function getLocallyIgnoredErrors(): array
    {
        return $this->locallyIgnoredErrors;
    }
    /**
     * @return array<string, LinesToIgnore>
     */
    public function getLinesToIgnore(): array
    {
        return $this->linesToIgnore;
    }
    /**
     * @return array<string, LinesToIgnore>
     */
    public function getUnmatchedLineIgnores(): array
    {
        return $this->unmatchedLineIgnores;
    }
    /**
     * @return CollectorData
     */
    public function getCollectedData(): array
    {
        return $this->collectedData;
    }
    /**
     * @return array<string, array<string>>
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }
    /**
     * @return array<string, array<string>>
     */
    public function getUsedTraitDependencies(): array
    {
        return $this->usedTraitDependencies;
    }
    /**
     * @return array<string, array<RootExportedNode>>
     */
    public function getExportedNodes(): array
    {
        return $this->exportedNodes;
    }
    /**
     * @return array<string, array{string, bool, string}>
     */
    public function getProjectExtensionFiles(): array
    {
        return $this->projectExtensionFiles;
    }
    /**
     * @return array<string, string>
     */
    public function getCurrentFileHashes(): array
    {
        return $this->currentFileHashes;
    }
}
