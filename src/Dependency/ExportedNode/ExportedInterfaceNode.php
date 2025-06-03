<?php

declare (strict_types=1);
namespace PHPStan\Dependency\ExportedNode;

use JsonSerializable;
use PHPStan\Dependency\ExportedNode;
use PHPStan\Dependency\RootExportedNode;
use ReturnTypeWillChange;
use function array_map;
use function count;
final class ExportedInterfaceNode implements RootExportedNode, JsonSerializable
{
    private string $name;
    private ?\PHPStan\Dependency\ExportedNode\ExportedPhpDocNode $phpDoc;
    /**
     * @var string[]
     */
    private array $extends;
    /**
     * @var ExportedNode[]
     */
    private array $statements;
    /**
     * @param string[] $extends
     * @param ExportedNode[] $statements
     */
    public function __construct(string $name, ?\PHPStan\Dependency\ExportedNode\ExportedPhpDocNode $phpDoc, array $extends, array $statements)
    {
        $this->name = $name;
        $this->phpDoc = $phpDoc;
        $this->extends = $extends;
        $this->statements = $statements;
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
        return $this->name === $node->name && $this->extends === $node->extends;
    }
    /**
     * @param mixed[] $properties
     */
    public static function __set_state(array $properties): self
    {
        return new self($properties['name'], $properties['phpDoc'], $properties['extends'], $properties['statements']);
    }
    /**
     * @return mixed
     */
    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return ['type' => self::class, 'data' => ['name' => $this->name, 'phpDoc' => $this->phpDoc, 'extends' => $this->extends, 'statements' => $this->statements]];
    }
    /**
     * @param mixed[] $data
     */
    public static function decode(array $data): self
    {
        return new self($data['name'], $data['phpDoc'] !== null ? \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::decode($data['phpDoc']['data']) : null, $data['extends'], array_map(static function (array $node): ExportedNode {
            $nodeType = $node['type'];
            return $nodeType::decode($node['data']);
        }, $data['statements']));
    }
    /**
     * @return self::TYPE_INTERFACE
     */
    public function getType(): string
    {
        return self::TYPE_INTERFACE;
    }
    public function getName(): string
    {
        return $this->name;
    }
}
