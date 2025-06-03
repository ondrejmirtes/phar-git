<?php

declare (strict_types=1);
namespace PHPStan\Type\Generic;

use PHPStan\Type\IterableType;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
use PHPStan\Type\Type;
/** @api */
final class TemplateIterableType extends IterableType implements \PHPStan\Type\Generic\TemplateType
{
    /** @use TemplateTypeTrait<IterableType> */
    use \PHPStan\Type\Generic\TemplateTypeTrait;
    use UndecidedComparisonCompoundTypeTrait;
    /**
     * @param non-empty-string $name
     */
    public function __construct(\PHPStan\Type\Generic\TemplateTypeScope $scope, \PHPStan\Type\Generic\TemplateTypeStrategy $templateTypeStrategy, \PHPStan\Type\Generic\TemplateTypeVariance $templateTypeVariance, string $name, IterableType $bound, ?Type $default)
    {
        parent::__construct($bound->getKeyType(), $bound->getItemType());
        $this->scope = $scope;
        $this->strategy = $templateTypeStrategy;
        $this->variance = $templateTypeVariance;
        $this->name = $name;
        $this->bound = $bound;
        $this->default = $default;
    }
}
