<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PHPStan\DependencyInjection\Type\DynamicReturnTypeExtensionRegistryProvider;
use PHPStan\DependencyInjection\Type\ExpressionTypeResolverExtensionRegistryProvider;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Parser\Parser;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\AttributeReflectionFactory;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Properties\PropertyReflectionFinder;
final class DirectInternalScopeFactory implements \PHPStan\Analyser\InternalScopeFactory
{
    private ReflectionProvider $reflectionProvider;
    private InitializerExprTypeResolver $initializerExprTypeResolver;
    private DynamicReturnTypeExtensionRegistryProvider $dynamicReturnTypeExtensionRegistryProvider;
    private ExpressionTypeResolverExtensionRegistryProvider $expressionTypeResolverExtensionRegistryProvider;
    private ExprPrinter $exprPrinter;
    private \PHPStan\Analyser\TypeSpecifier $typeSpecifier;
    private PropertyReflectionFinder $propertyReflectionFinder;
    private Parser $parser;
    private \PHPStan\Analyser\NodeScopeResolver $nodeScopeResolver;
    private \PHPStan\Analyser\RicherScopeGetTypeHelper $richerScopeGetTypeHelper;
    private PhpVersion $phpVersion;
    private AttributeReflectionFactory $attributeReflectionFactory;
    /**
     * @var int|array{min: int, max: int}|null
     */
    private $configPhpVersion;
    private \PHPStan\Analyser\ConstantResolver $constantResolver;
    /**
     * @param int|array{min: int, max: int}|null $configPhpVersion
     */
    public function __construct(ReflectionProvider $reflectionProvider, InitializerExprTypeResolver $initializerExprTypeResolver, DynamicReturnTypeExtensionRegistryProvider $dynamicReturnTypeExtensionRegistryProvider, ExpressionTypeResolverExtensionRegistryProvider $expressionTypeResolverExtensionRegistryProvider, ExprPrinter $exprPrinter, \PHPStan\Analyser\TypeSpecifier $typeSpecifier, PropertyReflectionFinder $propertyReflectionFinder, Parser $parser, \PHPStan\Analyser\NodeScopeResolver $nodeScopeResolver, \PHPStan\Analyser\RicherScopeGetTypeHelper $richerScopeGetTypeHelper, PhpVersion $phpVersion, AttributeReflectionFactory $attributeReflectionFactory, $configPhpVersion, \PHPStan\Analyser\ConstantResolver $constantResolver)
    {
        $this->reflectionProvider = $reflectionProvider;
        $this->initializerExprTypeResolver = $initializerExprTypeResolver;
        $this->dynamicReturnTypeExtensionRegistryProvider = $dynamicReturnTypeExtensionRegistryProvider;
        $this->expressionTypeResolverExtensionRegistryProvider = $expressionTypeResolverExtensionRegistryProvider;
        $this->exprPrinter = $exprPrinter;
        $this->typeSpecifier = $typeSpecifier;
        $this->propertyReflectionFinder = $propertyReflectionFinder;
        $this->parser = $parser;
        $this->nodeScopeResolver = $nodeScopeResolver;
        $this->richerScopeGetTypeHelper = $richerScopeGetTypeHelper;
        $this->phpVersion = $phpVersion;
        $this->attributeReflectionFactory = $attributeReflectionFactory;
        $this->configPhpVersion = $configPhpVersion;
        $this->constantResolver = $constantResolver;
    }
    /**
     * @param \PHPStan\Reflection\Php\PhpFunctionFromParserNodeReflection|null $function
     */
    public function create(\PHPStan\Analyser\ScopeContext $context, bool $declareStrictTypes = \false, $function = null, ?string $namespace = null, array $expressionTypes = [], array $nativeExpressionTypes = [], array $conditionalExpressions = [], array $inClosureBindScopeClasses = [], ?ParametersAcceptor $anonymousFunctionReflection = null, bool $inFirstLevelStatement = \true, array $currentlyAssignedExpressions = [], array $currentlyAllowedUndefinedExpressions = [], array $inFunctionCallsStack = [], bool $afterExtractCall = \false, ?\PHPStan\Analyser\Scope $parentScope = null, bool $nativeTypesPromoted = \false) : \PHPStan\Analyser\MutatingScope
    {
        return new \PHPStan\Analyser\MutatingScope($this, $this->reflectionProvider, $this->initializerExprTypeResolver, $this->dynamicReturnTypeExtensionRegistryProvider->getRegistry(), $this->expressionTypeResolverExtensionRegistryProvider->getRegistry(), $this->exprPrinter, $this->typeSpecifier, $this->propertyReflectionFinder, $this->parser, $this->nodeScopeResolver, $this->richerScopeGetTypeHelper, $this->constantResolver, $context, $this->phpVersion, $this->attributeReflectionFactory, $this->configPhpVersion, $declareStrictTypes, $function, $namespace, $expressionTypes, $nativeExpressionTypes, $conditionalExpressions, $inClosureBindScopeClasses, $anonymousFunctionReflection, $inFirstLevelStatement, $currentlyAssignedExpressions, $currentlyAllowedUndefinedExpressions, $inFunctionCallsStack, $afterExtractCall, $parentScope, $nativeTypesPromoted);
    }
}
