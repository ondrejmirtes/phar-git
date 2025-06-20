<?php

declare (strict_types=1);
namespace PHPStan\Type\Generic;

use PHPStan\Type\KeyOfType;
use PHPStan\Type\Traits\UndecidedComparisonCompoundTypeTrait;
use PHPStan\Type\Type;
/** @api */
final class TemplateKeyOfType extends KeyOfType implements \PHPStan\Type\Generic\TemplateType
{
    /** @use TemplateTypeTrait<KeyOfType> */
    use \PHPStan\Type\Generic\TemplateTypeTrait;
    use UndecidedComparisonCompoundTypeTrait;
    /**
     * @param non-empty-string $name
     */
    public function __construct(\PHPStan\Type\Generic\TemplateTypeScope $scope, \PHPStan\Type\Generic\TemplateTypeStrategy $templateTypeStrategy, \PHPStan\Type\Generic\TemplateTypeVariance $templateTypeVariance, string $name, KeyOfType $bound, ?Type $default)
    {
        parent::__construct($bound->getType());
        $this->scope = $scope;
        $this->strategy = $templateTypeStrategy;
        $this->variance = $templateTypeVariance;
        $this->name = $name;
        $this->bound = $bound;
        $this->default = $default;
    }
    protected function getResult(): Type
    {
        $result = $this->getBound()->getResult();
        return \PHPStan\Type\Generic\TemplateTypeFactory::create($this->getScope(), $this->getName(), $result, $this->getVariance(), $this->getStrategy(), $this->getDefault());
    }
    protected function shouldGeneralizeInferredType(): bool
    {
        return \false;
    }
}
