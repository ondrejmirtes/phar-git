<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\Type;
/**
 * @api
 */
class ExtendedFunctionVariant extends \PHPStan\Reflection\FunctionVariant implements \PHPStan\Reflection\ExtendedParametersAcceptor
{
    private Type $phpDocReturnType;
    private Type $nativeReturnType;
    /**
     * @param list<ExtendedParameterReflection> $parameters
     * @api
     */
    public function __construct(TemplateTypeMap $templateTypeMap, ?TemplateTypeMap $resolvedTemplateTypeMap, array $parameters, bool $isVariadic, Type $returnType, Type $phpDocReturnType, Type $nativeReturnType, ?TemplateTypeVarianceMap $callSiteVarianceMap = null)
    {
        $this->phpDocReturnType = $phpDocReturnType;
        $this->nativeReturnType = $nativeReturnType;
        parent::__construct($templateTypeMap, $resolvedTemplateTypeMap, $parameters, $isVariadic, $returnType, $callSiteVarianceMap);
    }
    /**
     * @return list<ExtendedParameterReflection>
     */
    public function getParameters() : array
    {
        /** @var list<ExtendedParameterReflection> $parameters */
        $parameters = parent::getParameters();
        return $parameters;
    }
    public function getPhpDocReturnType() : Type
    {
        return $this->phpDocReturnType;
    }
    public function getNativeReturnType() : Type
    {
        return $this->nativeReturnType;
    }
}
