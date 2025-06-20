<?php

declare (strict_types=1);
namespace PHPStan\Rules\Methods;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Node\StaticMethodCallableNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;
/**
 * @implements Rule<StaticMethodCallableNode>
 */
final class StaticMethodCallableRule implements Rule
{
    private \PHPStan\Rules\Methods\StaticMethodCallCheck $methodCallCheck;
    private PhpVersion $phpVersion;
    public function __construct(\PHPStan\Rules\Methods\StaticMethodCallCheck $methodCallCheck, PhpVersion $phpVersion)
    {
        $this->methodCallCheck = $methodCallCheck;
        $this->phpVersion = $phpVersion;
    }
    public function getNodeType(): string
    {
        return StaticMethodCallableNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$this->phpVersion->supportsFirstClassCallables()) {
            return [RuleErrorBuilder::message('First-class callables are supported only on PHP 8.1 and later.')->nonIgnorable()->identifier('callable.notSupported')->build()];
        }
        $methodName = $node->getName();
        if (!$methodName instanceof Node\Identifier) {
            return [];
        }
        $methodNameName = $methodName->toString();
        [$errors, $methodReflection] = $this->methodCallCheck->check($scope, $methodNameName, $node->getClass());
        if ($methodReflection === null) {
            return $errors;
        }
        $declaringClass = $methodReflection->getDeclaringClass();
        if ($declaringClass->hasNativeMethod($methodNameName)) {
            return $errors;
        }
        $messagesMethodName = SprintfHelper::escapeFormatString($declaringClass->getDisplayName() . '::' . $methodReflection->getName() . '()');
        $errors[] = RuleErrorBuilder::message(sprintf('Creating callable from a non-native static method %s.', $messagesMethodName))->identifier('callable.nonNativeMethod')->build();
        return $errors;
    }
}
