<?php

declare (strict_types=1);
namespace PHPStan\DependencyInjection;

use Exception;
use function implode;
final class InvalidExcludePathsException extends Exception
{
    /**
     * @var string[]
     */
    private array $errors;
    /**
     * @var array{analyse?: list<string>, analyseAndScan?: list<string>}
     */
    private array $suggestOptional;
    /**
     * @param string[] $errors
     * @param array{analyse?: list<string>, analyseAndScan?: list<string>} $suggestOptional
     */
    public function __construct(array $errors, array $suggestOptional)
    {
        $this->errors = $errors;
        $this->suggestOptional = $suggestOptional;
        parent::__construct(implode("\n", $this->errors));
    }
    /**
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
    /**
     * @return array{analyse?: list<string>, analyseAndScan?: list<string>}
     */
    public function getSuggestOptional(): array
    {
        return $this->suggestOptional;
    }
}
