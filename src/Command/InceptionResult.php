<?php

declare (strict_types=1);
namespace PHPStan\Command;

use PHPStan\DependencyInjection\Container;
use PHPStan\File\PathNotFoundException;
use PHPStan\Internal\BytesHelper;
use function floor;
use function implode;
use function max;
use function memory_get_peak_usage;
use function microtime;
use function round;
use function sprintf;
final class InceptionResult
{
    private \PHPStan\Command\Output $stdOutput;
    private \PHPStan\Command\Output $errorOutput;
    private Container $container;
    private bool $isDefaultLevelUsed;
    private ?string $projectConfigFile;
    /**
     * @var mixed[]|null
     */
    private ?array $projectConfigArray;
    private ?string $generateBaselineFile;
    private ?string $editorModeTmpFile;
    private ?string $editorModeInsteadOfFile;
    /** @var callable(): (array{string[], bool}) */
    private $filesCallback;
    /**
     * @param callable(): (array{string[], bool}) $filesCallback
     * @param mixed[]|null $projectConfigArray
     */
    public function __construct(callable $filesCallback, \PHPStan\Command\Output $stdOutput, \PHPStan\Command\Output $errorOutput, Container $container, bool $isDefaultLevelUsed, ?string $projectConfigFile, ?array $projectConfigArray, ?string $generateBaselineFile, ?string $editorModeTmpFile, ?string $editorModeInsteadOfFile)
    {
        $this->stdOutput = $stdOutput;
        $this->errorOutput = $errorOutput;
        $this->container = $container;
        $this->isDefaultLevelUsed = $isDefaultLevelUsed;
        $this->projectConfigFile = $projectConfigFile;
        $this->projectConfigArray = $projectConfigArray;
        $this->generateBaselineFile = $generateBaselineFile;
        $this->editorModeTmpFile = $editorModeTmpFile;
        $this->editorModeInsteadOfFile = $editorModeInsteadOfFile;
        $this->filesCallback = $filesCallback;
    }
    /**
     * @throws InceptionNotSuccessfulException
     * @throws PathNotFoundException
     * @return array{string[], bool}
     */
    public function getFiles(): array
    {
        $callback = $this->filesCallback;
        /** @throws InceptionNotSuccessfulException|PathNotFoundException */
        return $callback();
    }
    public function getStdOutput(): \PHPStan\Command\Output
    {
        return $this->stdOutput;
    }
    public function getErrorOutput(): \PHPStan\Command\Output
    {
        return $this->errorOutput;
    }
    public function getContainer(): Container
    {
        return $this->container;
    }
    public function isDefaultLevelUsed(): bool
    {
        return $this->isDefaultLevelUsed;
    }
    public function getProjectConfigFile(): ?string
    {
        return $this->projectConfigFile;
    }
    /**
     * @return mixed[]|null
     */
    public function getProjectConfigArray(): ?array
    {
        return $this->projectConfigArray;
    }
    public function getGenerateBaselineFile(): ?string
    {
        return $this->generateBaselineFile;
    }
    public function getEditorModeTmpFile(): ?string
    {
        return $this->editorModeTmpFile;
    }
    public function getEditorModeInsteadOfFile(): ?string
    {
        return $this->editorModeInsteadOfFile;
    }
    public function handleReturn(int $exitCode, ?int $peakMemoryUsageBytes, float $analysisStartTime): int
    {
        if ($this->getErrorOutput()->isVerbose()) {
            $elapsedTime = round(microtime(\true) - $analysisStartTime, 2);
            if ($elapsedTime < 10) {
                $elapsedTimeString = sprintf('%.2f seconds', $elapsedTime);
            } else {
                $elapsedTimeString = $this->formatDuration((int) $elapsedTime);
            }
            $this->getErrorOutput()->writeLineFormatted(sprintf('Elapsed time: %s', $elapsedTimeString));
        }
        if ($peakMemoryUsageBytes !== null && $this->getErrorOutput()->isVerbose()) {
            $this->getErrorOutput()->writeLineFormatted(sprintf('Used memory: %s', BytesHelper::bytes(max(memory_get_peak_usage(\true), $peakMemoryUsageBytes))));
        }
        return $exitCode;
    }
    private function formatDuration(int $seconds): string
    {
        $minutes = (int) floor($seconds / 60);
        $remainingSeconds = $seconds % 60;
        $result = [];
        if ($minutes > 0) {
            $result[] = $minutes . ' minute' . ($minutes > 1 ? 's' : '');
        }
        if ($remainingSeconds > 0) {
            $result[] = $remainingSeconds . ' second' . ($remainingSeconds > 1 ? 's' : '');
        }
        return implode(' ', $result);
    }
}
