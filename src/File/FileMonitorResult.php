<?php

declare (strict_types=1);
namespace PHPStan\File;

use function count;
final class FileMonitorResult
{
    /**
     * @var string[]
     */
    private array $newFiles;
    /**
     * @var string[]
     */
    private array $changedFiles;
    /**
     * @var string[]
     */
    private array $deletedFiles;
    /**
     * @param string[] $newFiles
     * @param string[] $changedFiles
     * @param string[] $deletedFiles
     */
    public function __construct(array $newFiles, array $changedFiles, array $deletedFiles)
    {
        $this->newFiles = $newFiles;
        $this->changedFiles = $changedFiles;
        $this->deletedFiles = $deletedFiles;
    }
    /**
     * @return string[]
     */
    public function getChangedFiles() : array
    {
        return $this->changedFiles;
    }
    public function hasAnyChanges() : bool
    {
        return count($this->newFiles) > 0 || count($this->changedFiles) > 0 || count($this->deletedFiles) > 0;
    }
}
