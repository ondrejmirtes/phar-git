<?php

declare (strict_types=1);
namespace PHPStan\Rules\RuleErrors;

use PhpParser\Node;
use PHPStan\Rules\FileRuleError;
use PHPStan\Rules\FixableNodeRuleError;
use PHPStan\Rules\MetadataRuleError;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\TipRuleError;
/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
final class RuleError173 implements RuleError, FileRuleError, TipRuleError, MetadataRuleError, FixableNodeRuleError
{
    public string $message;
    public string $file;
    public string $fileDescription;
    public string $tip;
    /** @var mixed[] */
    public array $metadata;
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
    public function getTip(): string
    {
        return $this->tip;
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
