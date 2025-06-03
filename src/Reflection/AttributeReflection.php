<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PHPStan\Type\Type;
/**
 * @api
 */
final class AttributeReflection
{
    private string $name;
    /**
     * @var array<string, Type>
     */
    private array $argumentTypes;
    /**
     * @param array<string, Type> $argumentTypes
     */
    public function __construct(string $name, array $argumentTypes)
    {
        $this->name = $name;
        $this->argumentTypes = $argumentTypes;
    }
    public function getName() : string
    {
        return $this->name;
    }
    /**
     * @return array<string, Type>
     */
    public function getArgumentTypes() : array
    {
        return $this->argumentTypes;
    }
}
