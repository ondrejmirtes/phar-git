<?php

declare (strict_types=1);
namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
/**
 * @implements Rule<ClassPropertyNode>
 */
final class PropertiesInInterfaceRule implements Rule
{
    private PhpVersion $phpVersion;
    public function __construct(PhpVersion $phpVersion)
    {
        $this->phpVersion = $phpVersion;
    }
    public function getNodeType(): string
    {
        return ClassPropertyNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        if (!$node->getClassReflection()->isInterface()) {
            return [];
        }
        if (!$this->phpVersion->supportsPropertyHooks()) {
            return [RuleErrorBuilder::message('Interfaces can include properties only on PHP 8.4 and later.')->nonIgnorable()->identifier('property.inInterface')->build()];
        }
        if (!$node->hasHooks()) {
            return [RuleErrorBuilder::message('Interfaces can only include hooked properties.')->nonIgnorable()->identifier('property.nonHookedInInterface')->build()];
        }
        if (!$node->isPublic()) {
            return [RuleErrorBuilder::message('Interfaces cannot include non-public properties.')->nonIgnorable()->identifier('property.nonPublicInInterface')->build()];
        }
        if ($node->isReadOnly()) {
            return [RuleErrorBuilder::message('Interfaces cannot include readonly hooked properties.')->nonIgnorable()->identifier('property.readOnlyInInterface')->build()];
        }
        if ($node->isStatic()) {
            return [RuleErrorBuilder::message('Hooked properties cannot be static.')->nonIgnorable()->identifier('property.hookedStatic')->build()];
        }
        if ($node->isAbstract()) {
            return [RuleErrorBuilder::message('Property in interface cannot be explicitly abstract.')->nonIgnorable()->identifier('property.abstractInInterface')->build()];
        }
        if ($node->isFinal()) {
            return [RuleErrorBuilder::message('Interfaces cannot include final properties.')->nonIgnorable()->identifier('property.finalInInterface')->build()];
        }
        foreach ($node->getHooks() as $hook) {
            if (!$hook->isFinal()) {
                continue;
            }
            return [RuleErrorBuilder::message('Property hook cannot be both abstract and final.')->nonIgnorable()->identifier('property.abstractFinalHook')->build()];
        }
        if ($this->hasAnyHookBody($node)) {
            return [RuleErrorBuilder::message('Interfaces cannot include property hooks with bodies.')->nonIgnorable()->identifier('property.hookBodyInInterface')->build()];
        }
        return [];
    }
    private function hasAnyHookBody(ClassPropertyNode $node): bool
    {
        foreach ($node->getHooks() as $hook) {
            if ($hook->body !== null) {
                return \true;
            }
        }
        return \false;
    }
}
