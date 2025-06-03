<?php

declare (strict_types=1);
namespace PHPStan\File;

use function array_key_exists;
use function array_merge;
use function array_unique;
use function array_values;
final class FileExcluderFactory
{
    private \PHPStan\File\FileExcluderRawFactory $fileExcluderRawFactory;
    /**
     * @var array{analyse?: array<int, string>, analyseAndScan?: array<int, string>}
     */
    private array $excludePaths;
    /**
     * @param array{analyse?: array<int, string>, analyseAndScan?: array<int, string>} $excludePaths
     */
    public function __construct(\PHPStan\File\FileExcluderRawFactory $fileExcluderRawFactory, array $excludePaths)
    {
        $this->fileExcluderRawFactory = $fileExcluderRawFactory;
        $this->excludePaths = $excludePaths;
    }
    public function createAnalyseFileExcluder() : \PHPStan\File\FileExcluder
    {
        $paths = [];
        if (array_key_exists('analyse', $this->excludePaths)) {
            $paths = $this->excludePaths['analyse'];
        }
        if (array_key_exists('analyseAndScan', $this->excludePaths)) {
            $paths = array_merge($paths, $this->excludePaths['analyseAndScan']);
        }
        return $this->fileExcluderRawFactory->create(array_values(array_unique($paths)));
    }
    public function createScanFileExcluder() : \PHPStan\File\FileExcluder
    {
        $paths = [];
        if (array_key_exists('analyseAndScan', $this->excludePaths)) {
            $paths = $this->excludePaths['analyseAndScan'];
        }
        return $this->fileExcluderRawFactory->create(array_values(array_unique($paths)));
    }
}
