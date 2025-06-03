<?php

declare (strict_types=1);
namespace PHPStan\Type\Generic;

use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
use PHPStan\Type\Type;
/** @api */
class TemplateObjectWithoutClassType extends ObjectWithoutClassType implements \PHPStan\Type\Generic\TemplateType
{
    use UndecidedComparisonCompoundTypeTrait;
    /** @use TemplateTypeTrait<ObjectWithoutClassType> */
    use \PHPStan\Type\Generic\TemplateTypeTrait;
    /**
     * @param non-empty-string $name
     */
    public function __construct(\PHPStan\Type\Generic\TemplateTypeScope $scope, \PHPStan\Type\Generic\TemplateTypeStrategy $templateTypeStrategy, \PHPStan\Type\Generic\TemplateTypeVariance $templateTypeVariance, string $name, ObjectWithoutClassType $bound, ?Type $default)
    {
        parent::__construct();
        $this->scope = $scope;
        $this->strategy = $templateTypeStrategy;
        $this->variance = $templateTypeVariance;
        $this->name = $name;
        $this->bound = $bound;
        $this->default = $default;
    }
}
