<?php

declare (strict_types=1);
namespace PHPStan\Rules\Namespaces;

use PhpParser\Node;
use PhpParser\Node\Stmt\Use_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use function count;
use function sprintf;
use function strtolower;
/**
 * @implements Rule<Node\Stmt\GroupUse>
 */
final class ExistingNamesInGroupUseRule implements Rule
{
    private ReflectionProvider $reflectionProvider;
    private ClassNameCheck $classCheck;
    private bool $checkFunctionNameCase;
    private bool $discoveringSymbolsTip;
    public function __construct(ReflectionProvider $reflectionProvider, ClassNameCheck $classCheck, bool $checkFunctionNameCase, bool $discoveringSymbolsTip)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->classCheck = $classCheck;
        $this->checkFunctionNameCase = $checkFunctionNameCase;
        $this->discoveringSymbolsTip = $discoveringSymbolsTip;
    }
    public function getNodeType(): string
    {
        return Node\Stmt\GroupUse::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];
        foreach ($node->uses as $use) {
            $error = null;
            /** @var Node\Name $name */
            $name = Node\Name::concat($node->prefix, $use->name, ['startLine' => $use->getStartLine()]);
            if ($node->type === Use_::TYPE_CONSTANT || $use->type === Use_::TYPE_CONSTANT) {
                $error = $this->checkConstant($name);
            } elseif ($node->type === Use_::TYPE_FUNCTION || $use->type === Use_::TYPE_FUNCTION) {
                $error = $this->checkFunction($name);
            } elseif ($use->type === Use_::TYPE_NORMAL) {
                $error = $this->checkClass($scope, $name);
            } else {
                throw new ShouldNotHappenException();
            }
            if ($error === null) {
                continue;
            }
            $errors[] = $error;
        }
        return $errors;
    }
    private function checkConstant(Node\Name $name): ?IdentifierRuleError
    {
        if (!$this->reflectionProvider->hasConstant($name, null)) {
            $errorBuilder = RuleErrorBuilder::message(sprintf('Used constant %s not found.', (string) $name))->line($name->getStartLine())->identifier('constant.notFound');
            if ($this->discoveringSymbolsTip) {
                $errorBuilder->discoveringSymbolsTip();
            }
            return $errorBuilder->build();
        }
        return null;
    }
    private function checkFunction(Node\Name $name): ?IdentifierRuleError
    {
        if (!$this->reflectionProvider->hasFunction($name, null)) {
            $errorBuilder = RuleErrorBuilder::message(sprintf('Used function %s not found.', (string) $name))->line($name->getStartLine())->identifier('function.notFound');
            if ($this->discoveringSymbolsTip) {
                $errorBuilder->discoveringSymbolsTip();
            }
            return $errorBuilder->build();
        }
        if ($this->checkFunctionNameCase) {
            $functionReflection = $this->reflectionProvider->getFunction($name, null);
            $realName = $functionReflection->getName();
            $usedName = (string) $name;
            if (strtolower($realName) === strtolower($usedName) && $realName !== $usedName) {
                return RuleErrorBuilder::message(sprintf('Function %s used with incorrect case: %s.', $realName, $usedName))->line($name->getStartLine())->identifier('function.nameCase')->build();
            }
        }
        return null;
    }
    private function checkClass(Scope $scope, Node\Name $name): ?IdentifierRuleError
    {
        $errors = $this->classCheck->checkClassNames($scope, [new ClassNameNodePair((string) $name, $name)], null);
        if (count($errors) === 0) {
            return null;
        } elseif (count($errors) === 1) {
            return $errors[0];
        }
        throw new ShouldNotHappenException();
    }
}
