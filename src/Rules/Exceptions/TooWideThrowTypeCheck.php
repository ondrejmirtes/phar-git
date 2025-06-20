<?php

declare (strict_types=1);
namespace PHPStan\Rules\Exceptions;

use PHPStan\Analyser\ThrowPoint;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\VerbosityLevel;
use function array_map;
final class TooWideThrowTypeCheck
{
    private bool $implicitThrows;
    public function __construct(bool $implicitThrows)
    {
        $this->implicitThrows = $implicitThrows;
    }
    /**
     * @param ThrowPoint[] $throwPoints
     * @return string[]
     */
    public function check(Type $throwType, array $throwPoints): array
    {
        if ($throwType->isVoid()->yes()) {
            return [];
        }
        $throwPointType = TypeCombinator::union(...array_map(function (ThrowPoint $throwPoint): Type {
            if (!$this->implicitThrows && !$throwPoint->isExplicit()) {
                return new NeverType();
            }
            return $throwPoint->getType();
        }, $throwPoints));
        $throwClasses = [];
        foreach (TypeUtils::flattenTypes($throwType) as $type) {
            if (!$throwPointType instanceof NeverType && !$type->isSuperTypeOf($throwPointType)->no()) {
                continue;
            }
            $throwClasses[] = $type->describe(VerbosityLevel::typeOnly());
        }
        return $throwClasses;
    }
}
