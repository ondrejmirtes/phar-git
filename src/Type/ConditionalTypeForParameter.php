<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\PhpDocParser\Ast\Type\ConditionalTypeForParameterNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\LateResolvableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use function array_merge;
use function sprintf;
/** @api */
final class ConditionalTypeForParameter implements \PHPStan\Type\CompoundType, \PHPStan\Type\LateResolvableType
{
    private string $parameterName;
    private \PHPStan\Type\Type $target;
    private \PHPStan\Type\Type $if;
    private \PHPStan\Type\Type $else;
    private bool $negated;
    use LateResolvableTypeTrait;
    use NonGeneralizableTypeTrait;
    public function __construct(string $parameterName, \PHPStan\Type\Type $target, \PHPStan\Type\Type $if, \PHPStan\Type\Type $else, bool $negated)
    {
        $this->parameterName = $parameterName;
        $this->target = $target;
        $this->if = $if;
        $this->else = $else;
        $this->negated = $negated;
    }
    public function getParameterName() : string
    {
        return $this->parameterName;
    }
    public function getTarget() : \PHPStan\Type\Type
    {
        return $this->target;
    }
    public function getIf() : \PHPStan\Type\Type
    {
        return $this->if;
    }
    public function getElse() : \PHPStan\Type\Type
    {
        return $this->else;
    }
    public function isNegated() : bool
    {
        return $this->negated;
    }
    public function changeParameterName(string $parameterName) : self
    {
        return new self($parameterName, $this->target, $this->if, $this->else, $this->negated);
    }
    public function toConditional(\PHPStan\Type\Type $subject) : \PHPStan\Type\Type
    {
        return new \PHPStan\Type\ConditionalType($subject, $this->target, $this->if, $this->else, $this->negated);
    }
    public function isSuperTypeOf(\PHPStan\Type\Type $type) : \PHPStan\Type\IsSuperTypeOfResult
    {
        if ($type instanceof self) {
            return $this->if->isSuperTypeOf($type->if)->and($this->else->isSuperTypeOf($type->else));
        }
        return $this->isSuperTypeOfDefault($type);
    }
    public function getReferencedClasses() : array
    {
        return array_merge($this->target->getReferencedClasses(), $this->if->getReferencedClasses(), $this->else->getReferencedClasses());
    }
    public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance) : array
    {
        return array_merge($this->target->getReferencedTemplateTypes($positionVariance), $this->if->getReferencedTemplateTypes($positionVariance), $this->else->getReferencedTemplateTypes($positionVariance));
    }
    public function equals(\PHPStan\Type\Type $type) : bool
    {
        return $type instanceof self && $this->parameterName === $type->parameterName && $this->target->equals($type->target) && $this->if->equals($type->if) && $this->else->equals($type->else);
    }
    public function describe(\PHPStan\Type\VerbosityLevel $level) : string
    {
        return sprintf('(%s %s %s ? %s : %s)', $this->parameterName, $this->negated ? 'is not' : 'is', $this->target->describe($level), $this->if->describe($level), $this->else->describe($level));
    }
    public function isResolvable() : bool
    {
        return \false;
    }
    protected function getResult() : \PHPStan\Type\Type
    {
        return \PHPStan\Type\TypeCombinator::union($this->if, $this->else);
    }
    public function traverse(callable $cb) : \PHPStan\Type\Type
    {
        $target = $cb($this->target);
        $if = $cb($this->if);
        $else = $cb($this->else);
        if ($this->target === $target && $this->if === $if && $this->else === $else) {
            return $this;
        }
        return new self($this->parameterName, $target, $if, $else, $this->negated);
    }
    public function traverseSimultaneously(\PHPStan\Type\Type $right, callable $cb) : \PHPStan\Type\Type
    {
        if (!$right instanceof self) {
            return $this;
        }
        $target = $cb($this->target, $right->target);
        $if = $cb($this->if, $right->if);
        $else = $cb($this->else, $right->else);
        if ($this->target === $target && $this->if === $if && $this->else === $else) {
            return $this;
        }
        return new self($this->parameterName, $target, $if, $else, $this->negated);
    }
    public function toPhpDocNode() : TypeNode
    {
        return new ConditionalTypeForParameterNode($this->parameterName, $this->target->toPhpDocNode(), $this->if->toPhpDocNode(), $this->else->toPhpDocNode(), $this->negated);
    }
}
