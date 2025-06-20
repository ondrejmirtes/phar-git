<?php

declare (strict_types=1);
namespace PHPStan\Rules\Classes;

use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\Container;
use PHPStan\Internal\SprintfHelper;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\Php\PhpMethodReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\ClassNameNodePair;
use PHPStan\Rules\ClassNameUsageLocation;
use PHPStan\Rules\FunctionCallParametersCheck;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RestrictedUsage\RestrictedMethodUsageExtension;
use PHPStan\Rules\RestrictedUsage\RewrittenDeclaringClassMethodReflection;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Constant\ConstantStringType;
use function array_filter;
use function array_map;
use function array_merge;
use function count;
use function sprintf;
use function strtolower;
/**
 * @implements Rule<Node\Expr\New_>
 */
final class InstantiationRule implements Rule
{
    private Container $container;
    private ReflectionProvider $reflectionProvider;
    private FunctionCallParametersCheck $check;
    private ClassNameCheck $classCheck;
    private bool $discoveringSymbolsTip;
    public function __construct(Container $container, ReflectionProvider $reflectionProvider, FunctionCallParametersCheck $check, ClassNameCheck $classCheck, bool $discoveringSymbolsTip)
    {
        $this->container = $container;
        $this->reflectionProvider = $reflectionProvider;
        $this->check = $check;
        $this->classCheck = $classCheck;
        $this->discoveringSymbolsTip = $discoveringSymbolsTip;
    }
    public function getNodeType(): string
    {
        return New_::class;
    }
    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];
        foreach ($this->getClassNames($node, $scope) as [$class, $isName]) {
            $errors = array_merge($errors, $this->checkClassName($class, $isName, $node, $scope));
        }
        return $errors;
    }
    /**
     * @param Node\Expr\New_ $node
     * @return list<IdentifierRuleError>
     */
    private function checkClassName(string $class, bool $isName, Node $node, Scope $scope): array
    {
        $lowercasedClass = strtolower($class);
        $messages = [];
        $isStatic = \false;
        if ($lowercasedClass === 'static') {
            if (!$scope->isInClass()) {
                return [RuleErrorBuilder::message(sprintf('Using %s outside of class scope.', $class))->identifier('outOfClass.static')->build()];
            }
            $isStatic = \true;
            $classReflection = $scope->getClassReflection();
            if (!$classReflection->isFinal()) {
                if (!$classReflection->hasConstructor()) {
                    return [];
                }
                $constructor = $classReflection->getConstructor();
                if (!$constructor->getPrototype()->getDeclaringClass()->isInterface() && $constructor instanceof PhpMethodReflection && !$constructor->isFinal()->yes() && !$constructor->getPrototype()->isAbstract()) {
                    return [];
                }
            }
        } elseif ($lowercasedClass === 'self') {
            if (!$scope->isInClass()) {
                return [RuleErrorBuilder::message(sprintf('Using %s outside of class scope.', $class))->identifier('outOfClass.self')->build()];
            }
            $classReflection = $scope->getClassReflection();
        } elseif ($lowercasedClass === 'parent') {
            if (!$scope->isInClass()) {
                return [RuleErrorBuilder::message(sprintf('Using %s outside of class scope.', $class))->identifier('outOfClass.parent')->build()];
            }
            if ($scope->getClassReflection()->getParentClass() === null) {
                return [RuleErrorBuilder::message(sprintf('%s::%s() calls new parent but %s does not extend any class.', $scope->getClassReflection()->getDisplayName(), $scope->getFunctionName(), $scope->getClassReflection()->getDisplayName()))->identifier('class.noParent')->build()];
            }
            $classReflection = $scope->getClassReflection()->getParentClass();
        } else {
            if (!$this->reflectionProvider->hasClass($class)) {
                if ($scope->isInClassExists($class)) {
                    return [];
                }
                $errorBuilder = RuleErrorBuilder::message(sprintf('Instantiated class %s not found.', $class))->identifier('class.notFound');
                if ($this->discoveringSymbolsTip) {
                    $errorBuilder->discoveringSymbolsTip();
                }
                return [$errorBuilder->build()];
            }
            $messages = $this->classCheck->checkClassNames($scope, [new ClassNameNodePair($class, $node->class)], ClassNameUsageLocation::from(ClassNameUsageLocation::INSTANTIATION));
            $classReflection = $this->reflectionProvider->getClass($class);
        }
        if ($classReflection->isEnum() && $isName) {
            return [RuleErrorBuilder::message(sprintf('Cannot instantiate enum %s.', $classReflection->getDisplayName()))->identifier('new.enum')->build()];
        }
        if (!$isStatic && $classReflection->isInterface() && $isName) {
            return [RuleErrorBuilder::message(sprintf('Cannot instantiate interface %s.', $classReflection->getDisplayName()))->identifier('new.interface')->build()];
        }
        if (!$isStatic && $classReflection->isAbstract() && $isName) {
            return [RuleErrorBuilder::message(sprintf('Instantiated class %s is abstract.', $classReflection->getDisplayName()))->identifier('new.abstract')->build()];
        }
        if (!$isName) {
            return [];
        }
        if (!$classReflection->hasConstructor()) {
            if (count($node->getArgs()) > 0) {
                return array_merge($messages, [RuleErrorBuilder::message(sprintf('Class %s does not have a constructor and must be instantiated without any parameters.', $classReflection->getDisplayName()))->identifier('new.noConstructor')->build()]);
            }
            return $messages;
        }
        $constructorReflection = $classReflection->getConstructor();
        if (!$scope->canCallMethod($constructorReflection)) {
            $messages[] = RuleErrorBuilder::message(sprintf('Cannot instantiate class %s via %s constructor %s::%s().', $classReflection->getDisplayName(), $constructorReflection->isPrivate() ? 'private' : 'protected', $constructorReflection->getDeclaringClass()->getDisplayName(), $constructorReflection->getName()))->identifier(sprintf('new.%sConstructor', $constructorReflection->isPrivate() ? 'private' : 'protected'))->build();
        }
        /** @var RestrictedMethodUsageExtension[] $restrictedUsageExtensions */
        $restrictedUsageExtensions = $this->container->getServicesByTag(RestrictedMethodUsageExtension::METHOD_EXTENSION_TAG);
        foreach ($restrictedUsageExtensions as $extension) {
            $restrictedUsage = $extension->isRestrictedMethodUsage($constructorReflection, $scope);
            if ($restrictedUsage === null) {
                continue;
            }
            if ($classReflection->getName() !== $constructorReflection->getDeclaringClass()->getName()) {
                $rewrittenConstructorReflection = new RewrittenDeclaringClassMethodReflection($classReflection, $constructorReflection);
                $rewrittenRestrictedUsage = $extension->isRestrictedMethodUsage($rewrittenConstructorReflection, $scope);
                if ($rewrittenRestrictedUsage === null) {
                    continue;
                }
            }
            $messages[] = RuleErrorBuilder::message($restrictedUsage->errorMessage)->identifier($restrictedUsage->identifier)->build();
        }
        $classDisplayName = SprintfHelper::escapeFormatString($classReflection->getDisplayName());
        return array_merge($messages, $this->check->check(
            ParametersAcceptorSelector::selectFromArgs($scope, $node->getArgs(), $constructorReflection->getVariants(), $constructorReflection->getNamedArgumentsVariants()),
            $scope,
            $constructorReflection->getDeclaringClass()->isBuiltin(),
            $node,
            'new',
            $constructorReflection->acceptsNamedArguments(),
            'Class ' . $classDisplayName . ' constructor invoked with %d parameter, %d required.',
            'Class ' . $classDisplayName . ' constructor invoked with %d parameters, %d required.',
            'Class ' . $classDisplayName . ' constructor invoked with %d parameter, at least %d required.',
            'Class ' . $classDisplayName . ' constructor invoked with %d parameters, at least %d required.',
            'Class ' . $classDisplayName . ' constructor invoked with %d parameter, %d-%d required.',
            'Class ' . $classDisplayName . ' constructor invoked with %d parameters, %d-%d required.',
            '%s of class ' . $classDisplayName . ' constructor expects %s, %s given.',
            '',
            // constructor does not have a return type
            ' %s of class ' . $classDisplayName . ' constructor is passed by reference, so it expects variables only',
            'Unable to resolve the template type %s in instantiation of class ' . $classDisplayName,
            'Missing parameter $%s in call to ' . $classDisplayName . ' constructor.',
            'Unknown parameter $%s in call to ' . $classDisplayName . ' constructor.',
            'Return type of call to ' . $classDisplayName . ' constructor contains unresolvable type.',
            '%s of class ' . $classDisplayName . ' constructor contains unresolvable type.',
            'Class ' . $classDisplayName . ' constructor invoked with %s, but it\'s not allowed because of @no-named-arguments.'
        ));
    }
    /**
     * @param Node\Expr\New_ $node
     * @return array<int, array{string, bool}>
     */
    private function getClassNames(Node $node, Scope $scope): array
    {
        if ($node->class instanceof Node\Name) {
            return [[(string) $node->class, \true]];
        }
        if ($node->class instanceof Node\Stmt\Class_) {
            $classNames = $scope->getType($node)->getObjectClassNames();
            if ($classNames === []) {
                throw new ShouldNotHappenException();
            }
            return array_map(static fn(string $className) => [$className, \true], $classNames);
        }
        $type = $scope->getType($node->class);
        if ($type->isClassString()->yes()) {
            $concretes = array_filter($type->getClassStringObjectType()->getObjectClassReflections(), static fn(ClassReflection $classReflection): bool => !$classReflection->isAbstract() && !$classReflection->isInterface());
            if (count($concretes) > 0) {
                return array_map(static fn(ClassReflection $classReflection): array => [$classReflection->getName(), \true], $concretes);
            }
        }
        return array_merge(array_map(static fn(ConstantStringType $type): array => [$type->getValue(), \true], $type->getConstantStrings()), array_map(static fn(string $name): array => [$name, \false], $type->getObjectClassNames()));
    }
}
