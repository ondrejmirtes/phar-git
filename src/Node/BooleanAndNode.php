<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Expr\BinaryOp\LogicalAnd;
use PHPStan\Analyser\Scope;
/**
 * @api
 */
final class BooleanAndNode extends Expr implements \PHPStan\Node\VirtualNode
{
    /**
     * @var \PhpParser\Node\Expr\BinaryOp\BooleanAnd|\PhpParser\Node\Expr\BinaryOp\LogicalAnd
     */
    private $originalNode;
    private Scope $rightScope;
    /**
     * @param \PhpParser\Node\Expr\BinaryOp\BooleanAnd|\PhpParser\Node\Expr\BinaryOp\LogicalAnd $originalNode
     */
    public function __construct($originalNode, Scope $rightScope)
    {
        $this->originalNode = $originalNode;
        $this->rightScope = $rightScope;
        parent::__construct($originalNode->getAttributes());
    }
    /**
     * @return BooleanAnd|LogicalAnd
     */
    public function getOriginalNode()
    {
        return $this->originalNode;
    }
    public function getRightScope(): Scope
    {
        return $this->rightScope;
    }
    public function getType(): string
    {
        return 'PHPStan_Node_BooleanAndNode';
    }
    /**
     * @return string[]
     */
    public function getSubNodeNames(): array
    {
        return [];
    }
}
