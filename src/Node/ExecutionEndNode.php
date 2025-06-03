<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PhpParser\Node;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\StatementResult;
/**
 * @api
 */
final class ExecutionEndNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    private Node\Stmt $node;
    private StatementResult $statementResult;
    private bool $hasNativeReturnTypehint;
    public function __construct(Node\Stmt $node, StatementResult $statementResult, bool $hasNativeReturnTypehint)
    {
        $this->node = $node;
        $this->statementResult = $statementResult;
        $this->hasNativeReturnTypehint = $hasNativeReturnTypehint;
        parent::__construct($node->getAttributes());
    }
    public function getNode(): Node\Stmt
    {
        return $this->node;
    }
    public function getStatementResult(): StatementResult
    {
        return $this->statementResult;
    }
    public function hasNativeReturnTypehint(): bool
    {
        return $this->hasNativeReturnTypehint;
    }
    public function getType(): string
    {
        return 'PHPStan_Node_ExecutionEndNode';
    }
    /**
     * @return string[]
     */
    public function getSubNodeNames(): array
    {
        return [];
    }
}
