<?php

/**
 * This file is part of the Nette Framework (https://nette.org)
 * Copyright (c) 2004 David Grudl (https://davidgrudl.com)
 */
declare (strict_types=1);
namespace _PHPStan_checksum\Nette\Neon;

/**
 * Representation of NEON entity 'foo(bar=1)'
 */
final class Entity extends \stdClass
{
    /** @var mixed */
    public $value;
    /** @var mixed[] */
    public $attributes;
    public function __construct($value, array $attrs = [])
    {
        $this->value = $value;
        $this->attributes = $attrs;
    }
    /** @param  mixed[]  $properties */
    public static function __set_state(array $properties)
    {
        return new self($properties['value'], $properties['attributes']);
    }
}
