<?php

declare (strict_types=1);
namespace PHPStan\Dependency\ExportedNode;

use JsonSerializable;
use PHPStan\Dependency\ExportedNode;
use PHPStan\ShouldNotHappenException;
use ReturnTypeWillChange;
use function array_map;
use function count;
final class ExportedPropertiesNode implements JsonSerializable, ExportedNode
{
    /**
     * @var string[]
     */
    private array $names;
    private ?\PHPStan\Dependency\ExportedNode\ExportedPhpDocNode $phpDoc;
    private ?string $type;
    private bool $public;
    private bool $private;
    private bool $static;
    private bool $readonly;
    private bool $abstract;
    private bool $final;
    private bool $publicSet;
    private bool $protectedSet;
    private bool $privateSet;
    private bool $virtual;
    /**
     * @var ExportedAttributeNode[]
     */
    private array $attributes;
    /**
     * @var ExportedPropertyHookNode[]
     */
    private array $hooks;
    /**
     * @param string[] $names
     * @param ExportedAttributeNode[] $attributes
     * @param ExportedPropertyHookNode[] $hooks
     */
    public function __construct(array $names, ?\PHPStan\Dependency\ExportedNode\ExportedPhpDocNode $phpDoc, ?string $type, bool $public, bool $private, bool $static, bool $readonly, bool $abstract, bool $final, bool $publicSet, bool $protectedSet, bool $privateSet, bool $virtual, array $attributes, array $hooks)
    {
        $this->names = $names;
        $this->phpDoc = $phpDoc;
        $this->type = $type;
        $this->public = $public;
        $this->private = $private;
        $this->static = $static;
        $this->readonly = $readonly;
        $this->abstract = $abstract;
        $this->final = $final;
        $this->publicSet = $publicSet;
        $this->protectedSet = $protectedSet;
        $this->privateSet = $privateSet;
        $this->virtual = $virtual;
        $this->attributes = $attributes;
        $this->hooks = $hooks;
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
        if (count($this->names) !== count($node->names)) {
            return \false;
        }
        foreach ($this->names as $i => $name) {
            if ($name !== $node->names[$i]) {
                return \false;
            }
        }
        if (count($this->attributes) !== count($node->attributes)) {
            return \false;
        }
        foreach ($this->attributes as $i => $attribute) {
            if (!$attribute->equals($node->attributes[$i])) {
                return \false;
            }
        }
        if (count($this->hooks) !== count($node->hooks)) {
            return \false;
        }
        foreach ($this->hooks as $i => $hook) {
            if (!$hook->equals($node->hooks[$i])) {
                return \false;
            }
        }
        return $this->type === $node->type && $this->public === $node->public && $this->private === $node->private && $this->static === $node->static && $this->readonly === $node->readonly && $this->abstract === $node->abstract && $this->final === $node->final && $this->publicSet === $node->publicSet && $this->protectedSet === $node->protectedSet && $this->privateSet === $node->privateSet && $this->virtual === $node->virtual;
    }
    /**
     * @param mixed[] $properties
     */
    public static function __set_state(array $properties): self
    {
        return new self($properties['names'], $properties['phpDoc'], $properties['type'], $properties['public'], $properties['private'], $properties['static'], $properties['readonly'], $properties['abstract'], $properties['final'], $properties['publicSet'], $properties['protectedSet'], $properties['privateSet'], $properties['virtual'], $properties['attributes'], $properties['hooks']);
    }
    /**
     * @param mixed[] $data
     */
    public static function decode(array $data): self
    {
        return new self($data['names'], $data['phpDoc'] !== null ? \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::decode($data['phpDoc']['data']) : null, $data['type'], $data['public'], $data['private'], $data['static'], $data['readonly'], $data['abstract'], $data['final'], $data['publicSet'], $data['protectedSet'], $data['privateSet'], $data['virtual'], array_map(static function (array $attributeData): \PHPStan\Dependency\ExportedNode\ExportedAttributeNode {
            if ($attributeData['type'] !== \PHPStan\Dependency\ExportedNode\ExportedAttributeNode::class) {
                throw new ShouldNotHappenException();
            }
            return \PHPStan\Dependency\ExportedNode\ExportedAttributeNode::decode($attributeData['data']);
        }, $data['attributes']), array_map(static function (array $attributeData): \PHPStan\Dependency\ExportedNode\ExportedPropertyHookNode {
            if ($attributeData['type'] !== \PHPStan\Dependency\ExportedNode\ExportedPropertyHookNode::class) {
                throw new ShouldNotHappenException();
            }
            return \PHPStan\Dependency\ExportedNode\ExportedPropertyHookNode::decode($attributeData['data']);
        }, $data['hooks']));
    }
    /**
     * @return mixed
     */
    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return ['type' => self::class, 'data' => ['names' => $this->names, 'phpDoc' => $this->phpDoc, 'type' => $this->type, 'public' => $this->public, 'private' => $this->private, 'static' => $this->static, 'readonly' => $this->readonly, 'abstract' => $this->abstract, 'final' => $this->final, 'publicSet' => $this->publicSet, 'protectedSet' => $this->protectedSet, 'privateSet' => $this->privateSet, 'virtual' => $this->virtual, 'attributes' => $this->attributes, 'hooks' => $this->hooks]];
    }
}
