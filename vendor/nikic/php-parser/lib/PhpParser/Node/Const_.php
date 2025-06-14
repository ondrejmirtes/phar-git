<?php

declare (strict_types=1);
namespace PhpParser\Node;

use PhpParser\NodeAbstract;
class Const_ extends NodeAbstract
{
    /** @var Identifier Name */
    public \PhpParser\Node\Identifier $name;
    /** @var Expr Value */
    public \PhpParser\Node\Expr $value;
    /** @var Name|null Namespaced name (if using NameResolver) */
    public ?\PhpParser\Node\Name $namespacedName;
    /**
     * Constructs a const node for use in class const and const statements.
     *
     * @param string|Identifier $name Name
     * @param Expr $value Value
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct($name, \PhpParser\Node\Expr $value, array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->name = \is_string($name) ? new \PhpParser\Node\Identifier($name) : $name;
        $this->value = $value;
    }
    public function getSubNodeNames(): array
    {
        return ['name', 'value'];
    }
    public function getType(): string
    {
        return 'Const';
    }
}
