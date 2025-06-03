<?php

declare (strict_types=1);
namespace PHPStan\Fixable;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use PHPStan\Node\VirtualNode;
use PHPStan\ShouldNotHappenException;
final class ReplacingNodeVisitor extends NodeVisitorAbstract
{
    private Node $originalNode;
    /**
     * @var callable(Node): Node
     */
    private $newNodeCallable;
    private bool $found = \false;
    /**
     * @param callable(Node): Node $newNodeCallable
     */
    public function __construct(Node $originalNode, $newNodeCallable)
    {
        $this->originalNode = $originalNode;
        $this->newNodeCallable = $newNodeCallable;
    }
    public function enterNode(Node $node): ?Node
    {
        $origNode = $node->getAttribute('origNode');
        if ($origNode !== $this->originalNode) {
            return null;
        }
        $this->found = \true;
        $callable = $this->newNodeCallable;
        $newNode = $callable($node);
        if ($newNode instanceof VirtualNode) {
            throw new ShouldNotHappenException('Cannot print VirtualNode.');
        }
        return $newNode;
    }
    public function isFound(): bool
    {
        return $this->found;
    }
}
