<?php

declare (strict_types=1);
namespace PHPStan\Rules\RuleErrors;

use PhpParser\Node;
use PHPStan\Rules\FileRuleError;
use PHPStan\Rules\FixableNodeRuleError;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\LineRuleError;
use PHPStan\Rules\MetadataRuleError;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\TipRuleError;
/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
final class RuleError191 implements RuleError, LineRuleError, FileRuleError, TipRuleError, IdentifierRuleError, MetadataRuleError, FixableNodeRuleError
{
    public string $message;
    public int $line;
    public string $file;
    public string $fileDescription;
    public string $tip;
    public string $identifier;
    /** @var mixed[] */
    public array $metadata;
    public Node $originalNode;
    /** @var callable(Node): Node */
    public $newNodeCallable;
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
    public function getTip(): string
    {
        return $this->tip;
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
    public function getOriginalNode(): Node
    {
        return $this->originalNode;
    }
    /**
     * @return callable(Node): Node
     */
    public function getNewNodeCallable(): callable
    {
        return $this->newNodeCallable;
    }
}
