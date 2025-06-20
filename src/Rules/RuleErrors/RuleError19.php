<?php

declare (strict_types=1);
namespace PHPStan\Rules\RuleErrors;

use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\LineRuleError;
use PHPStan\Rules\RuleError;
/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
final class RuleError19 implements RuleError, LineRuleError, IdentifierRuleError
{
    public string $message;
    public int $line;
    public string $identifier;
    public function getMessage(): string
    {
        return $this->message;
    }
    public function getLine(): int
    {
        return $this->line;
    }
    public function getIdentifier(): string
    {
        return $this->identifier;
    }
}
