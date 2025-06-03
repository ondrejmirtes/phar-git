<?php

declare (strict_types=1);
namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Stmt\Break_;
use PhpParser\Node\Stmt\Continue_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\BreaklessWhileLoopNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
/**
 * @implements Rule<BreaklessWhileLoopNode>
 */
final class WhileLoopAlwaysTrueConditionRule implements Rule
{
    private \PHPStan\Rules\Comparison\ConstantConditionRuleHelper $helper;
    private bool $treatPhpDocTypesAsCertain;
    private bool $treatPhpDocTypesAsCertainTip;
    public function __construct(\PHPStan\Rules\Comparison\ConstantConditionRuleHelper $helper, bool $treatPhpDocTypesAsCertain, bool $treatPhpDocTypesAsCertainTip)
    {
        $this->helper = $helper;
        $this->treatPhpDocTypesAsCertain = $treatPhpDocTypesAsCertain;
        $this->treatPhpDocTypesAsCertainTip = $treatPhpDocTypesAsCertainTip;
    }
    public function getNodeType(): string
    {
        return BreaklessWhileLoopNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        foreach ($node->getExitPoints() as $exitPoint) {
            $statement = $exitPoint->getStatement();
            if ($statement instanceof Break_) {
                return [];
            }
            if (!$statement instanceof Continue_) {
                return [];
            }
            if ($statement->num === null) {
                continue;
            }
            if (!$statement->num instanceof Int_) {
                continue;
            }
            $value = $statement->num->value;
            if ($value === 1) {
                continue;
            }
            if ($value > 1) {
                return [];
            }
        }
        $originalNode = $node->getOriginalNode();
        $exprType = $this->helper->getBooleanType($scope, $originalNode->cond);
        if ($exprType->isTrue()->yes()) {
            $addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $originalNode): RuleErrorBuilder {
                if (!$this->treatPhpDocTypesAsCertain) {
                    return $ruleErrorBuilder;
                }
                $booleanNativeType = $this->helper->getNativeBooleanType($scope, $originalNode->cond);
                if ($booleanNativeType instanceof ConstantBooleanType) {
                    return $ruleErrorBuilder;
                }
                if (!$this->treatPhpDocTypesAsCertainTip) {
                    return $ruleErrorBuilder;
                }
                return $ruleErrorBuilder->treatPhpDocTypesAsCertainTip();
            };
            return [$addTip(RuleErrorBuilder::message('While loop condition is always true.'))->line($originalNode->cond->getStartLine())->identifier('while.alwaysTrue')->build()];
        }
        return [];
    }
}
