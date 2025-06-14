<?php

declare (strict_types=1);
namespace PHPStan\File;

use function str_replace;
use function str_starts_with;
use function strlen;
use function substr;
final class SimpleRelativePathHelper implements \PHPStan\File\RelativePathHelper
{
    private string $currentWorkingDirectory;
    public function __construct(string $currentWorkingDirectory)
    {
        $this->currentWorkingDirectory = $currentWorkingDirectory;
    }
    public function getRelativePath(string $filename): string
    {
        if ($this->currentWorkingDirectory !== '' && str_starts_with($filename, $this->currentWorkingDirectory)) {
            return str_replace('\\', '/', substr($filename, strlen($this->currentWorkingDirectory) + 1));
        }
        return str_replace('\\', '/', $filename);
    }
}
