<?php

declare (strict_types=1);
namespace PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\FunctionReturnStatementsNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;
/**
 * @implements Rule<FunctionReturnStatementsNode>
 */
final class MissingCheckedExceptionInFunctionThrowsRule implements Rule
{
    private \PHPStan\Rules\Exceptions\MissingCheckedExceptionInThrowsCheck $check;
    public function __construct(\PHPStan\Rules\Exceptions\MissingCheckedExceptionInThrowsCheck $check)
    {
        $this->check = $check;
    }
    public function getNodeType(): string
    {
        return FunctionReturnStatementsNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $statementResult = $node->getStatementResult();
        $functionReflection = $node->getFunctionReflection();
        $errors = [];
        foreach ($this->check->check($functionReflection->getThrowType(), $statementResult->getThrowPoints()) as [$className, $throwPointNode]) {
            $errors[] = RuleErrorBuilder::message(sprintf('Function %s() throws checked exception %s but it\'s missing from the PHPDoc @throws tag.', $functionReflection->getName(), $className))->line($throwPointNode->getStartLine())->identifier('missingType.checkedException')->build();
        }
        return $errors;
    }
}
