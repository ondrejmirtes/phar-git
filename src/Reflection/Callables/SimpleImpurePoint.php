<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Callables;

use PHPStan\Analyser\ImpurePoint;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptor;
use function sprintf;
/**
 * @phpstan-import-type ImpurePointIdentifier from ImpurePoint
 */
final class SimpleImpurePoint
{
    /**
     * @var ImpurePointIdentifier
     */
    private string $identifier;
    private string $description;
    private bool $certain;
    /**
     * @param ImpurePointIdentifier $identifier
     */
    public function __construct(string $identifier, string $description, bool $certain)
    {
        $this->identifier = $identifier;
        $this->description = $description;
        $this->certain = $certain;
    }
    /**
     * @param \PHPStan\Reflection\FunctionReflection|\PHPStan\Reflection\ExtendedMethodReflection $function
     */
    public static function createFromVariant($function, ?ParametersAcceptor $variant) : ?self
    {
        if (!$function->hasSideEffects()->no()) {
            $certain = $function->isPure()->no();
            if ($variant !== null) {
                $certain = $certain || $variant->getReturnType()->isVoid()->yes();
            }
            if ($function instanceof FunctionReflection) {
                return new \PHPStan\Reflection\Callables\SimpleImpurePoint('functionCall', sprintf('call to function %s()', $function->getName()), $certain);
            }
            return new \PHPStan\Reflection\Callables\SimpleImpurePoint('methodCall', sprintf('call to method %s::%s()', $function->getDeclaringClass()->getDisplayName(), $function->getName()), $certain);
        }
        return null;
    }
    /**
     * @return ImpurePointIdentifier
     */
    public function getIdentifier() : string
    {
        return $this->identifier;
    }
    public function getDescription() : string
    {
        return $this->description;
    }
    public function isCertain() : bool
    {
        return $this->certain;
    }
}
