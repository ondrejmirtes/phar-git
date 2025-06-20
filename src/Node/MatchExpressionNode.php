<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PhpParser\NodeAbstract;
use PHPStan\Analyser\Scope;
/**
 * @api
 */
final class MatchExpressionNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    private Expr $condition;
    /**
     * @var MatchExpressionArm[]
     */
    private array $arms;
    private Scope $endScope;
    /**
     * @param MatchExpressionArm[] $arms
     */
    public function __construct(Expr $condition, array $arms, Expr\Match_ $originalNode, Scope $endScope)
    {
        $this->condition = $condition;
        $this->arms = $arms;
        $this->endScope = $endScope;
        parent::__construct($originalNode->getAttributes());
    }
    public function getCondition(): Expr
    {
        return $this->condition;
    }
    /**
     * @return MatchExpressionArm[]
     */
    public function getArms(): array
    {
        return $this->arms;
    }
    public function getEndScope(): Scope
    {
        return $this->endScope;
    }
    public function getType(): string
    {
        return 'PHPStan_Node_MatchExpression';
    }
    /**
     * @return string[]
     */
    public function getSubNodeNames(): array
    {
        return [];
    }
}
