<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc\Tag;

use PHPStan\Type\Type;
/**
 * @api
 */
final class VarTag implements \PHPStan\PhpDoc\Tag\TypedTag
{
    private Type $type;
    private bool $isExplicit;
    public function __construct(Type $type, bool $isExplicit)
    {
        $this->type = $type;
        $this->isExplicit = $isExplicit;
    }
    public function getType() : Type
    {
        return $this->type;
    }
    public function withType(Type $type) : self
    {
        return new self($type, $this->isExplicit);
    }
    public function isExplicit() : bool
    {
        return $this->isExplicit;
    }
    public function toImplicit() : self
    {
        return new self($this->type, \false);
    }
}
