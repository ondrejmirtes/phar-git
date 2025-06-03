<?php

declare (strict_types=1);
namespace PHPStan\Rules\Exceptions;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\PropertyHookReturnStatementsNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\FileTypeMapper;
use function sprintf;
use function ucfirst;
/**
 * @implements Rule<PropertyHookReturnStatementsNode>
 */
final class TooWidePropertyHookThrowTypeRule implements Rule
{
    private FileTypeMapper $fileTypeMapper;
    private \PHPStan\Rules\Exceptions\TooWideThrowTypeCheck $check;
    public function __construct(FileTypeMapper $fileTypeMapper, \PHPStan\Rules\Exceptions\TooWideThrowTypeCheck $check)
    {
        $this->fileTypeMapper = $fileTypeMapper;
        $this->check = $check;
    }
    public function getNodeType() : string
    {
        return PropertyHookReturnStatementsNode::class;
    }
    public function processNode(Node $node, Scope $scope) : array
    {
        $docComment = $node->getDocComment();
        if ($docComment === null) {
            return [];
        }
        $statementResult = $node->getStatementResult();
        $hookReflection = $node->getHookReflection();
        if ($hookReflection->getPropertyHookName() === null) {
            throw new ShouldNotHappenException();
        }
        $classReflection = $node->getClassReflection();
        $resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc($scope->getFile(), $classReflection->getName(), $scope->isInTrait() ? $scope->getTraitReflection()->getName() : null, $hookReflection->getName(), $docComment->getText());
        if ($resolvedPhpDoc->getThrowsTag() === null) {
            return [];
        }
        $throwType = $resolvedPhpDoc->getThrowsTag()->getType();
        $errors = [];
        foreach ($this->check->check($throwType, $statementResult->getThrowPoints()) as $throwClass) {
            $errors[] = RuleErrorBuilder::message(sprintf('%s hook for property %s::$%s has %s in PHPDoc @throws tag but it\'s not thrown.', ucfirst($hookReflection->getPropertyHookName()), $hookReflection->getDeclaringClass()->getDisplayName(), $hookReflection->getHookedPropertyName(), $throwClass))->identifier('throws.unusedType')->build();
        }
        return $errors;
    }
}
