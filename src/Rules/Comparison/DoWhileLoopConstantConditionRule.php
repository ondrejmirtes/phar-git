<?php

declare (strict_types=1);
namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PhpParser\Node\Scalar\Int_;
use PhpParser\Node\Stmt\Break_;
use PhpParser\Node\Stmt\Continue_;
use PHPStan\Analyser\Scope;
use PHPStan\Node\DoWhileLoopConditionNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use function sprintf;
/**
 * @implements Rule<DoWhileLoopConditionNode>
 */
final class DoWhileLoopConstantConditionRule implements Rule
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
        return DoWhileLoopConditionNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $exprType = $this->helper->getBooleanType($scope, $node->getCond());
        if ($exprType instanceof ConstantBooleanType) {
            if ($exprType->getValue()) {
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
            }
            $addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $node): RuleErrorBuilder {
                if (!$this->treatPhpDocTypesAsCertain) {
                    return $ruleErrorBuilder;
                }
                $booleanNativeType = $this->helper->getNativeBooleanType($scope, $node->getCond());
                if ($booleanNativeType instanceof ConstantBooleanType) {
                    return $ruleErrorBuilder;
                }
                if (!$this->treatPhpDocTypesAsCertainTip) {
                    return $ruleErrorBuilder;
                }
                return $ruleErrorBuilder->treatPhpDocTypesAsCertainTip();
            };
            return [$addTip(RuleErrorBuilder::message(sprintf('Do-while loop condition is always %s.', $exprType->getValue() ? 'true' : 'false')))->line($node->getCond()->getStartLine())->identifier(sprintf('doWhile.always%s', $exprType->getValue() ? 'True' : 'False'))->build()];
        }
        return [];
    }
}
