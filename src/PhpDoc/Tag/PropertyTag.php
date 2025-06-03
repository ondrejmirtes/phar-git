<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc\Tag;

use PHPStan\Type\Type;
/**
 * @api
 */
final class PropertyTag
{
    private ?Type $readableType;
    private ?Type $writableType;
    public function __construct(?Type $readableType, ?Type $writableType)
    {
        $this->readableType = $readableType;
        $this->writableType = $writableType;
    }
    public function getReadableType() : ?Type
    {
        return $this->readableType;
    }
    public function getWritableType() : ?Type
    {
        return $this->writableType;
    }
    /**
     * @phpstan-assert-if-true !null $this->getReadableType()
     */
    public function isReadable() : bool
    {
        return $this->readableType !== null;
    }
    /**
     * @phpstan-assert-if-true !null $this->getWritableType()
     */
    public function isWritable() : bool
    {
        return $this->writableType !== null;
    }
}
