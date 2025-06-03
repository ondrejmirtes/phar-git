<?php

declare (strict_types=1);
namespace PHPStan\File;

final class NullRelativePathHelper implements \PHPStan\File\RelativePathHelper
{
    public function getRelativePath(string $filename) : string
    {
        return $filename;
    }
}
