<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc\Tag;

use PHPStan\Type\Type;
/**
 * @api
 */
final class MixinTag
{
    private Type $type;
    public function __construct(Type $type)
    {
        $this->type = $type;
    }
    public function getType(): Type
    {
        return $this->type;
    }
}
