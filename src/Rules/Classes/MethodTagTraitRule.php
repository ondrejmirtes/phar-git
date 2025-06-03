<?php

declare (strict_types=1);
namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
/**
 * @implements Rule<Node\Stmt\Trait_>
 */
final class MethodTagTraitRule implements Rule
{
    private \PHPStan\Rules\Classes\MethodTagCheck $check;
    private ReflectionProvider $reflectionProvider;
    public function __construct(\PHPStan\Rules\Classes\MethodTagCheck $check, ReflectionProvider $reflectionProvider)
    {
        $this->check = $check;
        $this->reflectionProvider = $reflectionProvider;
    }
    public function getNodeType(): string
    {
        return Node\Stmt\Trait_::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $traitName = $node->namespacedName;
        if ($traitName === null) {
            return [];
        }
        if (!$this->reflectionProvider->hasClass($traitName->toString())) {
            return [];
        }
        return $this->check->checkInTraitDefinitionContext($this->reflectionProvider->getClass($traitName->toString()));
    }
}
