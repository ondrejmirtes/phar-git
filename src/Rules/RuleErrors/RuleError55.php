<?php

declare (strict_types=1);
namespace PHPStan\Rules\RuleErrors;

use PHPStan\Rules\FileRuleError;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\LineRuleError;
use PHPStan\Rules\MetadataRuleError;
use PHPStan\Rules\RuleError;
/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
final class RuleError55 implements RuleError, LineRuleError, FileRuleError, IdentifierRuleError, MetadataRuleError
{
    public string $message;
    public int $line;
    public string $file;
    public string $fileDescription;
    public string $identifier;
    /** @var mixed[] */
    public array $metadata;
    public function getMessage(): string
    {
        return $this->message;
    }
    public function getLine(): int
    {
        return $this->line;
    }
    public function getFile(): string
    {
        return $this->file;
    }
    public function getFileDescription(): string
    {
        return $this->fileDescription;
    }
    public function getIdentifier(): string
    {
        return $this->identifier;
    }
    /**
     * @return mixed[]
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
