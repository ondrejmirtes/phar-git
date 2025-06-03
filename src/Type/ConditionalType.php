<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\PhpDocParser\Ast\Type\ConditionalTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\LateResolvableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use function array_merge;
use function sprintf;
/** @api */
final class ConditionalType implements \PHPStan\Type\CompoundType, \PHPStan\Type\LateResolvableType
{
    private \PHPStan\Type\Type $subject;
    private \PHPStan\Type\Type $target;
    private \PHPStan\Type\Type $if;
    private \PHPStan\Type\Type $else;
    private bool $negated;
    use LateResolvableTypeTrait;
    use NonGeneralizableTypeTrait;
    private ?\PHPStan\Type\Type $normalizedIf = null;
    private ?\PHPStan\Type\Type $normalizedElse = null;
    private ?\PHPStan\Type\Type $subjectWithTargetIntersectedType = null;
    private ?\PHPStan\Type\Type $subjectWithTargetRemovedType = null;
    public function __construct(\PHPStan\Type\Type $subject, \PHPStan\Type\Type $target, \PHPStan\Type\Type $if, \PHPStan\Type\Type $else, bool $negated)
    {
        $this->subject = $subject;
        $this->target = $target;
        $this->if = $if;
        $this->else = $else;
        $this->negated = $negated;
    }
    public function getSubject(): \PHPStan\Type\Type
    {
        return $this->subject;
    }
    public function getTarget(): \PHPStan\Type\Type
    {
        return $this->target;
    }
    public function getIf(): \PHPStan\Type\Type
    {
        return $this->if;
    }
    public function getElse(): \PHPStan\Type\Type
    {
        return $this->else;
    }
    public function isNegated(): bool
    {
        return $this->negated;
    }
    public function isSuperTypeOf(\PHPStan\Type\Type $type): \PHPStan\Type\IsSuperTypeOfResult
    {
        if ($type instanceof self) {
            return $this->if->isSuperTypeOf($type->if)->and($this->else->isSuperTypeOf($type->else));
        }
        return $this->isSuperTypeOfDefault($type);
    }
    public function getReferencedClasses(): array
    {
        return array_merge($this->subject->getReferencedClasses(), $this->target->getReferencedClasses(), $this->if->getReferencedClasses(), $this->else->getReferencedClasses());
    }
    public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
    {
        return array_merge($this->subject->getReferencedTemplateTypes($positionVariance), $this->target->getReferencedTemplateTypes($positionVariance), $this->if->getReferencedTemplateTypes($positionVariance), $this->else->getReferencedTemplateTypes($positionVariance));
    }
    public function equals(\PHPStan\Type\Type $type): bool
    {
        return $type instanceof self && $this->subject->equals($type->subject) && $this->target->equals($type->target) && $this->if->equals($type->if) && $this->else->equals($type->else);
    }
    public function describe(\PHPStan\Type\VerbosityLevel $level): string
    {
        return sprintf('(%s %s %s ? %s : %s)', $this->subject->describe($level), $this->negated ? 'is not' : 'is', $this->target->describe($level), $this->if->describe($level), $this->else->describe($level));
    }
    public function isResolvable(): bool
    {
        return !\PHPStan\Type\TypeUtils::containsTemplateType($this->subject) && !\PHPStan\Type\TypeUtils::containsTemplateType($this->target);
    }
    protected function getResult(): \PHPStan\Type\Type
    {
        $isSuperType = $this->target->isSuperTypeOf($this->subject);
        if ($isSuperType->yes()) {
            return !$this->negated ? $this->getNormalizedIf() : $this->getNormalizedElse();
        }
        if ($isSuperType->no()) {
            return !$this->negated ? $this->getNormalizedElse() : $this->getNormalizedIf();
        }
        return \PHPStan\Type\TypeCombinator::union($this->getNormalizedIf(), $this->getNormalizedElse());
    }
    public function traverse(callable $cb): \PHPStan\Type\Type
    {
        $subject = $cb($this->subject);
        $target = $cb($this->target);
        $if = $cb($this->getNormalizedIf());
        $else = $cb($this->getNormalizedElse());
        if ($this->subject === $subject && $this->target === $target && $this->getNormalizedIf() === $if && $this->getNormalizedElse() === $else) {
            return $this;
        }
        return new self($subject, $target, $if, $else, $this->negated);
    }
    public function traverseSimultaneously(\PHPStan\Type\Type $right, callable $cb): \PHPStan\Type\Type
    {
        if (!$right instanceof self) {
            return $this;
        }
        $subject = $cb($this->subject, $right->subject);
        $target = $cb($this->target, $right->target);
        $if = $cb($this->getNormalizedIf(), $right->getNormalizedIf());
        $else = $cb($this->getNormalizedElse(), $right->getNormalizedElse());
        if ($this->subject === $subject && $this->target === $target && $this->getNormalizedIf() === $if && $this->getNormalizedElse() === $else) {
            return $this;
        }
        return new self($subject, $target, $if, $else, $this->negated);
    }
    public function toPhpDocNode(): TypeNode
    {
        return new ConditionalTypeNode($this->subject->toPhpDocNode(), $this->target->toPhpDocNode(), $this->if->toPhpDocNode(), $this->else->toPhpDocNode(), $this->negated);
    }
    private function getNormalizedIf(): \PHPStan\Type\Type
    {
        return $this->normalizedIf ??= \PHPStan\Type\TypeTraverser::map($this->if, fn(\PHPStan\Type\Type $type, callable $traverse) => $type === $this->subject ? !$this->negated ? $this->getSubjectWithTargetIntersectedType() : $this->getSubjectWithTargetRemovedType() : $traverse($type));
    }
    private function getNormalizedElse(): \PHPStan\Type\Type
    {
        return $this->normalizedElse ??= \PHPStan\Type\TypeTraverser::map($this->else, fn(\PHPStan\Type\Type $type, callable $traverse) => $type === $this->subject ? !$this->negated ? $this->getSubjectWithTargetRemovedType() : $this->getSubjectWithTargetIntersectedType() : $traverse($type));
    }
    private function getSubjectWithTargetIntersectedType(): \PHPStan\Type\Type
    {
        return $this->subjectWithTargetIntersectedType ??= \PHPStan\Type\TypeCombinator::intersect($this->subject, $this->target);
    }
    private function getSubjectWithTargetRemovedType(): \PHPStan\Type\Type
    {
        return $this->subjectWithTargetRemovedType ??= \PHPStan\Type\TypeCombinator::remove($this->subject, $this->target);
    }
}
