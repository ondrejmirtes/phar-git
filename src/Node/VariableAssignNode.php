<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;
final class VariableAssignNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    private Expr\Variable $variable;
    private Expr $assignedExpr;
    public function __construct(Expr\Variable $variable, Expr $assignedExpr)
    {
        $this->variable = $variable;
        $this->assignedExpr = $assignedExpr;
        parent::__construct($variable->getAttributes());
    }
    public function getVariable() : Expr\Variable
    {
        return $this->variable;
    }
    public function getAssignedExpr() : Expr
    {
        return $this->assignedExpr;
    }
    public function getType() : string
    {
        return 'PHPStan_Node_VariableAssignNodeNode';
    }
    /**
     * @return string[]
     */
    public function getSubNodeNames() : array
    {
        return [];
    }
}
