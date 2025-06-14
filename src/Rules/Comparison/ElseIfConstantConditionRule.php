<?php

declare (strict_types=1);
namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Parser\LastConditionVisitor;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Constant\ConstantBooleanType;
use function sprintf;
/**
 * @implements Rule<Node\Stmt\ElseIf_>
 */
final class ElseIfConstantConditionRule implements Rule
{
    private \PHPStan\Rules\Comparison\ConstantConditionRuleHelper $helper;
    private bool $treatPhpDocTypesAsCertain;
    private bool $reportAlwaysTrueInLastCondition;
    private bool $treatPhpDocTypesAsCertainTip;
    public function __construct(\PHPStan\Rules\Comparison\ConstantConditionRuleHelper $helper, bool $treatPhpDocTypesAsCertain, bool $reportAlwaysTrueInLastCondition, bool $treatPhpDocTypesAsCertainTip)
    {
        $this->helper = $helper;
        $this->treatPhpDocTypesAsCertain = $treatPhpDocTypesAsCertain;
        $this->reportAlwaysTrueInLastCondition = $reportAlwaysTrueInLastCondition;
        $this->treatPhpDocTypesAsCertainTip = $treatPhpDocTypesAsCertainTip;
    }
    public function getNodeType(): string
    {
        return Node\Stmt\ElseIf_::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $exprType = $this->helper->getBooleanType($scope, $node->cond);
        if ($exprType instanceof ConstantBooleanType) {
            $addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $node): RuleErrorBuilder {
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
            $isLast = $node->cond->getAttribute(LastConditionVisitor::ATTRIBUTE_NAME);
            if (!$exprType->getValue() || $isLast !== \true || $this->reportAlwaysTrueInLastCondition) {
                $errorBuilder = $addTip(RuleErrorBuilder::message(sprintf('Elseif condition is always %s.', $exprType->getValue() ? 'true' : 'false')))->line($node->cond->getStartLine());
                if ($exprType->getValue() && $isLast === \false && !$this->reportAlwaysTrueInLastCondition) {
                    $errorBuilder->tip('Remove remaining cases below this one and this error will disappear too.');
                }
                $errorBuilder->identifier(sprintf('elseif.always%s', $exprType->getValue() ? 'True' : 'False'));
                return [$errorBuilder->build()];
            }
        }
        return [];
    }
}
