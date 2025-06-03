<?php

declare (strict_types=1);
namespace PHPStan\Type\Generic;

use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
/** @api */
final class TemplateUnionType extends UnionType implements \PHPStan\Type\Generic\TemplateType
{
    /** @use TemplateTypeTrait<UnionType> */
    use \PHPStan\Type\Generic\TemplateTypeTrait;
    /**
     * @param non-empty-string $name
     */
    public function __construct(\PHPStan\Type\Generic\TemplateTypeScope $scope, \PHPStan\Type\Generic\TemplateTypeStrategy $templateTypeStrategy, \PHPStan\Type\Generic\TemplateTypeVariance $templateTypeVariance, string $name, UnionType $bound, ?Type $default)
    {
        parent::__construct($bound->getTypes());
        $this->scope = $scope;
        $this->strategy = $templateTypeStrategy;
        $this->variance = $templateTypeVariance;
        $this->name = $name;
        $this->bound = $bound;
        $this->default = $default;
    }
    public function filterTypes(callable $filterCb) : Type
    {
        $result = parent::filterTypes($filterCb);
        if (!$result instanceof \PHPStan\Type\Generic\TemplateType) {
            return \PHPStan\Type\Generic\TemplateTypeFactory::create($this->getScope(), $this->getName(), $result, $this->getVariance(), $this->getStrategy(), $this->getDefault());
        }
        return $result;
    }
}
