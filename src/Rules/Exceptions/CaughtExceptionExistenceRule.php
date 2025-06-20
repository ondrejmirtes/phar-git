<?php

declare (strict_types=1);
namespace PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PhpParser\Node\Stmt\Catch_;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\ClassNameUsageLocation;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use Throwable;
use function array_merge;
use function sprintf;
/**
 * @implements Rule<Node\Stmt\Catch_>
 */
final class CaughtExceptionExistenceRule implements Rule
{
    private ReflectionProvider $reflectionProvider;
    private ClassNameCheck $classCheck;
    private bool $checkClassCaseSensitivity;
    private bool $discoveringSymbolsTip;
    public function __construct(ReflectionProvider $reflectionProvider, ClassNameCheck $classCheck, bool $checkClassCaseSensitivity, bool $discoveringSymbolsTip)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->classCheck = $classCheck;
        $this->checkClassCaseSensitivity = $checkClassCaseSensitivity;
        $this->discoveringSymbolsTip = $discoveringSymbolsTip;
    }
    public function getNodeType(): string
    {
        return Catch_::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];
        foreach ($node->types as $class) {
            $className = (string) $class;
            if (!$this->reflectionProvider->hasClass($className)) {
                if ($scope->isInClassExists($className)) {
                    continue;
                }
                $errorBuilder = RuleErrorBuilder::message(sprintf('Caught class %s not found.', $className))->line($class->getStartLine())->identifier('class.notFound');
                if ($this->discoveringSymbolsTip) {
                    $errorBuilder->discoveringSymbolsTip();
                }
                $errors[] = $errorBuilder->build();
                continue;
            }
            $classReflection = $this->reflectionProvider->getClass($className);
            if (!$classReflection->isInterface() && !$classReflection->implementsInterface(Throwable::class)) {
                $errors[] = RuleErrorBuilder::message(sprintf('Caught class %s is not an exception.', $classReflection->getDisplayName()))->line($class->getStartLine())->identifier('catch.notThrowable')->build();
            }
            $errors = array_merge($errors, $this->classCheck->checkClassNames($scope, [new ClassNameNodePair($className, $class)], ClassNameUsageLocation::from(ClassNameUsageLocation::EXCEPTION_CATCH), $this->checkClassCaseSensitivity));
        }
        return $errors;
    }
}
