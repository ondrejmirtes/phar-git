<?php

declare (strict_types=1);
namespace PHPStan\File;

use function file_get_contents;
use function stream_resolve_include_path;
final class FileReader
{
    /**
     * @throws CouldNotReadFileException
     */
    public static function read(string $fileName): string
    {
        $path = $fileName;
        $contents = @file_get_contents($path);
        if ($contents === \false) {
            $path = stream_resolve_include_path($fileName);
            if ($path === \false) {
                throw new \PHPStan\File\CouldNotReadFileException($fileName);
            }
            $contents = @file_get_contents($path);
        }
        if ($contents === \false) {
            throw new \PHPStan\File\CouldNotReadFileException($fileName);
        }
        return $contents;
    }
}
