<?php

declare (strict_types=1);
namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\VerbosityLevel;
use function sprintf;
/**
 * @implements Rule<InClassNode>
 */
final class RequireExtendsRule implements Rule
{
    public function getNodeType(): string
    {
        return InClassNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->getClassReflection();
        if ($classReflection->isInterface()) {
            return [];
        }
        $errors = [];
        foreach ($classReflection->getInterfaces() as $interface) {
            $extendsTags = $interface->getRequireExtendsTags();
            foreach ($extendsTags as $extendsTag) {
                $type = $extendsTag->getType();
                foreach ($type->getObjectClassNames() as $className) {
                    if ($classReflection->is($className)) {
                        continue;
                    }
                    $errors[] = RuleErrorBuilder::message(sprintf('Interface %s requires implementing class to extend %s, but %s does not.', $interface->getDisplayName(), $type->describe(VerbosityLevel::typeOnly()), $classReflection->getDisplayName()))->identifier('class.missingExtends')->build();
                    break;
                }
            }
        }
        foreach ($classReflection->getTraits(\true) as $trait) {
            $extendsTags = $trait->getRequireExtendsTags();
            foreach ($extendsTags as $extendsTag) {
                $type = $extendsTag->getType();
                foreach ($type->getObjectClassNames() as $className) {
                    if ($classReflection->is($className)) {
                        continue;
                    }
                    $errors[] = RuleErrorBuilder::message(sprintf('Trait %s requires using class to extend %s, but %s does not.', $trait->getDisplayName(), $type->describe(VerbosityLevel::typeOnly()), $classReflection->getDisplayName()))->identifier('class.missingExtends')->build();
                    break;
                }
            }
        }
        return $errors;
    }
}
