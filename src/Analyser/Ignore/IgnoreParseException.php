<?php

declare (strict_types=1);
namespace PHPStan\Analyser\Ignore;

use Exception;
final class IgnoreParseException extends Exception
{
    private int $phpDocLine;
    public function __construct(string $message, int $phpDocLine)
    {
        $this->phpDocLine = $phpDocLine;
        parent::__construct($message);
    }
    public function getPhpDocLine() : int
    {
        return $this->phpDocLine;
    }
}
