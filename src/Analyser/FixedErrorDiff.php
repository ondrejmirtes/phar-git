<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

final class FixedErrorDiff
{
    /**
     * @readonly
     */
    public string $originalHash;
    /**
     * @readonly
     */
    public string $diff;
    public function __construct(string $originalHash, string $diff)
    {
        $this->originalHash = $originalHash;
        $this->diff = $diff;
    }
    /**
     * @param mixed[] $properties
     */
    public static function __set_state(array $properties): self
    {
        return new self($properties['originalHash'], $properties['diff']);
    }
}
