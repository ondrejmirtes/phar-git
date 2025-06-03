<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
/**
 * The purpose of this interface is to be able to
 * answer more questions about methods
 * without breaking backward compatibility
 * with existing MethodsClassReflectionExtension.
 *
 * Developers are meant to only implement MethodReflection
 * and its methods in their code.
 *
 * New methods on ExtendedMethodReflection will be added
 * in minor versions.
 *
 * @api
 */
interface ExtendedMethodReflection extends \PHPStan\Reflection\MethodReflection
{
    /**
     * @return list<ExtendedParametersAcceptor>
     */
    public function getVariants(): array;
    /**
     * @internal
     */
    public function getOnlyVariant(): \PHPStan\Reflection\ExtendedParametersAcceptor;
    /**
     * @return list<ExtendedParametersAcceptor>|null
     */
    public function getNamedArgumentsVariants(): ?array;
    public function acceptsNamedArguments(): TrinaryLogic;
    public function getAsserts(): \PHPStan\Reflection\Assertions;
    public function getSelfOutType(): ?Type;
    public function returnsByReference(): TrinaryLogic;
    public function isFinalByKeyword(): TrinaryLogic;
    /**
     * @return \PHPStan\TrinaryLogic|bool
     */
    public function isAbstract();
    /**
     * @return \PHPStan\TrinaryLogic|bool
     */
    public function isBuiltin();
    /**
     * This indicates whether the method has phpstan-pure
     * or phpstan-impure annotation above it.
     *
     * In most cases asking hasSideEffects() is much more practical
     * as it also accounts for void return type (method being always impure).
     */
    public function isPure(): TrinaryLogic;
    /**
     * @return list<AttributeReflection>
     */
    public function getAttributes(): array;
}
