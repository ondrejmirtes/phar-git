<?php

declare (strict_types=1);
namespace PHPStan\Dependency\ExportedNode;

use JsonSerializable;
use PHPStan\Dependency\ExportedNode;
use PHPStan\ShouldNotHappenException;
use ReturnTypeWillChange;
use function array_map;
use function count;
final class ExportedClassConstantsNode implements ExportedNode, JsonSerializable
{
    /**
     * @var ExportedClassConstantNode[]
     */
    private array $constants;
    private bool $public;
    private bool $private;
    private bool $final;
    private ?\PHPStan\Dependency\ExportedNode\ExportedPhpDocNode $phpDoc;
    /**
     * @param ExportedClassConstantNode[] $constants
     */
    public function __construct(array $constants, bool $public, bool $private, bool $final, ?\PHPStan\Dependency\ExportedNode\ExportedPhpDocNode $phpDoc)
    {
        $this->constants = $constants;
        $this->public = $public;
        $this->private = $private;
        $this->final = $final;
        $this->phpDoc = $phpDoc;
    }
    public function equals(ExportedNode $node) : bool
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
        if (count($this->constants) !== count($node->constants)) {
            return \false;
        }
        foreach ($this->constants as $i => $constant) {
            if (!$constant->equals($node->constants[$i])) {
                return \false;
            }
        }
        return $this->public === $node->public && $this->private === $node->private && $this->final === $node->final;
    }
    /**
     * @param mixed[] $properties
     */
    public static function __set_state(array $properties) : self
    {
        return new self($properties['constants'], $properties['public'], $properties['private'], $properties['final'], $properties['phpDoc']);
    }
    /**
     * @param mixed[] $data
     */
    public static function decode(array $data) : self
    {
        return new self(array_map(static function (array $constantData) : \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode {
            if ($constantData['type'] !== \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::class) {
                throw new ShouldNotHappenException();
            }
            return \PHPStan\Dependency\ExportedNode\ExportedClassConstantNode::decode($constantData['data']);
        }, $data['constants']), $data['public'], $data['private'], $data['final'], $data['phpDoc'] !== null ? \PHPStan\Dependency\ExportedNode\ExportedPhpDocNode::decode($data['phpDoc']['data']) : null);
    }
    /**
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return ['type' => self::class, 'data' => ['constants' => $this->constants, 'public' => $this->public, 'private' => $this->private, 'final' => $this->final, 'phpDoc' => $this->phpDoc]];
    }
}
