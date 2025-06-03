<?php

declare (strict_types=1);
namespace PHPStan\DependencyInjection;

use Exception;
use function implode;
use function sprintf;
final class DuplicateIncludedFilesException extends Exception
{
    /**
     * @var string[]
     */
    private array $files;
    /**
     * @param string[] $files
     */
    public function __construct(array $files)
    {
        $this->files = $files;
        parent::__construct(sprintf('These files are included multiple times: %s', implode(', ', $this->files)));
    }
    /**
     * @return string[]
     */
    public function getFiles(): array
    {
        return $this->files;
    }
}
