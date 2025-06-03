<?php

declare (strict_types=1);
namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;
/**
 * @implements Rule<Node\Stmt\Trait_>
 */
final class RequireExtendsDefinitionTraitRule implements Rule
{
    private ReflectionProvider $reflectionProvider;
    private \PHPStan\Rules\PhpDoc\RequireExtendsCheck $requireExtendsCheck;
    public function __construct(ReflectionProvider $reflectionProvider, \PHPStan\Rules\PhpDoc\RequireExtendsCheck $requireExtendsCheck)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->requireExtendsCheck = $requireExtendsCheck;
    }
    public function getNodeType(): string
    {
        return Node\Stmt\Trait_::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        if ($node->namespacedName === null || !$this->reflectionProvider->hasClass($node->namespacedName->toString())) {
            return [];
        }
        $traitReflection = $this->reflectionProvider->getClass($node->namespacedName->toString());
        $extendsTags = $traitReflection->getRequireExtendsTags();
        return $this->requireExtendsCheck->checkExtendsTags($scope, $node, $extendsTags);
    }
}
