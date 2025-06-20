<?php

declare (strict_types=1);
namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Parser\LastConditionVisitor;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;
/**
 * @implements Rule<Node\Expr\FuncCall>
 */
final class ImpossibleCheckTypeFunctionCallRule implements Rule
{
    private \PHPStan\Rules\Comparison\ImpossibleCheckTypeHelper $impossibleCheckTypeHelper;
    private bool $treatPhpDocTypesAsCertain;
    private bool $reportAlwaysTrueInLastCondition;
    private bool $treatPhpDocTypesAsCertainTip;
    public function __construct(\PHPStan\Rules\Comparison\ImpossibleCheckTypeHelper $impossibleCheckTypeHelper, bool $treatPhpDocTypesAsCertain, bool $reportAlwaysTrueInLastCondition, bool $treatPhpDocTypesAsCertainTip)
    {
        $this->impossibleCheckTypeHelper = $impossibleCheckTypeHelper;
        $this->treatPhpDocTypesAsCertain = $treatPhpDocTypesAsCertain;
        $this->reportAlwaysTrueInLastCondition = $reportAlwaysTrueInLastCondition;
        $this->treatPhpDocTypesAsCertainTip = $treatPhpDocTypesAsCertainTip;
    }
    public function getNodeType(): string
    {
        return Node\Expr\FuncCall::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Node\Name) {
            return [];
        }
        $functionName = (string) $node->name;
        $isAlways = $this->impossibleCheckTypeHelper->findSpecifiedType($scope, $node);
        if ($isAlways === null) {
            return [];
        }
        $addTip = function (RuleErrorBuilder $ruleErrorBuilder) use ($scope, $node): RuleErrorBuilder {
            if (!$this->treatPhpDocTypesAsCertain) {
                return $ruleErrorBuilder;
            }
            $isAlways = $this->impossibleCheckTypeHelper->doNotTreatPhpDocTypesAsCertain()->findSpecifiedType($scope, $node);
            if ($isAlways !== null) {
                return $ruleErrorBuilder;
            }
            if (!$this->treatPhpDocTypesAsCertainTip) {
                return $ruleErrorBuilder;
            }
            return $ruleErrorBuilder->treatPhpDocTypesAsCertainTip();
        };
        if (!$isAlways) {
            return [$addTip(RuleErrorBuilder::message(sprintf('Call to function %s()%s will always evaluate to false.', $functionName, $this->impossibleCheckTypeHelper->getArgumentsDescription($scope, $node->getArgs()))))->identifier('function.impossibleType')->build()];
        }
        $isLast = $node->getAttribute(LastConditionVisitor::ATTRIBUTE_NAME);
        if ($isLast === \true && !$this->reportAlwaysTrueInLastCondition) {
            return [];
        }
        $errorBuilder = $addTip(RuleErrorBuilder::message(sprintf('Call to function %s()%s will always evaluate to true.', $functionName, $this->impossibleCheckTypeHelper->getArgumentsDescription($scope, $node->getArgs()))));
        if ($isLast === \false && !$this->reportAlwaysTrueInLastCondition) {
            $errorBuilder->tip('Remove remaining cases below this one and this error will disappear too.');
        }
        $errorBuilder->identifier('function.alreadyNarrowedType');
        return [$errorBuilder->build()];
    }
}
