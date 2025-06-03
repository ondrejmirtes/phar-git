<?php

declare (strict_types=1);
namespace PHPStan\Type\Generic;

use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
use PHPStan\Type\Type;
/** @api */
final class TemplateGenericObjectType extends \PHPStan\Type\Generic\GenericObjectType implements \PHPStan\Type\Generic\TemplateType
{
    use UndecidedComparisonCompoundTypeTrait;
    /** @use TemplateTypeTrait<GenericObjectType> */
    use \PHPStan\Type\Generic\TemplateTypeTrait;
    /**
     * @param non-empty-string $name
     */
    public function __construct(\PHPStan\Type\Generic\TemplateTypeScope $scope, \PHPStan\Type\Generic\TemplateTypeStrategy $templateTypeStrategy, \PHPStan\Type\Generic\TemplateTypeVariance $templateTypeVariance, string $name, \PHPStan\Type\Generic\GenericObjectType $bound, ?Type $default)
    {
        parent::__construct($bound->getClassName(), $bound->getTypes(), null, null, $bound->getVariances());
        $this->scope = $scope;
        $this->strategy = $templateTypeStrategy;
        $this->variance = $templateTypeVariance;
        $this->name = $name;
        $this->bound = $bound;
        $this->default = $default;
    }
    protected function recreate(string $className, array $types, ?Type $subtractedType, array $variances = []): \PHPStan\Type\Generic\GenericObjectType
    {
        return new self($this->scope, $this->strategy, $this->variance, $this->name, $this->getBound(), $this->default);
    }
}
