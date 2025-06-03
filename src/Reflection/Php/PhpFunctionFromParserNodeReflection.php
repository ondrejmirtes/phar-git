<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Php;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\AttributeReflection;
use PHPStan\Reflection\ExtendedFunctionVariant;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypehintHelper;
use function array_reverse;
use function is_array;
use function is_string;
/**
 * @api
 */
class PhpFunctionFromParserNodeReflection implements FunctionReflection, ExtendedParametersAcceptor
{
    private string $fileName;
    private TemplateTypeMap $templateTypeMap;
    /**
     * @var Type[]
     */
    private array $realParameterTypes;
    /**
     * @var Type[]
     */
    private array $phpDocParameterTypes;
    /**
     * @var Type[]
     */
    private array $realParameterDefaultValues;
    /**
     * @var array<string, list<AttributeReflection>>
     */
    private array $parameterAttributes;
    private Type $realReturnType;
    private ?Type $phpDocReturnType;
    private ?Type $throwType;
    private ?string $deprecatedDescription;
    private bool $isDeprecated;
    private bool $isInternal;
    protected ?bool $isPure;
    private bool $acceptsNamedArguments;
    private Assertions $assertions;
    private ?string $phpDocComment;
    /**
     * @var Type[]
     */
    private array $parameterOutTypes;
    /**
     * @var array<string, bool>
     */
    private array $immediatelyInvokedCallableParameters;
    /**
     * @var array<string, Type>
     */
    private array $phpDocClosureThisTypeParameters;
    /**
     * @var list<AttributeReflection>
     */
    private array $attributes;
    /** @var Function_|ClassMethod|Node\PropertyHook */
    private Node\FunctionLike $functionLike;
    /** @var list<ExtendedFunctionVariant>|null */
    private ?array $variants = null;
    /**
     * @param Function_|ClassMethod|Node\PropertyHook $functionLike
     * @param Type[] $realParameterTypes
     * @param Type[] $phpDocParameterTypes
     * @param Type[] $realParameterDefaultValues
     * @param array<string, list<AttributeReflection>> $parameterAttributes
     * @param Type[] $parameterOutTypes
     * @param array<string, bool> $immediatelyInvokedCallableParameters
     * @param array<string, Type> $phpDocClosureThisTypeParameters
     * @param list<AttributeReflection> $attributes
     */
    public function __construct(FunctionLike $functionLike, string $fileName, TemplateTypeMap $templateTypeMap, array $realParameterTypes, array $phpDocParameterTypes, array $realParameterDefaultValues, array $parameterAttributes, Type $realReturnType, ?Type $phpDocReturnType, ?Type $throwType, ?string $deprecatedDescription, bool $isDeprecated, bool $isInternal, ?bool $isPure, bool $acceptsNamedArguments, Assertions $assertions, ?string $phpDocComment, array $parameterOutTypes, array $immediatelyInvokedCallableParameters, array $phpDocClosureThisTypeParameters, array $attributes)
    {
        $this->fileName = $fileName;
        $this->templateTypeMap = $templateTypeMap;
        $this->realParameterTypes = $realParameterTypes;
        $this->phpDocParameterTypes = $phpDocParameterTypes;
        $this->realParameterDefaultValues = $realParameterDefaultValues;
        $this->parameterAttributes = $parameterAttributes;
        $this->realReturnType = $realReturnType;
        $this->phpDocReturnType = $phpDocReturnType;
        $this->throwType = $throwType;
        $this->deprecatedDescription = $deprecatedDescription;
        $this->isDeprecated = $isDeprecated;
        $this->isInternal = $isInternal;
        $this->isPure = $isPure;
        $this->acceptsNamedArguments = $acceptsNamedArguments;
        $this->assertions = $assertions;
        $this->phpDocComment = $phpDocComment;
        $this->parameterOutTypes = $parameterOutTypes;
        $this->immediatelyInvokedCallableParameters = $immediatelyInvokedCallableParameters;
        $this->phpDocClosureThisTypeParameters = $phpDocClosureThisTypeParameters;
        $this->attributes = $attributes;
        $this->functionLike = $functionLike;
    }
    protected function getFunctionLike() : FunctionLike
    {
        return $this->functionLike;
    }
    public function getFileName() : string
    {
        return $this->fileName;
    }
    public function getName() : string
    {
        if ($this->functionLike instanceof ClassMethod) {
            return $this->functionLike->name->name;
        }
        if (!$this->functionLike instanceof Function_) {
            // PropertyHook is handled in PhpMethodFromParserNodeReflection subclass
            throw new ShouldNotHappenException();
        }
        if ($this->functionLike->namespacedName === null) {
            throw new ShouldNotHappenException();
        }
        return (string) $this->functionLike->namespacedName;
    }
    public function getVariants() : array
    {
        if ($this->variants === null) {
            $this->variants = [new ExtendedFunctionVariant($this->getTemplateTypeMap(), $this->getResolvedTemplateTypeMap(), $this->getParameters(), $this->isVariadic(), $this->getReturnType(), $this->getPhpDocReturnType(), $this->getNativeReturnType())];
        }
        return $this->variants;
    }
    public function getOnlyVariant() : ExtendedParametersAcceptor
    {
        return $this;
    }
    public function getNamedArgumentsVariants() : ?array
    {
        return null;
    }
    public function getTemplateTypeMap() : TemplateTypeMap
    {
        return $this->templateTypeMap;
    }
    public function getResolvedTemplateTypeMap() : TemplateTypeMap
    {
        return TemplateTypeMap::createEmpty();
    }
    /**
     * @return list<ExtendedParameterReflection>
     */
    public function getParameters() : array
    {
        $parameters = [];
        $isOptional = \true;
        /** @var Node\Param $parameter */
        foreach (array_reverse($this->functionLike->getParams()) as $parameter) {
            if ($parameter->default === null && !$parameter->variadic) {
                $isOptional = \false;
            }
            if (!$parameter->var instanceof Variable || !is_string($parameter->var->name)) {
                throw new ShouldNotHappenException();
            }
            if (isset($this->immediatelyInvokedCallableParameters[$parameter->var->name])) {
                $immediatelyInvokedCallable = TrinaryLogic::createFromBoolean($this->immediatelyInvokedCallableParameters[$parameter->var->name]);
            } else {
                $immediatelyInvokedCallable = TrinaryLogic::createMaybe();
            }
            if (isset($this->phpDocClosureThisTypeParameters[$parameter->var->name])) {
                $closureThisType = $this->phpDocClosureThisTypeParameters[$parameter->var->name];
            } else {
                $closureThisType = null;
            }
            $parameters[] = new \PHPStan\Reflection\Php\PhpParameterFromParserNodeReflection($parameter->var->name, $isOptional, $this->realParameterTypes[$parameter->var->name], $this->phpDocParameterTypes[$parameter->var->name] ?? null, $parameter->byRef ? PassedByReference::createCreatesNewVariable() : PassedByReference::createNo(), $this->realParameterDefaultValues[$parameter->var->name] ?? null, $parameter->variadic, $this->parameterOutTypes[$parameter->var->name] ?? null, $immediatelyInvokedCallable, $closureThisType, $this->parameterAttributes[$parameter->var->name] ?? []);
        }
        return array_reverse($parameters);
    }
    public function isVariadic() : bool
    {
        foreach ($this->functionLike->getParams() as $parameter) {
            if ($parameter->variadic) {
                return \true;
            }
        }
        return \false;
    }
    public function getReturnType() : Type
    {
        return TypehintHelper::decideType($this->realReturnType, $this->phpDocReturnType);
    }
    public function getPhpDocReturnType() : Type
    {
        return $this->phpDocReturnType ?? new MixedType();
    }
    public function getNativeReturnType() : Type
    {
        return $this->realReturnType;
    }
    public function getCallSiteVarianceMap() : TemplateTypeVarianceMap
    {
        return TemplateTypeVarianceMap::createEmpty();
    }
    public function getDeprecatedDescription() : ?string
    {
        if ($this->isDeprecated) {
            return $this->deprecatedDescription;
        }
        return null;
    }
    public function isDeprecated() : TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->isDeprecated);
    }
    public function isInternal() : TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->isInternal);
    }
    public function getThrowType() : ?Type
    {
        return $this->throwType;
    }
    public function hasSideEffects() : TrinaryLogic
    {
        if ($this->getReturnType()->isVoid()->yes()) {
            return TrinaryLogic::createYes();
        }
        if ($this->isPure !== null) {
            return TrinaryLogic::createFromBoolean(!$this->isPure);
        }
        return TrinaryLogic::createMaybe();
    }
    public function isBuiltin() : bool
    {
        return \false;
    }
    public function isGenerator() : bool
    {
        return $this->nodeIsOrContainsYield($this->functionLike);
    }
    public function acceptsNamedArguments() : TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->acceptsNamedArguments);
    }
    private function nodeIsOrContainsYield(Node $node) : bool
    {
        if ($node instanceof Node\Expr\Yield_) {
            return \true;
        }
        if ($node instanceof Node\Expr\YieldFrom) {
            return \true;
        }
        foreach ($node->getSubNodeNames() as $nodeName) {
            $nodeProperty = $node->{$nodeName};
            if ($nodeProperty instanceof Node && $this->nodeIsOrContainsYield($nodeProperty)) {
                return \true;
            }
            if (!is_array($nodeProperty)) {
                continue;
            }
            foreach ($nodeProperty as $nodePropertyArrayItem) {
                if ($nodePropertyArrayItem instanceof Node && $this->nodeIsOrContainsYield($nodePropertyArrayItem)) {
                    return \true;
                }
            }
        }
        return \false;
    }
    public function getAsserts() : Assertions
    {
        return $this->assertions;
    }
    public function getDocComment() : ?string
    {
        return $this->phpDocComment;
    }
    public function returnsByReference() : TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->functionLike->returnsByRef());
    }
    public function isPure() : TrinaryLogic
    {
        if ($this->isPure === null) {
            return TrinaryLogic::createMaybe();
        }
        return TrinaryLogic::createFromBoolean($this->isPure);
    }
    public function getAttributes() : array
    {
        return $this->attributes;
    }
}
