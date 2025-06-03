<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PHPStan\ShouldNotHappenException;
use PHPStan\Type\VerbosityLevel;
use function count;
use function implode;
use function sprintf;
final class ConditionalExpressionHolder
{
    /**
     * @var array<string, ExpressionTypeHolder>
     */
    private array $conditionExpressionTypeHolders;
    private \PHPStan\Analyser\ExpressionTypeHolder $typeHolder;
    /**
     * @param array<string, ExpressionTypeHolder> $conditionExpressionTypeHolders
     */
    public function __construct(array $conditionExpressionTypeHolders, \PHPStan\Analyser\ExpressionTypeHolder $typeHolder)
    {
        $this->conditionExpressionTypeHolders = $conditionExpressionTypeHolders;
        $this->typeHolder = $typeHolder;
        if (count($conditionExpressionTypeHolders) === 0) {
            throw new ShouldNotHappenException();
        }
    }
    /**
     * @return array<string, ExpressionTypeHolder>
     */
    public function getConditionExpressionTypeHolders(): array
    {
        return $this->conditionExpressionTypeHolders;
    }
    public function getTypeHolder(): \PHPStan\Analyser\ExpressionTypeHolder
    {
        return $this->typeHolder;
    }
    public function getKey(): string
    {
        $parts = [];
        foreach ($this->conditionExpressionTypeHolders as $exprString => $typeHolder) {
            $parts[] = $exprString . '=' . $typeHolder->getType()->describe(VerbosityLevel::precise());
        }
        return sprintf('%s => %s (%s)', implode(' && ', $parts), $this->typeHolder->getType()->describe(VerbosityLevel::precise()), $this->typeHolder->getCertainty()->describe());
    }
}
