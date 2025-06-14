<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Php;

use PHPStan\BetterReflection\Reflection\Adapter\ReflectionFunction;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionParameter;
use PHPStan\Internal\DeprecatedAttributeHelper;
use PHPStan\Parser\Parser;
use PHPStan\Parser\VariadicFunctionsVisitor;
use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\AttributeReflection;
use PHPStan\Reflection\AttributeReflectionFactory;
use PHPStan\Reflection\ExtendedFunctionVariant;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypehintHelper;
use function array_key_exists;
use function array_map;
use function count;
use function is_array;
use function is_file;
final class PhpFunctionReflection implements FunctionReflection
{
    private InitializerExprTypeResolver $initializerExprTypeResolver;
    private ReflectionFunction $reflection;
    private Parser $parser;
    private AttributeReflectionFactory $attributeReflectionFactory;
    private TemplateTypeMap $templateTypeMap;
    /**
     * @var array<string, Type>
     */
    private array $phpDocParameterTypes;
    private ?Type $phpDocReturnType;
    private ?Type $phpDocThrowType;
    private ?string $deprecatedDescription;
    private bool $isDeprecated;
    private bool $isInternal;
    private ?string $filename;
    private ?bool $isPure;
    private Assertions $asserts;
    private bool $acceptsNamedArguments;
    private ?string $phpDocComment;
    /**
     * @var array<string, Type>
     */
    private array $phpDocParameterOutTypes;
    /**
     * @var array<string, bool>
     */
    private array $phpDocParameterImmediatelyInvokedCallable;
    /**
     * @var array<string, Type>
     */
    private array $phpDocParameterClosureThisTypes;
    /**
     * @var list<AttributeReflection>
     */
    private array $attributes;
    /** @var list<ExtendedFunctionVariant>|null */
    private ?array $variants = null;
    private ?bool $containsVariadicCalls = null;
    /**
     * @param array<string, Type> $phpDocParameterTypes
     * @param array<string, Type> $phpDocParameterOutTypes
     * @param array<string, bool> $phpDocParameterImmediatelyInvokedCallable
     * @param array<string, Type> $phpDocParameterClosureThisTypes
     * @param list<AttributeReflection> $attributes
     */
    public function __construct(InitializerExprTypeResolver $initializerExprTypeResolver, ReflectionFunction $reflection, Parser $parser, AttributeReflectionFactory $attributeReflectionFactory, TemplateTypeMap $templateTypeMap, array $phpDocParameterTypes, ?Type $phpDocReturnType, ?Type $phpDocThrowType, ?string $deprecatedDescription, bool $isDeprecated, bool $isInternal, ?string $filename, ?bool $isPure, Assertions $asserts, bool $acceptsNamedArguments, ?string $phpDocComment, array $phpDocParameterOutTypes, array $phpDocParameterImmediatelyInvokedCallable, array $phpDocParameterClosureThisTypes, array $attributes)
    {
        $this->initializerExprTypeResolver = $initializerExprTypeResolver;
        $this->reflection = $reflection;
        $this->parser = $parser;
        $this->attributeReflectionFactory = $attributeReflectionFactory;
        $this->templateTypeMap = $templateTypeMap;
        $this->phpDocParameterTypes = $phpDocParameterTypes;
        $this->phpDocReturnType = $phpDocReturnType;
        $this->phpDocThrowType = $phpDocThrowType;
        $this->deprecatedDescription = $deprecatedDescription;
        $this->isDeprecated = $isDeprecated;
        $this->isInternal = $isInternal;
        $this->filename = $filename;
        $this->isPure = $isPure;
        $this->asserts = $asserts;
        $this->acceptsNamedArguments = $acceptsNamedArguments;
        $this->phpDocComment = $phpDocComment;
        $this->phpDocParameterOutTypes = $phpDocParameterOutTypes;
        $this->phpDocParameterImmediatelyInvokedCallable = $phpDocParameterImmediatelyInvokedCallable;
        $this->phpDocParameterClosureThisTypes = $phpDocParameterClosureThisTypes;
        $this->attributes = $attributes;
    }
    public function getName(): string
    {
        return $this->reflection->getName();
    }
    public function getFileName(): ?string
    {
        if ($this->filename === null) {
            return null;
        }
        if (!is_file($this->filename)) {
            return null;
        }
        return $this->filename;
    }
    public function getVariants(): array
    {
        if ($this->variants === null) {
            $this->variants = [new ExtendedFunctionVariant($this->templateTypeMap, null, $this->getParameters(), $this->isVariadic(), $this->getReturnType(), $this->getPhpDocReturnType(), $this->getNativeReturnType())];
        }
        return $this->variants;
    }
    public function getOnlyVariant(): ExtendedParametersAcceptor
    {
        return $this->getVariants()[0];
    }
    public function getNamedArgumentsVariants(): ?array
    {
        return null;
    }
    /**
     * @return list<ExtendedParameterReflection>
     */
    private function getParameters(): array
    {
        return array_map(function (ReflectionParameter $reflection): \PHPStan\Reflection\Php\PhpParameterReflection {
            if (array_key_exists($reflection->getName(), $this->phpDocParameterImmediatelyInvokedCallable)) {
                $immediatelyInvokedCallable = TrinaryLogic::createFromBoolean($this->phpDocParameterImmediatelyInvokedCallable[$reflection->getName()]);
            } else {
                $immediatelyInvokedCallable = TrinaryLogic::createMaybe();
            }
            return new \PHPStan\Reflection\Php\PhpParameterReflection($this->initializerExprTypeResolver, $reflection, $this->phpDocParameterTypes[$reflection->getName()] ?? null, null, $this->phpDocParameterOutTypes[$reflection->getName()] ?? null, $immediatelyInvokedCallable, $this->phpDocParameterClosureThisTypes[$reflection->getName()] ?? null, $this->attributeReflectionFactory->fromNativeReflection($reflection->getAttributes(), InitializerExprContext::fromReflectionParameter($reflection)));
        }, $this->reflection->getParameters());
    }
    private function isVariadic(): bool
    {
        $isNativelyVariadic = $this->reflection->isVariadic();
        if (!$isNativelyVariadic && $this->reflection->getFileName() !== \false && !$this->isBuiltin()) {
            $filename = $this->reflection->getFileName();
            if ($this->containsVariadicCalls !== null) {
                return $this->containsVariadicCalls;
            }
            if (array_key_exists($this->reflection->getName(), VariadicFunctionsVisitor::$cache)) {
                return $this->containsVariadicCalls = VariadicFunctionsVisitor::$cache[$this->reflection->getName()];
            }
            $nodes = $this->parser->parseFile($filename);
            if (count($nodes) > 0) {
                $variadicFunctions = $nodes[0]->getAttribute(VariadicFunctionsVisitor::ATTRIBUTE_NAME);
                if (is_array($variadicFunctions) && array_key_exists($this->reflection->getName(), $variadicFunctions)) {
                    return $this->containsVariadicCalls = $variadicFunctions[$this->reflection->getName()];
                }
            }
            return $this->containsVariadicCalls = \false;
        }
        return $isNativelyVariadic;
    }
    private function getReturnType(): Type
    {
        return TypehintHelper::decideTypeFromReflection($this->reflection->getReturnType(), $this->phpDocReturnType);
    }
    private function getPhpDocReturnType(): Type
    {
        if ($this->phpDocReturnType !== null) {
            return $this->phpDocReturnType;
        }
        return new MixedType();
    }
    private function getNativeReturnType(): Type
    {
        return TypehintHelper::decideTypeFromReflection($this->reflection->getReturnType());
    }
    public function getDeprecatedDescription(): ?string
    {
        if ($this->isDeprecated) {
            return $this->deprecatedDescription;
        }
        if ($this->reflection->isDeprecated()) {
            $attributes = $this->reflection->getBetterReflection()->getAttributes();
            return DeprecatedAttributeHelper::getDeprecatedDescription($attributes);
        }
        return null;
    }
    public function isDeprecated(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->isDeprecated || $this->reflection->isDeprecated());
    }
    public function isInternal(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->isInternal);
    }
    public function getThrowType(): ?Type
    {
        return $this->phpDocThrowType;
    }
    public function hasSideEffects(): TrinaryLogic
    {
        if ($this->getReturnType()->isVoid()->yes()) {
            return TrinaryLogic::createYes();
        }
        if ($this->isPure !== null) {
            return TrinaryLogic::createFromBoolean(!$this->isPure);
        }
        return TrinaryLogic::createMaybe();
    }
    public function isPure(): TrinaryLogic
    {
        if ($this->isPure === null) {
            return TrinaryLogic::createMaybe();
        }
        return TrinaryLogic::createFromBoolean($this->isPure);
    }
    public function isBuiltin(): bool
    {
        return $this->reflection->isInternal();
    }
    public function getAsserts(): Assertions
    {
        return $this->asserts;
    }
    public function getDocComment(): ?string
    {
        return $this->phpDocComment;
    }
    public function returnsByReference(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->reflection->returnsReference());
    }
    public function acceptsNamedArguments(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->acceptsNamedArguments);
    }
    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
