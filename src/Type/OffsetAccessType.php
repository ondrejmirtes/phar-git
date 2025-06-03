<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\PhpDocParser\Ast\Type\OffsetAccessTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\PhpDocParser\Printer\Printer;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Traits\LateResolvableTypeTrait;
use PHPStan\Type\Traits\NonGeneralizableTypeTrait;
use function array_merge;
/** @api */
final class OffsetAccessType implements \PHPStan\Type\CompoundType, \PHPStan\Type\LateResolvableType
{
    private \PHPStan\Type\Type $type;
    private \PHPStan\Type\Type $offset;
    use LateResolvableTypeTrait;
    use NonGeneralizableTypeTrait;
    public function __construct(\PHPStan\Type\Type $type, \PHPStan\Type\Type $offset)
    {
        $this->type = $type;
        $this->offset = $offset;
    }
    public function getReferencedClasses() : array
    {
        return array_merge($this->type->getReferencedClasses(), $this->offset->getReferencedClasses());
    }
    public function getObjectClassNames() : array
    {
        return [];
    }
    public function getObjectClassReflections() : array
    {
        return [];
    }
    public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance) : array
    {
        return array_merge($this->type->getReferencedTemplateTypes($positionVariance), $this->offset->getReferencedTemplateTypes($positionVariance));
    }
    public function equals(\PHPStan\Type\Type $type) : bool
    {
        return $type instanceof self && $this->type->equals($type->type) && $this->offset->equals($type->offset);
    }
    public function describe(\PHPStan\Type\VerbosityLevel $level) : string
    {
        $printer = new Printer();
        return $printer->print($this->toPhpDocNode());
    }
    public function isResolvable() : bool
    {
        return !\PHPStan\Type\TypeUtils::containsTemplateType($this->type) && !\PHPStan\Type\TypeUtils::containsTemplateType($this->offset);
    }
    protected function getResult() : \PHPStan\Type\Type
    {
        return $this->type->getOffsetValueType($this->offset);
    }
    /**
     * @param callable(Type): Type $cb
     */
    public function traverse(callable $cb) : \PHPStan\Type\Type
    {
        $type = $cb($this->type);
        $offset = $cb($this->offset);
        if ($this->type === $type && $this->offset === $offset) {
            return $this;
        }
        return new self($type, $offset);
    }
    public function traverseSimultaneously(\PHPStan\Type\Type $right, callable $cb) : \PHPStan\Type\Type
    {
        if (!$right instanceof self) {
            return $this;
        }
        $type = $cb($this->type, $right->type);
        $offset = $cb($this->offset, $right->offset);
        if ($this->type === $type && $this->offset === $offset) {
            return $this;
        }
        return new self($type, $offset);
    }
    public function toPhpDocNode() : TypeNode
    {
        return new OffsetAccessTypeNode($this->type->toPhpDocNode(), $this->offset->toPhpDocNode());
    }
}
