<?php

declare (strict_types=1);
namespace PHPStan\File;

use function str_starts_with;
use function strlen;
use function substr;
final class SystemAgnosticSimpleRelativePathHelper implements \PHPStan\File\RelativePathHelper
{
    private \PHPStan\File\FileHelper $fileHelper;
    public function __construct(\PHPStan\File\FileHelper $fileHelper)
    {
        $this->fileHelper = $fileHelper;
    }
    public function getRelativePath(string $filename) : string
    {
        $cwd = $this->fileHelper->getWorkingDirectory();
        if ($cwd !== '' && str_starts_with($filename, $cwd)) {
            return substr($filename, strlen($cwd) + 1);
        }
        return $filename;
    }
}
