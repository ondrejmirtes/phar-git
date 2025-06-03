<?php

declare (strict_types=1);
namespace PHPStan\Rules\Generics;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Rules\Rule;
use PHPStan\Type\Generic\TemplateTypeVariance;
use function sprintf;
/**
 * @implements Rule<ClassPropertyNode>
 */
final class PropertyVarianceRule implements Rule
{
    private \PHPStan\Rules\Generics\VarianceCheck $varianceCheck;
    public function __construct(\PHPStan\Rules\Generics\VarianceCheck $varianceCheck)
    {
        $this->varianceCheck = $varianceCheck;
    }
    public function getNodeType(): string
    {
        return ClassPropertyNode::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $classReflection = $node->getClassReflection();
        if (!$classReflection->hasNativeProperty($node->getName())) {
            return [];
        }
        $propertyReflection = $classReflection->getNativeProperty($node->getName());
        if ($propertyReflection->isPrivate()) {
            return [];
        }
        $variance = $node->isReadOnly() || $node->isReadOnlyByPhpDoc() ? TemplateTypeVariance::createCovariant() : TemplateTypeVariance::createInvariant();
        return $this->varianceCheck->check($variance, $propertyReflection->getReadableType(), sprintf('in property %s::$%s', SprintfHelper::escapeFormatString($classReflection->getDisplayName()), SprintfHelper::escapeFormatString($node->getName())));
    }
}
