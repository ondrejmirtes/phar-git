<?php

declare (strict_types=1);
namespace PHPStan\Rules\RuleErrors;

use PhpParser\Node;
use PHPStan\Rules\FileRuleError;
use PHPStan\Rules\FixableNodeRuleError;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\NonIgnorableRuleError;
use PHPStan\Rules\RuleError;
/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
final class RuleError213 implements RuleError, FileRuleError, IdentifierRuleError, NonIgnorableRuleError, FixableNodeRuleError
{
    public string $message;
    public string $file;
    public string $fileDescription;
    public string $identifier;
    public Node $originalNode;
    /** @var callable(Node): Node */
    public $newNodeCallable;
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
    public function getIdentifier(): string
    {
        return $this->identifier;
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
