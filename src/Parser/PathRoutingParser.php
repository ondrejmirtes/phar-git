<?php

declare (strict_types=1);
namespace PHPStan\Parser;

use PHPStan\File\FileHelper;
use function array_fill_keys;
use function array_slice;
use function count;
use function explode;
use function implode;
use function is_link;
use function realpath;
use function str_contains;
use const DIRECTORY_SEPARATOR;
final class PathRoutingParser implements \PHPStan\Parser\Parser
{
    private FileHelper $fileHelper;
    private \PHPStan\Parser\Parser $currentPhpVersionRichParser;
    private \PHPStan\Parser\Parser $currentPhpVersionSimpleParser;
    private \PHPStan\Parser\Parser $php8Parser;
    private ?string $singleReflectionFile;
    /** @var bool[] filePath(string) => bool(true) */
    private array $analysedFiles = [];
    public function __construct(FileHelper $fileHelper, \PHPStan\Parser\Parser $currentPhpVersionRichParser, \PHPStan\Parser\Parser $currentPhpVersionSimpleParser, \PHPStan\Parser\Parser $php8Parser, ?string $singleReflectionFile)
    {
        $this->fileHelper = $fileHelper;
        $this->currentPhpVersionRichParser = $currentPhpVersionRichParser;
        $this->currentPhpVersionSimpleParser = $currentPhpVersionSimpleParser;
        $this->php8Parser = $php8Parser;
        $this->singleReflectionFile = $singleReflectionFile !== null ? $fileHelper->normalizePath($singleReflectionFile) : null;
    }
    /**
     * @param string[] $files
     */
    public function setAnalysedFiles(array $files) : void
    {
        $this->analysedFiles = array_fill_keys($files, \true);
    }
    public function parseFile(string $file) : array
    {
        $normalizedPath = $this->fileHelper->normalizePath($file, '/');
        if (str_contains($normalizedPath, 'vendor/jetbrains/phpstorm-stubs')) {
            return $this->php8Parser->parseFile($file);
        }
        if (str_contains($normalizedPath, 'vendor/phpstan/php-8-stubs/stubs')) {
            return $this->php8Parser->parseFile($file);
        }
        $file = $this->fileHelper->normalizePath($file);
        if (!isset($this->analysedFiles[$file]) && $file !== $this->singleReflectionFile) {
            // check symlinked file that still might be in analysedFiles
            $pathParts = explode(DIRECTORY_SEPARATOR, $file);
            for ($i = count($pathParts); $i > 1; $i--) {
                $joinedPartOfPath = implode(DIRECTORY_SEPARATOR, array_slice($pathParts, 0, $i));
                if (!@is_link($joinedPartOfPath)) {
                    continue;
                }
                $realFilePath = realpath($file);
                if ($realFilePath !== \false) {
                    $normalizedRealFilePath = $this->fileHelper->normalizePath($realFilePath);
                    if (isset($this->analysedFiles[$normalizedRealFilePath])) {
                        return $this->currentPhpVersionRichParser->parseFile($file);
                    }
                }
                break;
            }
            return $this->currentPhpVersionSimpleParser->parseFile($file);
        }
        return $this->currentPhpVersionRichParser->parseFile($file);
    }
    public function parseString(string $sourceCode) : array
    {
        return $this->currentPhpVersionSimpleParser->parseString($sourceCode);
    }
}
