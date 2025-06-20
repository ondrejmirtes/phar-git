<?php

declare (strict_types=1);
namespace PHPStan\Dependency\ExportedNode;

use JsonSerializable;
use PHPStan\Dependency\ExportedNode;
use PHPStan\ShouldNotHappenException;
use ReturnTypeWillChange;
use function array_map;
use function count;
final class ExportedParameterNode implements ExportedNode, JsonSerializable
{
    private string $name;
    private ?string $type;
    private bool $byRef;
    private bool $variadic;
    private bool $hasDefault;
    /**
     * @var ExportedAttributeNode[]
     */
    private array $attributes;
    /**
     * @param ExportedAttributeNode[] $attributes
     */
    public function __construct(string $name, ?string $type, bool $byRef, bool $variadic, bool $hasDefault, array $attributes)
    {
        $this->name = $name;
        $this->type = $type;
        $this->byRef = $byRef;
        $this->variadic = $variadic;
        $this->hasDefault = $hasDefault;
        $this->attributes = $attributes;
    }
    public function equals(ExportedNode $node): bool
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
        return $this->name === $node->name && $this->type === $node->type && $this->byRef === $node->byRef && $this->variadic === $node->variadic && $this->hasDefault === $node->hasDefault;
    }
    /**
     * @param mixed[] $properties
     */
    public static function __set_state(array $properties): self
    {
        return new self($properties['name'], $properties['type'], $properties['byRef'], $properties['variadic'], $properties['hasDefault'], $properties['attributes']);
    }
    /**
     * @return mixed
     */
    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return ['type' => self::class, 'data' => ['name' => $this->name, 'type' => $this->type, 'byRef' => $this->byRef, 'variadic' => $this->variadic, 'hasDefault' => $this->hasDefault, 'attributes' => $this->attributes]];
    }
    /**
     * @param mixed[] $data
     */
    public static function decode(array $data): self
    {
        return new self($data['name'], $data['type'], $data['byRef'], $data['variadic'], $data['hasDefault'], array_map(static function (array $attributeData): \PHPStan\Dependency\ExportedNode\ExportedAttributeNode {
            if ($attributeData['type'] !== \PHPStan\Dependency\ExportedNode\ExportedAttributeNode::class) {
                throw new ShouldNotHappenException();
            }
            return \PHPStan\Dependency\ExportedNode\ExportedAttributeNode::decode($attributeData['data']);
        }, $data['attributes']));
    }
}
