<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PhpParser\Node\Expr\Array_;
use PhpParser\NodeAbstract;
/**
 * @api
 */
final class LiteralArrayNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    /**
     * @var LiteralArrayItem[]
     */
    private array $itemNodes;
    /**
     * @param LiteralArrayItem[] $itemNodes
     */
    public function __construct(Array_ $originalNode, array $itemNodes)
    {
        $this->itemNodes = $itemNodes;
        parent::__construct($originalNode->getAttributes());
    }
    /**
     * @return LiteralArrayItem[]
     */
    public function getItemNodes(): array
    {
        return $this->itemNodes;
    }
    public function getType(): string
    {
        return 'PHPStan_Node_LiteralArray';
    }
    /**
     * @return string[]
     */
    public function getSubNodeNames(): array
    {
        return [];
    }
}
