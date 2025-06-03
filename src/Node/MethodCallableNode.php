<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
/**
 * @api
 */
final class MethodCallableNode extends Expr implements \PHPStan\Node\VirtualNode
{
    private Expr $var;
    /**
     * @var \PhpParser\Node\Identifier|\PhpParser\Node\Expr
     */
    private $name;
    private Expr\MethodCall $originalNode;
    /**
     * @param \PhpParser\Node\Identifier|\PhpParser\Node\Expr $name
     */
    public function __construct(Expr $var, $name, Expr\MethodCall $originalNode)
    {
        $this->var = $var;
        $this->name = $name;
        $this->originalNode = $originalNode;
        parent::__construct($originalNode->getAttributes());
    }
    public function getVar() : Expr
    {
        return $this->var;
    }
    /**
     * @return Expr|Identifier
     */
    public function getName()
    {
        return $this->name;
    }
    public function getOriginalNode() : Expr\MethodCall
    {
        return $this->originalNode;
    }
    public function getType() : string
    {
        return 'PHPStan_Node_MethodCallableNode';
    }
    /**
     * @return string[]
     */
    public function getSubNodeNames() : array
    {
        return [];
    }
}
