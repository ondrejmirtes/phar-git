<?php

declare (strict_types=1);
namespace PHPStan\Dependency\ExportedNode;

use JsonSerializable;
use PHPStan\Dependency\ExportedNode;
use PHPStan\Dependency\RootExportedNode;
use PHPStan\ShouldNotHappenException;
use ReturnTypeWillChange;
use function array_map;
use function count;
final class ExportedEnumNode implements RootExportedNode, JsonSerializable
{
    private string $name;
    private ?string $scalarType;
    private ?\PHPStan\Dependency\ExportedNode\ExportedPhpDocNode $phpDoc;
    /**
     * @var string[]
     */
    private array $implements;
    /**
     * @var ExportedNode[]
     */
    private array $statements;
    /**
     * @var ExportedAttributeNode[]
     */
    private array $attributes;
    /**
     * @param string[] $implements
     * @param ExportedNode[] $statements
     * @param ExportedAttributeNode[] $attributes
     */
    public function __construct(string $name, ?string $scalarType, ?\PHPStan\Dependency\ExportedNode\ExportedPhpDocNode $phpDoc, array $implements, array $statements, array $attributes)
    {
        $this->name = $name;
        $this->scalarType = $scalarType;
        $this->phpDoc = $phpDoc;
        $this->implements = $implements;
        $this->statements = $statements;
        $this->attributes = $attributes;
    }
    public function equals(ExportedNode $node): bool
    {
        if (!$node instanceof self) {
            return \false;
        }
        if ($this->phpDoc === null) {
            if ($node->phpDoc !== null) {
                return \false;
            }
        } elseif ($node->phpDoc !== null) {
            if (!$this->phpDoc->equals($node->phpDoc)) {
                return \false;
            }
        } else {
            return \false;
        }
        if (count($this->statements) !== count($node->statements)) {
            return \false;
        }
        foreach ($this->statements as $i => $statement) {
            if ($statement->equals($node->statements[$i])) {
                continue;
            }
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
        return $this->name === $node->name && $this->scalarType === $node->scalarType && $this->implements === $node->implements;
    }
    /**
     * @param mixed[] $properties
     */
    public static function __set_state(array $properties): self
    {
        return new self($properties['name'], $properties['scalarType'], $properties['phpDoc'], $properties['implements'], $properties['statements'], $properties['attributes']);
    }
    /**
     * @return mixed
     */
    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return ['type' => self::class, 'data' => ['name' => $this->name, 'scalarType' => $this->scalarType, 'phpDoc' => $this->phpDoc, 'implements' => $this->implements, 'statements' => $this->statements, 'attributes' => $this->attributes]];
    }
    /**
     * @param mixed[] $data
     */
    public static function decode(array $data): self
    {
        return new self($data['name'], $data['scalarType'], $data['phpDoc'] !== null ? \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::decode($data['phpDoc']['data']) : null, $data['implements'], array_map(static function (array $node): ExportedNode {
            $nodeType = $node['type'];
            return $nodeType::decode($node['data']);
        }, $data['statements']), array_map(static function (array $attributeData): \PHPStan\Dependency\ExportedNode\ExportedAttributeNode {
            if ($attributeData['type'] !== \PHPStan\Dependency\ExportedNode\ExportedAttributeNode::class) {
                throw new ShouldNotHappenException();
            }
            return \PHPStan\Dependency\ExportedNode\ExportedAttributeNode::decode($attributeData['data']);
        }, $data['attributes']));
    }
    /**
     * @return self::TYPE_ENUM
     */
    public function getType(): string
    {
        return self::TYPE_ENUM;
    }
    public function getName(): string
    {
        return $this->name;
    }
}
