<?php

declare (strict_types=1);
namespace PHPStan\Rules\Generics;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Type\Generic\TemplateTypeScope;
use function sprintf;
/**
 * @implements Rule<InClassNode>
 */
final class InterfaceTemplateTypeRule implements Rule
{
    private \PHPStan\Rules\Generics\TemplateTypeCheck $templateTypeCheck;
    public function __construct(\PHPStan\Rules\Generics\TemplateTypeCheck $templateTypeCheck)
    {
        $this->templateTypeCheck = $templateTypeCheck;
    }
    public function getNodeType(): string
    {
        return InClassNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->getClassReflection();
        if (!$classReflection->isInterface()) {
            return [];
        }
        $interfaceName = $classReflection->getName();
        $escapadInterfaceName = SprintfHelper::escapeFormatString($interfaceName);
        return $this->templateTypeCheck->check($scope, $node, TemplateTypeScope::createWithClass($interfaceName), $classReflection->getTemplateTags(), sprintf('PHPDoc tag @template for interface %s cannot have existing class %%s as its name.', $escapadInterfaceName), sprintf('PHPDoc tag @template for interface %s cannot have existing type alias %%s as its name.', $escapadInterfaceName), sprintf('PHPDoc tag @template %%s for interface %s has invalid bound type %%s.', $escapadInterfaceName), sprintf('PHPDoc tag @template %%s for interface %s with bound type %%s is not supported.', $escapadInterfaceName), sprintf('PHPDoc tag @template %%s for interface %s has invalid default type %%s.', $escapadInterfaceName), sprintf('Default type %%s in PHPDoc tag @template %%s for interface %s is not subtype of bound type %%s.', $escapadInterfaceName), sprintf('PHPDoc tag @template %%s for interface %s does not have a default type but follows an optional @template %%s.', $escapadInterfaceName));
    }
}
