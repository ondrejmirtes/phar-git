<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PhpParser\Node\Stmt\While_;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\StatementExitPoint;
/**
 * @api
 */
final class BreaklessWhileLoopNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    private While_ $originalNode;
    /**
     * @var StatementExitPoint[]
     */
    private array $exitPoints;
    /**
     * @param StatementExitPoint[] $exitPoints
     */
    public function __construct(While_ $originalNode, array $exitPoints)
    {
        $this->originalNode = $originalNode;
        $this->exitPoints = $exitPoints;
        parent::__construct($originalNode->getAttributes());
    }
    public function getOriginalNode() : While_
    {
        return $this->originalNode;
    }
    /**
     * @return StatementExitPoint[]
     */
    public function getExitPoints() : array
    {
        return $this->exitPoints;
    }
    public function getType() : string
    {
        return 'PHPStan_Node_BreaklessWhileLoop';
    }
    /**
     * @return string[]
     */
    public function getSubNodeNames() : array
    {
        return [];
    }
}
