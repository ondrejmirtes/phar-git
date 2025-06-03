<?php

declare (strict_types=1);
namespace PHPStan\Testing;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;
/**
 * @implements Rule<InClassNode>
 */
final class NonexistentAnalysedClassRule implements Rule
{
    private ReflectionProvider $reflectionProvider;
    public function __construct(ReflectionProvider $reflectionProvider)
    {
        $this->reflectionProvider = $reflectionProvider;
    }
    public function getNodeType(): string
    {
        return InClassNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $className = $node->getClassReflection()->getName();
        if ($this->reflectionProvider->hasClass($className)) {
            return [];
        }
        return [RuleErrorBuilder::message(sprintf('%s %s not found in ReflectionProvider. Configure "autoload-dev" section in composer.json to include your tests directory.', $node->getClassReflection()->getClassTypeDescription(), $node->getClassReflection()->getName()))->identifier('phpstan.classNotFound')->nonIgnorable()->build()];
    }
}
