<?php

declare (strict_types=1);
namespace PHPStan\Rules\RuleErrors;

use PHPStan\Rules\FileRuleError;
use PHPStan\Rules\RuleError;
/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
final class RuleError5 implements RuleError, FileRuleError
{
    public string $message;
    public string $file;
    public string $fileDescription;
    public function getMessage(): string
    {
        return $this->message;
    }
    public function getFile(): string
    {
        return $this->file;
    }
    public function getFileDescription(): string
    {
        return $this->fileDescription;
    }
}
