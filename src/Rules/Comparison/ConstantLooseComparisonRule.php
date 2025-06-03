<?php

declare (strict_types=1);
namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Parser\LastConditionVisitor;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use function sprintf;
/**
 * @implements Rule<Node\Expr\BinaryOp>
 */
final class ConstantLooseComparisonRule implements Rule
{
    private bool $treatPhpDocTypesAsCertain;
    private bool $reportAlwaysTrueInLastCondition;
    private bool $treatPhpDocTypesAsCertainTip;
    public function __construct(bool $treatPhpDocTypesAsCertain, bool $reportAlwaysTrueInLastCondition, bool $treatPhpDocTypesAsCertainTip)
    {
        $this->treatPhpDocTypesAsCertain = $treatPhpDocTypesAsCertain;
        $this->reportAlwaysTrueInLastCondition = $reportAlwaysTrueInLastCondition;
        $this->treatPhpDocTypesAsCertainTip = $treatPhpDocTypesAsCertainTip;
    }
    public function getNodeType(): string
    {
        return Node\Expr\BinaryOp::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node instanceof Node\Expr\BinaryOp\Equal && !$node instanceof Node\Expr\BinaryOp\NotEqual) {
            return [];
        }
        $nodeType = $this->treatPhpDocTypesAsCertain ? $scope->getType($node) : $scope->getNativeType($node);
        if (!$nodeType->isTrue()->yes() && !$nodeType->isFalse()->yes()) {
            return [];
        }
        $addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $node): RuleErrorBuilder {
            if (!$this->treatPhpDocTypesAsCertain) {
                return $ruleErrorBuilder;
            }
            $instanceofTypeWithoutPhpDocs = $scope->getNativeType($node);
            if ($instanceofTypeWithoutPhpDocs->isTrue()->yes() || $instanceofTypeWithoutPhpDocs->isFalse()->yes()) {
                return $ruleErrorBuilder;
            }
            if (!$this->treatPhpDocTypesAsCertainTip) {
                return $ruleErrorBuilder;
            }
            return $ruleErrorBuilder->treatPhpDocTypesAsCertainTip();
        };
        if ($nodeType->isFalse()->yes()) {
            return [$addTip(RuleErrorBuilder::message(sprintf('Loose comparison using %s between %s and %s will always evaluate to false.', $node->getOperatorSigil(), $scope->getType($node->left)->describe(VerbosityLevel::value()), $scope->getType($node->right)->describe(VerbosityLevel::value()))))->identifier(sprintf('%s.alwaysFalse', $node instanceof Node\Expr\BinaryOp\Equal ? 'equal' : 'notEqual'))->build()];
        }
        $isLast = $node->getAttribute(LastConditionVisitor::ATTRIBUTE_NAME);
        if ($isLast === \true && !$this->reportAlwaysTrueInLastCondition) {
            return [];
        }
        $errorBuilder = $addTip(RuleErrorBuilder::message(sprintf('Loose comparison using %s between %s and %s will always evaluate to true.', $node->getOperatorSigil(), $scope->getType($node->left)->describe(VerbosityLevel::value()), $scope->getType($node->right)->describe(VerbosityLevel::value()))));
        if ($isLast === \false && !$this->reportAlwaysTrueInLastCondition) {
            $errorBuilder->tip('Remove remaining cases below this one and this error will disappear too.');
        }
        $errorBuilder->identifier(sprintf('%s.alwaysTrue', $node instanceof Node\Expr\BinaryOp\Equal ? 'equal' : 'notEqual'));
        return [$errorBuilder->build()];
    }
}
