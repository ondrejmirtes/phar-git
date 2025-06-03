<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\Type\DynamicReturnTypeExtensionRegistryProvider;
use PHPStan\DependencyInjection\Type\ExpressionTypeResolverExtensionRegistryProvider;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\AttributeReflectionFactory;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
final class LazyInternalScopeFactory implements \PHPStan\Analyser\InternalScopeFactory
{
    private Container $container;
    /** @var int|array{min: int, max: int}|null */
    private $phpVersion;
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->phpVersion = $this->container->getParameter('phpVersion');
    }
    /**
     * @param \PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection|null $function
     */
    public function create(\PHPStan\Analyser\ScopeContext $context, bool $declareStrictTypes = \false, $function = null, ?string $namespace = null, array $expressionTypes = [], array $nativeExpressionTypes = [], array $conditionalExpressions = [], array $inClosureBindScopeClasses = [], ?ParametersAcceptor $anonymousFunctionReflection = null, bool $inFirstLevelStatement = \true, array $currentlyAssignedExpressions = [], array $currentlyAllowedUndefinedExpressions = [], array $inFunctionCallsStack = [], bool $afterExtractCall = \false, ?\PHPStan\Analyser\Scope $parentScope = null, bool $nativeTypesPromoted = \false): \PHPStan\Analyser\MutatingScope
    {
        return new \PHPStan\Analyser\MutatingScope($this, $this->container->getByType(ReflectionProvider::class), $this->container->getByType(InitializerExprTypeResolver::class), $this->container->getByType(DynamicReturnTypeExtensionRegistryProvider::class)->getRegistry(), $this->container->getByType(ExpressionTypeResolverExtensionRegistryProvider::class)->getRegistry(), $this->container->getByType(ExprPrinter::class), $this->container->getByType(\PHPStan\Analyser\TypeSpecifier::class), $this->container->getByType(PropertyReflectionFinder::class), $this->container->getService('currentPhpVersionSimpleParser'), $this->container->getByType(\PHPStan\Analyser\NodeScopeResolver::class), $this->container->getByType(\PHPStan\Analyser\RicherScopeGetTypeHelper::class), $this->container->getByType(\PHPStan\Analyser\ConstantResolver::class), $context, $this->container->getByType(PhpVersion::class), $this->container->getByType(AttributeReflectionFactory::class), $this->phpVersion, $declareStrictTypes, $function, $namespace, $expressionTypes, $nativeExpressionTypes, $conditionalExpressions, $inClosureBindScopeClasses, $anonymousFunctionReflection, $inFirstLevelStatement, $currentlyAssignedExpressions, $currentlyAllowedUndefinedExpressions, $inFunctionCallsStack, $afterExtractCall, $parentScope, $nativeTypesPromoted);
    }
}
