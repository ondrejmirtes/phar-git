<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PHPStan\Collectors\CollectedData;
use PHPStan\Dependency\RootExportedNode;
/**
 * @phpstan-type LinesToIgnore = array<string, array<int, non-empty-list<string>|null>>
 * @phpstan-import-type CollectorData from CollectedData
 */
final class FileAnalyserResult
{
    /**
     * @var list<Error>
     */
    private array $errors;
    /**
     * @var list<Error>
     */
    private array $filteredPhpErrors;
    /**
     * @var list<Error>
     */
    private array $allPhpErrors;
    /**
     * @var list<Error>
     */
    private array $locallyIgnoredErrors;
    /**
     * @var CollectorData
     */
    private array $collectedData;
    /**
     * @var list<string>
     */
    private array $dependencies;
    /**
     * @var list<string>
     */
    private array $usedTraitDependencies;
    /**
     * @var list<RootExportedNode>
     */
    private array $exportedNodes;
    /**
     * @var LinesToIgnore
     */
    private array $linesToIgnore;
    /**
     * @var LinesToIgnore
     */
    private array $unmatchedLineIgnores;
    /**
     * @param list<Error> $errors
     * @param list<Error> $filteredPhpErrors
     * @param list<Error> $allPhpErrors
     * @param list<Error> $locallyIgnoredErrors
     * @param CollectorData $collectedData
     * @param list<string> $dependencies
     * @param list<string> $usedTraitDependencies
     * @param list<RootExportedNode> $exportedNodes
     * @param LinesToIgnore $linesToIgnore
     * @param LinesToIgnore $unmatchedLineIgnores
     */
    public function __construct(array $errors, array $filteredPhpErrors, array $allPhpErrors, array $locallyIgnoredErrors, array $collectedData, array $dependencies, array $usedTraitDependencies, array $exportedNodes, array $linesToIgnore, array $unmatchedLineIgnores)
    {
        $this->errors = $errors;
        $this->filteredPhpErrors = $filteredPhpErrors;
        $this->allPhpErrors = $allPhpErrors;
        $this->locallyIgnoredErrors = $locallyIgnoredErrors;
        $this->collectedData = $collectedData;
        $this->dependencies = $dependencies;
        $this->usedTraitDependencies = $usedTraitDependencies;
        $this->exportedNodes = $exportedNodes;
        $this->linesToIgnore = $linesToIgnore;
        $this->unmatchedLineIgnores = $unmatchedLineIgnores;
    }
    /**
     * @return list<Error>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    /**
     * @return list<Error>
     */
    public function getFilteredPhpErrors(): array
    {
        return $this->filteredPhpErrors;
    }
    /**
     * @return list<Error>
     */
    public function getAllPhpErrors(): array
    {
        return $this->allPhpErrors;
    }
    /**
     * @return list<Error>
     */
    public function getLocallyIgnoredErrors(): array
    {
        return $this->locallyIgnoredErrors;
    }
    /**
     * @return CollectorData
     */
    public function getCollectedData(): array
    {
        return $this->collectedData;
    }
    /**
     * @return list<string>
     */
    public function getDependencies(): array
    {
        return $this->dependencies;
    }
    /**
     * @return list<string>
     */
    public function getUsedTraitDependencies(): array
    {
        return $this->usedTraitDependencies;
    }
    /**
     * @return list<RootExportedNode>
     */
    public function getExportedNodes(): array
    {
        return $this->exportedNodes;
    }
    /**
     * @return LinesToIgnore
     */
    public function getLinesToIgnore(): array
    {
        return $this->linesToIgnore;
    }
    /**
     * @return LinesToIgnore
     */
    public function getUnmatchedLineIgnores(): array
    {
        return $this->unmatchedLineIgnores;
    }
}
