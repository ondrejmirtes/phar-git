<?php

declare (strict_types=1);
namespace PHPStan\Collectors;

use JsonSerializable;
use PhpParser\Node;
use ReturnTypeWillChange;
/**
 * @api
 *
 * @phpstan-type CollectorData = array<string, array<class-string<Collector<Node, mixed>>, list<mixed>>>
 */
final class CollectedData implements JsonSerializable
{
    /**
     * @var mixed
     */
    private $data;
    private string $filePath;
    /**
     * @var class-string<Collector<Node, mixed>>
     */
    private string $collectorType;
    /**
     * @param mixed $data
     * @param class-string<Collector<Node, mixed>> $collectorType
     */
    public function __construct($data, string $filePath, string $collectorType)
    {
        $this->data = $data;
        $this->filePath = $filePath;
        $this->collectorType = $collectorType;
    }
    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
    public function getFilePath(): string
    {
        return $this->filePath;
    }
    public function changeFilePath(string $newFilePath): self
    {
        return new self($this->data, $newFilePath, $this->collectorType);
    }
    /**
     * @return class-string<Collector<Node, mixed>>
     */
    public function getCollectorType(): string
    {
        return $this->collectorType;
    }
    /**
     * @return mixed
     */
    #[ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return ['data' => $this->data, 'filePath' => $this->filePath, 'collectorType' => $this->collectorType];
    }
    /**
     * @param mixed[] $json
     */
    public static function decode(array $json): self
    {
        return new self($json['data'], $json['filePath'], $json['collectorType']);
    }
    /**
     * @param mixed[] $properties
     */
    public static function __set_state(array $properties): self
    {
        return new self($properties['data'], $properties['filePath'], $properties['collectorType']);
    }
}
