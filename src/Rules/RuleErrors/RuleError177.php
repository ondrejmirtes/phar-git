<?php

declare (strict_types=1);
namespace PHPStan\Rules\RuleErrors;

use PhpParser\Node;
use PHPStan\Rules\FixableNodeRuleError;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\MetadataRuleError;
use PHPStan\Rules\RuleError;
/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
final class RuleError177 implements RuleError, IdentifierRuleError, MetadataRuleError, FixableNodeRuleError
{
    public string $message;
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
