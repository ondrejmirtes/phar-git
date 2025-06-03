<?php

declare (strict_types=1);
namespace PHPStan\Dependency\ExportedNode;

use JsonSerializable;
use PHPStan\Dependency\ExportedNode;
use PHPStan\ShouldNotHappenException;
use ReturnTypeWillChange;
use function array_map;
use function count;
final class ExportedClassConstantNode implements ExportedNode, JsonSerializable
{
    private string $name;
    private string $value;
    /**
     * @var ExportedAttributeNode[]
     */
    private array $attributes;
    /**
     * @param ExportedAttributeNode[] $attributes
     */
    public function __construct(string $name, string $value, array $attributes)
    {
        $this->name = $name;
        $this->value = $value;
        $this->attributes = $attributes;
    }
    public function equals(ExportedNode $node) : bool
    {
        if (!$node instanceof self) {
            return \false;
        }
        if (count($this->attributes) !== count($node->attributes)) {
            return \false;
        }
        foreach ($this->attributes as $i => $attribute) {
            if (!$attribute->equals($node->attributes[$i])) {
                return \false;
            }
        }
        return $this->name === $node->name && $this->value === $node->value;
    }
    /**
     * @param mixed[] $properties
     */
    public static function __set_state(array $properties) : self
    {
        return new self($properties['name'], $properties['value'], $properties['attributes']);
    }
    /**
     * @param mixed[] $data
     */
    public static function decode(array $data) : self
    {
        return new self($data['name'], $data['value'], array_map(static function (array $attributeData) : \PHPStan\Dependency\ExportedNode\ExportedAttributeNode {
            if ($attributeData['type'] !== \PHPStan\Dependency\ExportedNode\ExportedAttributeNode::class) {
                throw new ShouldNotHappenException();
            }
            return \PHPStan\Dependency\ExportedNode\ExportedAttributeNode::decode($attributeData['data']);
        }, $data['attributes']));
    }
    /**
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return ['type' => self::class, 'data' => ['name' => $this->name, 'value' => $this->value, 'attributes' => $this->attributes]];
    }
}
