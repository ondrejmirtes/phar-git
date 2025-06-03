<?php

declare (strict_types=1);
namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use function sprintf;
/**
 * @implements Rule<Node\Stmt\If_>
 */
final class IfConstantConditionRule implements Rule
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
    public function getNodeType() : string
    {
        return Node\Stmt\If_::class;
    }
    public function processNode(Node $node, Scope $scope) : array
    {
        $exprType = $this->helper->getBooleanType($scope, $node->cond);
        if ($exprType instanceof ConstantBooleanType) {
            $addTip = function (RuleErrorBuilder $ruleErrorBuilder) use($scope, $node) : RuleErrorBuilder {
                if (!$this->treatPhpDocTypesAsCertain) {
                    return $ruleErrorBuilder;
                }
                $booleanNativeType = $this->helper->getNativeBooleanType($scope, $node->cond);
                if ($booleanNativeType instanceof ConstantBooleanType) {
                    return $ruleErrorBuilder;
                }
                if (!$this->treatPhpDocTypesAsCertainTip) {
                    return $ruleErrorBuilder;
                }
                return $ruleErrorBuilder->treatPhpDocTypesAsCertainTip();
            };
            return [$addTip(RuleErrorBuilder::message(sprintf('If condition is always %s.', $exprType->getValue() ? 'true' : 'false')))->identifier(sprintf('if.always%s', $exprType->getValue() ? 'True' : 'False'))->line($node->cond->getStartLine())->build()];
        }
        return [];
    }
}
