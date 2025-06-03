<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PhpParser\Node\Stmt\Foreach_;
use PhpParser\NodeAbstract;
final class InForeachNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    private Foreach_ $originalNode;
    public function __construct(Foreach_ $originalNode)
    {
        $this->originalNode = $originalNode;
        parent::__construct($originalNode->getAttributes());
    }
    public function getOriginalNode(): Foreach_
    {
        return $this->originalNode;
    }
    public function getType(): string
    {
        return 'PHPStan_Node_InForeachNode';
    }
    /**
     * @return string[]
     */
    public function getSubNodeNames(): array
    {
        return [];
    }
}
