<?php

declare (strict_types=1);
namespace PHPStan\Analyser\Ignore;

use _PHPStan_checksum\Nette\Utils\Json;
use _PHPStan_checksum\Nette\Utils\JsonException;
use PHPStan\File\FileHelper;
use PHPStan\ShouldNotHappenException;
use function array_key_exists;
use function array_values;
use function is_array;
use function is_file;
use function sprintf;
final class IgnoredErrorHelper
{
    private FileHelper $fileHelper;
    /**
     * @var (string|mixed[])[]
     */
    private array $ignoreErrors;
    private bool $reportUnmatchedIgnoredErrors;
    /**
     * @param (string|mixed[])[] $ignoreErrors
     */
    public function __construct(FileHelper $fileHelper, array $ignoreErrors, bool $reportUnmatchedIgnoredErrors)
    {
        $this->fileHelper = $fileHelper;
        $this->ignoreErrors = $ignoreErrors;
        $this->reportUnmatchedIgnoredErrors = $reportUnmatchedIgnoredErrors;
    }
    public function initialize(): \PHPStan\Analyser\Ignore\IgnoredErrorHelperResult
    {
        $otherIgnoreErrors = [];
        $ignoreErrorsByFile = [];
        $errors = [];
        $expandedIgnoreErrors = [];
        foreach ($this->ignoreErrors as $ignoreError) {
            if (is_array($ignoreError)) {
                if (!isset($ignoreError['message']) && !isset($ignoreError['messages']) && !isset($ignoreError['identifier'])) {
                    $errors[] = sprintf('Ignored error %s is missing a message or an identifier.', Json::encode($ignoreError));
                    continue;
                }
                if (isset($ignoreError['messages'])) {
                    foreach ($ignoreError['messages'] as $message) {
                        $expandedIgnoreError = $ignoreError;
                        unset($expandedIgnoreError['messages']);
                        $expandedIgnoreError['message'] = $message;
                        $expandedIgnoreErrors[] = $expandedIgnoreError;
                    }
                } else {
                    $expandedIgnoreErrors[] = $ignoreError;
                }
            } else {
                $expandedIgnoreErrors[] = $ignoreError;
            }
        }
        $uniquedExpandedIgnoreErrors = [];
        foreach ($expandedIgnoreErrors as $ignoreError) {
            if (!isset($ignoreError['message']) && !isset($ignoreError['identifier'])) {
                $uniquedExpandedIgnoreErrors[] = $ignoreError;
                continue;
            }
            if (!isset($ignoreError['path'])) {
                $uniquedExpandedIgnoreErrors[] = $ignoreError;
                continue;
            }
            $key = $ignoreError['path'];
            if (isset($ignoreError['message'])) {
                $key = sprintf("%s\n%s", $key, $ignoreError['message']);
            }
            if (isset($ignoreError['identifier'])) {
                $key = sprintf("%s\n%s", $key, $ignoreError['identifier']);
            }
            if ($key === '') {
                throw new ShouldNotHappenException();
            }
            if (!array_key_exists($key, $uniquedExpandedIgnoreErrors)) {
                $uniquedExpandedIgnoreErrors[$key] = $ignoreError;
                continue;
            }
            $uniquedExpandedIgnoreErrors[$key] = ['message' => $ignoreError['message'] ?? null, 'path' => $ignoreError['path'], 'identifier' => $ignoreError['identifier'] ?? null, 'count' => ($uniquedExpandedIgnoreErrors[$key]['count'] ?? 1) + ($ignoreError['count'] ?? 1), 'reportUnmatched' => ($uniquedExpandedIgnoreErrors[$key]['reportUnmatched'] ?? $this->reportUnmatchedIgnoredErrors) || ($ignoreError['reportUnmatched'] ?? $this->reportUnmatchedIgnoredErrors)];
        }
        $expandedIgnoreErrors = array_values($uniquedExpandedIgnoreErrors);
        foreach ($expandedIgnoreErrors as $i => $ignoreError) {
            $ignoreErrorEntry = ['index' => $i, 'ignoreError' => $ignoreError];
            try {
                if (is_array($ignoreError)) {
                    if (!isset($ignoreError['message']) && !isset($ignoreError['identifier'])) {
                        $errors[] = sprintf('Ignored error %s is missing a message or an identifier.', Json::encode($ignoreError));
                        continue;
                    }
                    if (!isset($ignoreError['path'])) {
                        $otherIgnoreErrors[] = $ignoreErrorEntry;
                    } elseif (@is_file($ignoreError['path'])) {
                        $normalizedPath = $this->fileHelper->normalizePath($ignoreError['path']);
                        $ignoreError['path'] = $normalizedPath;
                        $ignoreErrorsByFile[$normalizedPath][] = $ignoreErrorEntry;
                        $ignoreError['realPath'] = $normalizedPath;
                        $expandedIgnoreErrors[$i] = $ignoreError;
                    } else {
                        $otherIgnoreErrors[] = $ignoreErrorEntry;
                    }
                } else {
                    $otherIgnoreErrors[] = $ignoreErrorEntry;
                }
            } catch (JsonException $e) {
                $errors[] = $e->getMessage();
            }
        }
        return new \PHPStan\Analyser\Ignore\IgnoredErrorHelperResult($this->fileHelper, $errors, $otherIgnoreErrors, $ignoreErrorsByFile, $expandedIgnoreErrors, $this->reportUnmatchedIgnoredErrors);
    }
}
