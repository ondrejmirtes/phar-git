<?php

declare (strict_types=1);
namespace PHPStan\Type\Generic;

use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
use PHPStan\Type\Type;
/** @api */
final class TemplateConstantIntegerType extends ConstantIntegerType implements \PHPStan\Type\Generic\TemplateType
{
    /** @use TemplateTypeTrait<ConstantIntegerType> */
    use \PHPStan\Type\Generic\TemplateTypeTrait;
    use UndecidedComparisonCompoundTypeTrait;
    /**
     * @param non-empty-string $name
     */
    public function __construct(\PHPStan\Type\Generic\TemplateTypeScope $scope, \PHPStan\Type\Generic\TemplateTypeStrategy $templateTypeStrategy, \PHPStan\Type\Generic\TemplateTypeVariance $templateTypeVariance, string $name, ConstantIntegerType $bound, ?Type $default)
    {
        parent::__construct($bound->getValue());
        $this->scope = $scope;
        $this->strategy = $templateTypeStrategy;
        $this->variance = $templateTypeVariance;
        $this->name = $name;
        $this->bound = $bound;
        $this->default = $default;
    }
    protected function shouldGeneralizeInferredType(): bool
    {
        return \false;
    }
}
