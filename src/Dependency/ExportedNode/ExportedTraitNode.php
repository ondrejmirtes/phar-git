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
final class ExportedTraitNode implements RootExportedNode, JsonSerializable
{
    private string $name;
    private ?\PHPStan\Dependency\ExportedNode\ExportedPhpDocNode $phpDoc;
    /**
     * @var string[]
     */
    private array $usedTraits;
    /**
     * @var ExportedTraitUseAdaptation[]
     */
    private array $traitUseAdaptations;
    /**
     * @var ExportedNode[]
     */
    private array $statements;
    /**
     * @var ExportedAttributeNode[]
     */
    private array $attributes;
    /**
     * @param string[] $usedTraits
     * @param ExportedTraitUseAdaptation[] $traitUseAdaptations
     * @param ExportedNode[] $statements
     * @param ExportedAttributeNode[] $attributes
     */
    public function __construct(string $name, ?\PHPStan\Dependency\ExportedNode\ExportedPhpDocNode $phpDoc, array $usedTraits, array $traitUseAdaptations, array $statements, array $attributes)
    {
        $this->name = $name;
        $this->phpDoc = $phpDoc;
        $this->usedTraits = $usedTraits;
        $this->traitUseAdaptations = $traitUseAdaptations;
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
        if (count($this->attributes) !== count($node->attributes)) {
            return \false;
        }
        foreach ($this->attributes as $i => $attribute) {
            if (!$attribute->equals($node->attributes[$i])) {
                return \false;
            }
        }
        if (count($this->traitUseAdaptations) !== count($node->traitUseAdaptations)) {
            return \false;
        }
        foreach ($this->traitUseAdaptations as $i => $ourTraitUseAdaptation) {
            $theirTraitUseAdaptation = $node->traitUseAdaptations[$i];
            if (!$ourTraitUseAdaptation->equals($theirTraitUseAdaptation)) {
                return \false;
            }
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
        return $this->name === $node->name && $this->usedTraits === $node->usedTraits;
    }
    /**
     * @param mixed[] $properties
     */
    public static function __set_state(array $properties): self
    {
        return new self($properties['name'], $properties['phpDoc'], $properties['usedTraits'], $properties['traitUseAdaptations'], $properties['statements'], $properties['attributes']);
    }
    /**
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return ['type' => self::class, 'data' => ['name' => $this->name, 'phpDoc' => $this->phpDoc, 'usedTraits' => $this->usedTraits, 'traitUseAdaptations' => $this->traitUseAdaptations, 'statements' => $this->statements, 'attributes' => $this->attributes]];
    }
    /**
     * @param mixed[] $data
     */
    public static function decode(array $data): self
    {
        return new self($data['name'], $data['phpDoc'] !== null ? \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::decode($data['phpDoc']['data']) : null, $data['usedTraits'], array_map(static function (array $traitUseAdaptationData): \PHPStan\Dependency\ExportedNode\ExportedTraitUseAdaptation {
            if ($traitUseAdaptationData['type'] !== \PHPStan\Dependency\ExportedNode\ExportedTraitUseAdaptation::class) {
                throw new ShouldNotHappenException();
            }
            return \PHPStan\Dependency\ExportedNode\ExportedTraitUseAdaptation::decode($traitUseAdaptationData['data']);
        }, $data['traitUseAdaptations']), array_map(static function (array $node): ExportedNode {
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
     * @return self::TYPE_TRAIT
     */
    public function getType(): string
    {
        return self::TYPE_TRAIT;
    }
    public function getName(): string
    {
        return $this->name;
    }
}
