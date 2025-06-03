<?php

declare (strict_types=1);
namespace PHPStan\Reflection\SignatureMap;

use PHPStan\Type\Type;
final class FunctionSignature
{
    /**
     * @var list<ParameterSignature>
     */
    private array $parameters;
    private Type $returnType;
    private Type $nativeReturnType;
    private bool $variadic;
    /**
     * @param list<ParameterSignature> $parameters
     */
    public function __construct(array $parameters, Type $returnType, Type $nativeReturnType, bool $variadic)
    {
        $this->parameters = $parameters;
        $this->returnType = $returnType;
        $this->nativeReturnType = $nativeReturnType;
        $this->variadic = $variadic;
    }
    /**
     * @return list<ParameterSignature>
     */
    public function getParameters() : array
    {
        return $this->parameters;
    }
    public function getReturnType() : Type
    {
        return $this->returnType;
    }
    public function getNativeReturnType() : Type
    {
        return $this->nativeReturnType;
    }
    public function isVariadic() : bool
    {
        return $this->variadic;
    }
}
