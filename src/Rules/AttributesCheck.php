<?php

declare (strict_types=1);
namespace PHPStan\Rules;

use Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr\New_;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use function array_key_exists;
use function count;
use function sprintf;
use function strtolower;
final class AttributesCheck
{
    private ReflectionProvider $reflectionProvider;
    private \PHPStan\Rules\FunctionCallParametersCheck $functionCallParametersCheck;
    private \PHPStan\Rules\ClassNameCheck $classCheck;
    private bool $deprecationRulesInstalled;
    public function __construct(ReflectionProvider $reflectionProvider, \PHPStan\Rules\FunctionCallParametersCheck $functionCallParametersCheck, \PHPStan\Rules\ClassNameCheck $classCheck, bool $deprecationRulesInstalled)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->functionCallParametersCheck = $functionCallParametersCheck;
        $this->classCheck = $classCheck;
        $this->deprecationRulesInstalled = $deprecationRulesInstalled;
    }
    /**
     * @param AttributeGroup[] $attrGroups
     * @param int-mask-of<Attribute::TARGET_*> $requiredTarget
     * @return list<IdentifierRuleError>
     */
    public function check(Scope $scope, array $attrGroups, int $requiredTarget, string $targetName): array
    {
        $errors = [];
        $alreadyPresent = [];
        foreach ($attrGroups as $attrGroup) {
            foreach ($attrGroup->attrs as $attribute) {
                $name = $attribute->name->toString();
                if (!$this->reflectionProvider->hasClass($name)) {
                    $errors[] = \PHPStan\Rules\RuleErrorBuilder::message(sprintf('Attribute class %s does not exist.', $name))->line($attribute->getStartLine())->identifier('attribute.notFound')->build();
                    continue;
                }
                $attributeClass = $this->reflectionProvider->getClass($name);
                if (!$attributeClass->isAttributeClass()) {
                    $errors[] = \PHPStan\Rules\RuleErrorBuilder::message(sprintf('%s %s is not an Attribute class.', $attributeClass->getClassTypeDescription(), $attributeClass->getDisplayName()))->identifier('attribute.notAttribute')->line($attribute->getStartLine())->build();
                    continue;
                }
                if ($attributeClass->isAbstract()) {
                    $errors[] = \PHPStan\Rules\RuleErrorBuilder::message(sprintf('Attribute class %s is abstract.', $name))->identifier('attribute.abstract')->line($attribute->getStartLine())->build();
                }
                foreach ($this->classCheck->checkClassNames($scope, [new \PHPStan\Rules\ClassNameNodePair($name, $attribute)], \PHPStan\Rules\ClassNameUsageLocation::from(\PHPStan\Rules\ClassNameUsageLocation::ATTRIBUTE)) as $caseSensitivityError) {
                    $errors[] = $caseSensitivityError;
                }
                $flags = $attributeClass->getAttributeClassFlags();
                if (($flags & $requiredTarget) === 0) {
                    $errors[] = \PHPStan\Rules\RuleErrorBuilder::message(sprintf('Attribute class %s does not have the %s target.', $name, $targetName))->identifier('attribute.target')->line($attribute->getStartLine())->build();
                }
                if (($flags & Attribute::IS_REPEATABLE) === 0) {
                    $loweredName = strtolower($name);
                    if (array_key_exists($loweredName, $alreadyPresent)) {
                        $errors[] = \PHPStan\Rules\RuleErrorBuilder::message(sprintf('Attribute class %s is not repeatable but is already present above the %s.', $name, $targetName))->identifier('attribute.nonRepeatable')->line($attribute->getStartLine())->build();
                    }
                    $alreadyPresent[$loweredName] = \true;
                }
                if ($this->deprecationRulesInstalled && $attributeClass->isDeprecated()) {
                    if ($attributeClass->getDeprecatedDescription() !== null) {
                        $deprecatedError = sprintf('Attribute class %s is deprecated: %s', $name, $attributeClass->getDeprecatedDescription());
                    } else {
                        $deprecatedError = sprintf('Attribute class %s is deprecated.', $name);
                    }
                    $errors[] = \PHPStan\Rules\RuleErrorBuilder::message($deprecatedError)->identifier('attribute.deprecated')->line($attribute->getStartLine())->build();
                }
                if (!$attributeClass->hasConstructor()) {
                    if (count($attribute->args) > 0) {
                        $errors[] = \PHPStan\Rules\RuleErrorBuilder::message(sprintf('Attribute class %s does not have a constructor and must be instantiated without any parameters.', $name))->identifier('attribute.noConstructor')->line($attribute->getStartLine())->build();
                    }
                    continue;
                }
                $attributeConstructor = $attributeClass->getConstructor();
                if (!$attributeConstructor->isPublic()) {
                    $errors[] = \PHPStan\Rules\RuleErrorBuilder::message(sprintf('Constructor of attribute class %s is not public.', $name))->identifier('attribute.constructorNotPublic')->line($attribute->getStartLine())->build();
                }
                $attributeClassName = SprintfHelper::escapeFormatString($attributeClass->getDisplayName());
                $nodeAttributes = $attribute->getAttributes();
                $nodeAttributes['isAttribute'] = \true;
                $parameterErrors = $this->functionCallParametersCheck->check(
                    ParametersAcceptorSelector::selectFromArgs($scope, $attribute->args, $attributeConstructor->getVariants(), $attributeConstructor->getNamedArgumentsVariants()),
                    $scope,
                    $attributeConstructor->getDeclaringClass()->isBuiltin(),
                    new New_($attribute->name, $attribute->args, $nodeAttributes),
                    'attribute',
                    $attributeConstructor->acceptsNamedArguments(),
                    'Attribute class ' . $attributeClassName . ' constructor invoked with %d parameter, %d required.',
                    'Attribute class ' . $attributeClassName . ' constructor invoked with %d parameters, %d required.',
                    'Attribute class ' . $attributeClassName . ' constructor invoked with %d parameter, at least %d required.',
                    'Attribute class ' . $attributeClassName . ' constructor invoked with %d parameters, at least %d required.',
                    'Attribute class ' . $attributeClassName . ' constructor invoked with %d parameter, %d-%d required.',
                    'Attribute class ' . $attributeClassName . ' constructor invoked with %d parameters, %d-%d required.',
                    '%s of attribute class ' . $attributeClassName . ' constructor expects %s, %s given.',
                    '',
                    // constructor does not have a return type
                    '%s of attribute class ' . $attributeClassName . ' constructor is passed by reference, so it expects variables only',
                    'Unable to resolve the template type %s in instantiation of attribute class ' . $attributeClassName,
                    'Missing parameter $%s in call to ' . $attributeClassName . ' constructor.',
                    'Unknown parameter $%s in call to ' . $attributeClassName . ' constructor.',
                    'Return type of call to ' . $attributeClassName . ' constructor contains unresolvable type.',
                    '%s of attribute class ' . $attributeClassName . ' constructor contains unresolvable type.',
                    'Attribute class ' . $attributeClassName . ' constructor invoked with %s, but it\'s not allowed because of @no-named-arguments.'
                );
                foreach ($parameterErrors as $error) {
                    $errors[] = $error;
                }
            }
        }
        return $errors;
    }
}
