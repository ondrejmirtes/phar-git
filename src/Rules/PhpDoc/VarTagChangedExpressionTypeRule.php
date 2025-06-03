<?php

declare (strict_types=1);
namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\VarTagChangedExpressionTypeNode;
use PHPStan\Rules\Rule;
/**
 * @implements Rule<VarTagChangedExpressionTypeNode>
 */
final class VarTagChangedExpressionTypeRule implements Rule
{
    private \PHPStan\Rules\PhpDoc\VarTagTypeRuleHelper $varTagTypeRuleHelper;
    public function __construct(\PHPStan\Rules\PhpDoc\VarTagTypeRuleHelper $varTagTypeRuleHelper)
    {
        $this->varTagTypeRuleHelper = $varTagTypeRuleHelper;
    }
    public function getNodeType() : string
    {
        return VarTagChangedExpressionTypeNode::class;
    }
    public function processNode(Node $node, Scope $scope) : array
    {
        return $this->varTagTypeRuleHelper->checkExprType($scope, $node->getExpr(), $node->getVarTag()->getType());
    }
}
