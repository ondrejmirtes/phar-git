<?php

declare (strict_types=1);
namespace PHPStan\BetterReflection\SourceLocator\Type\Composer\Factory\Exception;

use UnexpectedValueException;
use function sprintf;
final class MissingInstalledJson extends UnexpectedValueException implements \PHPStan\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\Exception
{
    public static function inProjectPath(string $path): self
    {
        return new self(sprintf('Could not locate a "composer/installed.json" file in "%s"', $path));
    }
}
