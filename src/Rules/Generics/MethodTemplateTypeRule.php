<?php

declare (strict_types=1);
namespace PHPStan\Rules\Generics;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\FileTypeMapper;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\VerbosityLevel;
use function array_keys;
use function sprintf;
/**
 * @implements Rule<Node\Stmt\ClassMethod>
 */
final class MethodTemplateTypeRule implements Rule
{
    private FileTypeMapper $fileTypeMapper;
    private \PHPStan\Rules\Generics\TemplateTypeCheck $templateTypeCheck;
    public function __construct(FileTypeMapper $fileTypeMapper, \PHPStan\Rules\Generics\TemplateTypeCheck $templateTypeCheck)
    {
        $this->fileTypeMapper = $fileTypeMapper;
        $this->templateTypeCheck = $templateTypeCheck;
    }
    public function getNodeType(): string
    {
        return Node\Stmt\ClassMethod::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $docComment = $node->getDocComment();
        if ($docComment === null) {
            return [];
        }
        if (!$scope->isInClass()) {
            throw new ShouldNotHappenException();
        }
        $classReflection = $scope->getClassReflection();
        $className = $classReflection->getDisplayName();
        $methodName = $node->name->toString();
        $resolvedPhpDoc = $this->fileTypeMapper->getResolvedPhpDoc($scope->getFile(), $classReflection->getName(), $scope->isInTrait() ? $scope->getTraitReflection()->getName() : null, $methodName, $docComment->getText());
        $methodTemplateTags = $resolvedPhpDoc->getTemplateTags();
        $escapedClassName = SprintfHelper::escapeFormatString($className);
        $escapedMethodName = SprintfHelper::escapeFormatString($methodName);
        $messages = $this->templateTypeCheck->check($scope, $node, TemplateTypeScope::createWithMethod($className, $methodName), $methodTemplateTags, sprintf('PHPDoc tag @template for method %s::%s() cannot have existing class %%s as its name.', $escapedClassName, $escapedMethodName), sprintf('PHPDoc tag @template for method %s::%s() cannot have existing type alias %%s as its name.', $escapedClassName, $escapedMethodName), sprintf('PHPDoc tag @template %%s for method %s::%s() has invalid bound type %%s.', $escapedClassName, $escapedMethodName), sprintf('PHPDoc tag @template %%s for method %s::%s() with bound type %%s is not supported.', $escapedClassName, $escapedMethodName), sprintf('PHPDoc tag @template %%s for method %s::%s() has invalid default type %%s.', $escapedClassName, $escapedMethodName), sprintf('Default type %%s in PHPDoc tag @template %%s for method %s::%s() is not subtype of bound type %%s.', $escapedClassName, $escapedMethodName), sprintf('PHPDoc tag @template %%s for method %s::%s() does not have a default type but follows an optional @template %%s.', $escapedClassName, $escapedMethodName));
        $classTemplateTypes = $classReflection->getTemplateTypeMap()->getTypes();
        foreach (array_keys($methodTemplateTags) as $name) {
            if (!isset($classTemplateTypes[$name])) {
                continue;
            }
            $messages[] = RuleErrorBuilder::message(sprintf('PHPDoc tag @template %s for method %s::%s() shadows @template %s for class %s.', $name, $className, $methodName, $classTemplateTypes[$name]->describe(VerbosityLevel::typeOnly()), $classReflection->getDisplayName(\false)))->identifier('method.shadowTemplate')->build();
        }
        return $messages;
    }
}
