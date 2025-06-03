<?php

declare (strict_types=1);
namespace PHPStan\Reflection\Native;

use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\AttributeReflection;
use PHPStan\Reflection\ExtendedParametersAcceptor;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use function count;
final class NativeFunctionReflection implements FunctionReflection
{
    private string $name;
    /**
     * @var list<ExtendedParametersAcceptor>
     */
    private array $variants;
    /**
     * @var list<ExtendedParametersAcceptor>|null
     */
    private ?array $namedArgumentsVariants;
    private ?Type $throwType;
    private TrinaryLogic $hasSideEffects;
    private bool $isDeprecated;
    private ?string $phpDocComment;
    private bool $acceptsNamedArguments;
    /**
     * @var list<AttributeReflection>
     */
    private array $attributes;
    private Assertions $assertions;
    private TrinaryLogic $returnsByReference;
    /**
     * @param list<ExtendedParametersAcceptor> $variants
     * @param list<ExtendedParametersAcceptor>|null $namedArgumentsVariants
     * @param list<AttributeReflection> $attributes
     */
    public function __construct(string $name, array $variants, ?array $namedArgumentsVariants, ?Type $throwType, TrinaryLogic $hasSideEffects, bool $isDeprecated, ?Assertions $assertions, ?string $phpDocComment, ?TrinaryLogic $returnsByReference, bool $acceptsNamedArguments, array $attributes)
    {
        $this->name = $name;
        $this->variants = $variants;
        $this->namedArgumentsVariants = $namedArgumentsVariants;
        $this->throwType = $throwType;
        $this->hasSideEffects = $hasSideEffects;
        $this->isDeprecated = $isDeprecated;
        $this->phpDocComment = $phpDocComment;
        $this->acceptsNamedArguments = $acceptsNamedArguments;
        $this->attributes = $attributes;
        $this->assertions = $assertions ?? Assertions::createEmpty();
        $this->returnsByReference = $returnsByReference ?? TrinaryLogic::createMaybe();
    }
    public function getName(): string
    {
        return $this->name;
    }
    public function getFileName(): ?string
    {
        return null;
    }
    public function getVariants(): array
    {
        return $this->variants;
    }
    public function getOnlyVariant(): ExtendedParametersAcceptor
    {
        $variants = $this->getVariants();
        if (count($variants) !== 1) {
            throw new ShouldNotHappenException();
        }
        return $variants[0];
    }
    public function getNamedArgumentsVariants(): ?array
    {
        return $this->namedArgumentsVariants;
    }
    public function getThrowType(): ?Type
    {
        return $this->throwType;
    }
    public function getDeprecatedDescription(): ?string
    {
        return null;
    }
    public function isDeprecated(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->isDeprecated);
    }
    public function isInternal(): TrinaryLogic
    {
        return TrinaryLogic::createNo();
    }
    public function hasSideEffects(): TrinaryLogic
    {
        if ($this->isVoid()) {
            return TrinaryLogic::createYes();
        }
        return $this->hasSideEffects;
    }
    public function isPure(): TrinaryLogic
    {
        if ($this->hasSideEffects()->yes()) {
            return TrinaryLogic::createNo();
        }
        return $this->hasSideEffects->negate();
    }
    private function isVoid(): bool
    {
        foreach ($this->variants as $variant) {
            if (!$variant->getReturnType()->isVoid()->yes()) {
                return \false;
            }
        }
        return \true;
    }
    public function isBuiltin(): bool
    {
        return \true;
    }
    public function getAsserts(): Assertions
    {
        return $this->assertions;
    }
    public function getDocComment(): ?string
    {
        return $this->phpDocComment;
    }
    public function returnsByReference(): TrinaryLogic
    {
        return $this->returnsByReference;
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
