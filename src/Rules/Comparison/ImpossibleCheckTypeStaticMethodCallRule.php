<?php

declare (strict_types=1);
namespace PHPStan\Rules\Comparison;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\Scope;
use PHPStan\Parser\LastConditionVisitor;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use function sprintf;
/**
 * @implements Rule<Node\Expr\StaticCall>
 */
final class ImpossibleCheckTypeStaticMethodCallRule implements Rule
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
        return Node\Expr\StaticCall::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->name instanceof Node\Identifier) {
            return [];
        }
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
            $method = $this->getMethod($node->class, $node->name->name, $scope);
            return [$addTip(RuleErrorBuilder::message(sprintf('Call to static method %s::%s()%s will always evaluate to false.', $method->getDeclaringClass()->getDisplayName(), $method->getName(), $this->impossibleCheckTypeHelper->getArgumentsDescription($scope, $node->getArgs()))))->identifier('staticMethod.impossibleType')->build()];
        }
        $isLast = $node->getAttribute(LastConditionVisitor::ATTRIBUTE_NAME);
        if ($isLast === \true && !$this->reportAlwaysTrueInLastCondition) {
            return [];
        }
        $method = $this->getMethod($node->class, $node->name->name, $scope);
        $errorBuilder = $addTip(RuleErrorBuilder::message(sprintf('Call to static method %s::%s()%s will always evaluate to true.', $method->getDeclaringClass()->getDisplayName(), $method->getName(), $this->impossibleCheckTypeHelper->getArgumentsDescription($scope, $node->getArgs()))));
        if ($isLast === \false && !$this->reportAlwaysTrueInLastCondition) {
            $errorBuilder->tip('Remove remaining cases below this one and this error will disappear too.');
        }
        $errorBuilder->identifier('staticMethod.alreadyNarrowedType');
        return [$errorBuilder->build()];
    }
    /**
     * @param Node\Name|Expr $class
     * @throws ShouldNotHappenException
     */
    private function getMethod($class, string $methodName, Scope $scope): MethodReflection
    {
        if ($class instanceof Node\Name) {
            $calledOnType = $scope->resolveTypeByName($class);
        } else {
            $calledOnType = $scope->getType($class);
        }
        $method = $scope->getMethodReflection($calledOnType, $methodName);
        if ($method === null) {
            throw new ShouldNotHappenException();
        }
        return $method;
    }
}
