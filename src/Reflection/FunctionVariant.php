<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\Type;
/**
 * @api
 */
class FunctionVariant implements \PHPStan\Reflection\ParametersAcceptor
{
    private TemplateTypeMap $templateTypeMap;
    private ?TemplateTypeMap $resolvedTemplateTypeMap;
    /**
     * @var list<ParameterReflection>
     */
    private array $parameters;
    private bool $isVariadic;
    private Type $returnType;
    private TemplateTypeVarianceMap $callSiteVarianceMap;
    /**
     * @api
     * @param list<ParameterReflection> $parameters
     */
    public function __construct(TemplateTypeMap $templateTypeMap, ?TemplateTypeMap $resolvedTemplateTypeMap, array $parameters, bool $isVariadic, Type $returnType, ?TemplateTypeVarianceMap $callSiteVarianceMap = null)
    {
        $this->templateTypeMap = $templateTypeMap;
        $this->resolvedTemplateTypeMap = $resolvedTemplateTypeMap;
        $this->parameters = $parameters;
        $this->isVariadic = $isVariadic;
        $this->returnType = $returnType;
        $this->callSiteVarianceMap = $callSiteVarianceMap ?? TemplateTypeVarianceMap::createEmpty();
    }
    public function getTemplateTypeMap(): TemplateTypeMap
    {
        return $this->templateTypeMap;
    }
    public function getResolvedTemplateTypeMap(): TemplateTypeMap
    {
        return $this->resolvedTemplateTypeMap ?? TemplateTypeMap::createEmpty();
    }
    public function getCallSiteVarianceMap(): TemplateTypeVarianceMap
    {
        return $this->callSiteVarianceMap;
    }
    /**
     * @return list<ParameterReflection>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
    public function isVariadic(): bool
    {
        return $this->isVariadic;
    }
    public function getReturnType(): Type
    {
        return $this->returnType;
    }
}
