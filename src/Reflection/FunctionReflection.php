<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
/** @api */
interface FunctionReflection
{
    public function getName(): string;
    public function getFileName(): ?string;
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
    public function isDeprecated(): TrinaryLogic;
    public function getDeprecatedDescription(): ?string;
    public function isInternal(): TrinaryLogic;
    public function getThrowType(): ?Type;
    public function hasSideEffects(): TrinaryLogic;
    public function isBuiltin(): bool;
    public function getAsserts(): \PHPStan\Reflection\Assertions;
    public function getDocComment(): ?string;
    public function returnsByReference(): TrinaryLogic;
    /**
     * This indicates whether the function has phpstan-pure
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
