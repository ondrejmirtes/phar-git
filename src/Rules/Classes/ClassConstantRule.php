<?php

declare (strict_types=1);
namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\NullsafeOperatorHelper;
use PHPStan\Analyser\Scope;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\ClassNameUsageLocation;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Type\ErrorType;
use PHPStan\Type\StringType;
use PHPStan\Type\ThisType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function in_array;
use function sprintf;
use function strtolower;
/**
 * @implements Rule<Node\Expr\ClassConstFetch>
 */
final class ClassConstantRule implements Rule
{
    private ReflectionProvider $reflectionProvider;
    private RuleLevelHelper $ruleLevelHelper;
    private ClassNameCheck $classCheck;
    private PhpVersion $phpVersion;
    public function __construct(ReflectionProvider $reflectionProvider, RuleLevelHelper $ruleLevelHelper, ClassNameCheck $classCheck, PhpVersion $phpVersion)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->ruleLevelHelper = $ruleLevelHelper;
        $this->classCheck = $classCheck;
        $this->phpVersion = $phpVersion;
    }
    public function getNodeType(): string
    {
        return ClassConstFetch::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];
        if ($node->name instanceof Node\Identifier) {
            $constantNameScopes = [$node->name->name => $scope];
        } else {
            $nameType = $scope->getType($node->name);
            $constantNameScopes = [];
            foreach ($nameType->getConstantStrings() as $constantString) {
                $name = $constantString->getValue();
                $constantNameScopes[$name] = $scope->filterByTruthyValue(new Identical($node->name, new String_($name)));
            }
        }
        foreach ($constantNameScopes as $constantName => $constantScope) {
            $errors = array_merge($errors, $this->processSingleClassConstFetch($constantScope, $node, (string) $constantName));
        }
        return $errors;
    }
    /**
     * @return list<IdentifierRuleError>
     */
    private function processSingleClassConstFetch(Scope $scope, ClassConstFetch $node, string $constantName): array
    {
        $class = $node->class;
        $messages = [];
        if ($class instanceof Node\Name) {
            $className = (string) $class;
            $lowercasedClassName = strtolower($className);
            if (in_array($lowercasedClassName, ['self', 'static'], \true)) {
                if (!$scope->isInClass()) {
                    return [RuleErrorBuilder::message(sprintf('Using %s outside of class scope.', $className))->identifier(sprintf('outOfClass.%s', $lowercasedClassName))->build()];
                }
                $classType = $scope->resolveTypeByName($class);
            } elseif ($lowercasedClassName === 'parent') {
                if (!$scope->isInClass()) {
                    return [RuleErrorBuilder::message(sprintf('Using %s outside of class scope.', $className))->identifier(sprintf('outOfClass.%s', $lowercasedClassName))->build()];
                }
                $currentClassReflection = $scope->getClassReflection();
                if ($currentClassReflection->getParentClass() === null) {
                    return [RuleErrorBuilder::message(sprintf('Access to parent::%s but %s does not extend any class.', $constantName, $currentClassReflection->getDisplayName()))->identifier('class.noParent')->build()];
                }
                $classType = $scope->resolveTypeByName($class);
            } else {
                if (!$this->reflectionProvider->hasClass($className)) {
                    if ($scope->isInClassExists($className)) {
                        return [];
                    }
                    if (strtolower($constantName) === 'class') {
                        return [RuleErrorBuilder::message(sprintf('Class %s not found.', $className))->identifier('class.notFound')->discoveringSymbolsTip()->build()];
                    }
                    return [RuleErrorBuilder::message(sprintf('Access to constant %s on an unknown class %s.', $constantName, $className))->identifier('class.notFound')->discoveringSymbolsTip()->build()];
                }
                $classType = $scope->resolveTypeByName($class);
                if (strtolower($constantName) !== 'class') {
                    foreach ($classType->getObjectClassReflections() as $classTypeReflection) {
                        if (!$classTypeReflection->isTrait()) {
                            continue;
                        }
                        return [RuleErrorBuilder::message(sprintf('Cannot access constant %s on trait %s.', $constantName, $classTypeReflection->getDisplayName()))->identifier('classConstant.onTrait')->build()];
                    }
                }
                $locationData = [];
                $locationClassReflection = $this->reflectionProvider->getClass($className);
                if ($locationClassReflection->hasConstant($constantName)) {
                    $locationData['classConstant'] = $locationClassReflection->getConstant($constantName);
                }
                $messages = $this->classCheck->checkClassNames($scope, [new ClassNameNodePair($className, $class)], ClassNameUsageLocation::from(ClassNameUsageLocation::CLASS_CONSTANT_ACCESS, $locationData));
            }
            if (strtolower($constantName) === 'class') {
                return $messages;
            }
        } else {
            $classTypeResult = $this->ruleLevelHelper->findTypeToCheck($scope, NullsafeOperatorHelper::getNullsafeShortcircuitedExprRespectingScope($scope, $class), sprintf('Access to constant %s on an unknown class %%s.', SprintfHelper::escapeFormatString($constantName)), static fn(Type $type): bool => $type->canAccessConstants()->yes() && $type->hasConstant($constantName)->yes());
            $classType = $classTypeResult->getType();
            if ($classType instanceof ErrorType) {
                return $classTypeResult->getUnknownClassErrors();
            }
            if (strtolower($constantName) === 'class') {
                if (!$this->phpVersion->supportsClassConstantOnExpression()) {
                    return [RuleErrorBuilder::message('Accessing ::class constant on an expression is supported only on PHP 8.0 and later.')->identifier('classConstant.notSupported')->nonIgnorable()->build()];
                }
                if (!$class instanceof Node\Scalar\String_ && $classType->isString()->yes()) {
                    return [RuleErrorBuilder::message('Accessing ::class constant on a dynamic string is not supported in PHP.')->identifier('classConstant.dynamicString')->nonIgnorable()->build()];
                }
            }
        }
        if ($classType->isString()->yes()) {
            return $messages;
        }
        $typeForDescribe = $classType;
        if ($classType instanceof ThisType) {
            $typeForDescribe = $classType->getStaticObjectType();
        }
        $classType = TypeCombinator::remove($classType, new StringType());
        if (!$classType->canAccessConstants()->yes()) {
            return array_merge($messages, [RuleErrorBuilder::message(sprintf('Cannot access constant %s on %s.', $constantName, $typeForDescribe->describe(VerbosityLevel::typeOnly())))->identifier('classConstant.nonObject')->build()]);
        }
        if (strtolower($constantName) === 'class' || $scope->hasExpressionType($node)->yes()) {
            return $messages;
        }
        if (!$classType->hasConstant($constantName)->yes()) {
            return array_merge($messages, [RuleErrorBuilder::message(sprintf('Access to undefined constant %s::%s.', $typeForDescribe->describe(VerbosityLevel::typeOnly()), $constantName))->identifier('classConstant.notFound')->build()]);
        }
        $constantReflection = $classType->getConstant($constantName);
        if (!$scope->canAccessConstant($constantReflection)) {
            return array_merge($messages, [RuleErrorBuilder::message(sprintf('Access to %s constant %s of class %s.', $constantReflection->isPrivate() ? 'private' : 'protected', $constantName, $constantReflection->getDeclaringClass()->getDisplayName()))->identifier(sprintf('classConstant.%s', $constantReflection->isPrivate() ? 'private' : 'protected'))->build()]);
        }
        return $messages;
    }
}
