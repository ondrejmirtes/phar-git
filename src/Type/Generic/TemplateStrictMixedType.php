<?php

declare (strict_types=1);
namespace PHPStan\Type\Generic;

use PHPStan\Type\AcceptsResult;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\MixedType;
use PHPStan\Type\StrictMixedType;
use PHPStan\Type\Type;
/** @api */
final class TemplateStrictMixedType extends StrictMixedType implements \PHPStan\Type\Generic\TemplateType
{
    /** @use TemplateTypeTrait<StrictMixedType> */
    use \PHPStan\Type\Generic\TemplateTypeTrait;
    /**
     * @param non-empty-string $name
     */
    public function __construct(\PHPStan\Type\Generic\TemplateTypeScope $scope, \PHPStan\Type\Generic\TemplateTypeStrategy $templateTypeStrategy, \PHPStan\Type\Generic\TemplateTypeVariance $templateTypeVariance, string $name, StrictMixedType $bound, ?Type $default)
    {
        $this->scope = $scope;
        $this->strategy = $templateTypeStrategy;
        $this->variance = $templateTypeVariance;
        $this->name = $name;
        $this->bound = $bound;
        $this->default = $default;
    }
    public function isSuperTypeOfMixed(MixedType $type): IsSuperTypeOfResult
    {
        return $this->isSuperTypeOf($type);
    }
    public function isAcceptedBy(Type $acceptingType, bool $strictTypes): AcceptsResult
    {
        return $this->isSubTypeOf($acceptingType)->toAcceptsResult();
    }
}
