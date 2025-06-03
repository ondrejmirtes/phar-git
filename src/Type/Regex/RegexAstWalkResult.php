<?php

declare (strict_types=1);
namespace PHPStan\Type\Regex;

use PHPStan\Type\StringType;
use PHPStan\Type\Type;
/** @immutable */
final class RegexAstWalkResult
{
    private int $alternationId;
    private int $captureGroupId;
    /**
     * @var array<int, RegexCapturingGroup>
     */
    private array $capturingGroups;
    /**
     * @var list<string>
     */
    private array $markVerbs;
    private Type $subjectBaseType;
    /**
     * @param array<int, RegexCapturingGroup> $capturingGroups
     * @param list<string> $markVerbs
     */
    public function __construct(int $alternationId, int $captureGroupId, array $capturingGroups, array $markVerbs, Type $subjectBaseType)
    {
        $this->alternationId = $alternationId;
        $this->captureGroupId = $captureGroupId;
        $this->capturingGroups = $capturingGroups;
        $this->markVerbs = $markVerbs;
        $this->subjectBaseType = $subjectBaseType;
    }
    public static function createEmpty(): self
    {
        return new self(
            -1,
            // use different start-index for groups to make it easier to distinguish groupids from other ids
            100,
            [],
            [],
            new StringType()
        );
    }
    public function nextAlternationId(): self
    {
        return new self($this->alternationId + 1, $this->captureGroupId, $this->capturingGroups, $this->markVerbs, $this->subjectBaseType);
    }
    public function nextCaptureGroupId(): self
    {
        return new self($this->alternationId, $this->captureGroupId + 1, $this->capturingGroups, $this->markVerbs, $this->subjectBaseType);
    }
    public function addCapturingGroup(\PHPStan\Type\Regex\RegexCapturingGroup $group): self
    {
        $capturingGroups = $this->capturingGroups;
        $capturingGroups[$group->getId()] = $group;
        return new self($this->alternationId, $this->captureGroupId, $capturingGroups, $this->markVerbs, $this->subjectBaseType);
    }
    public function markVerb(string $markVerb): self
    {
        $verbs = $this->markVerbs;
        $verbs[] = $markVerb;
        return new self($this->alternationId, $this->captureGroupId, $this->capturingGroups, $verbs, $this->subjectBaseType);
    }
    public function withSubjectBaseType(Type $subjectBaseType): self
    {
        return new self($this->alternationId, $this->captureGroupId, $this->capturingGroups, $this->markVerbs, $subjectBaseType);
    }
    public function getAlternationId(): int
    {
        return $this->alternationId;
    }
    public function getCaptureGroupId(): int
    {
        return $this->captureGroupId;
    }
    /**
     * @return array<int, RegexCapturingGroup>
     */
    public function getCapturingGroups(): array
    {
        return $this->capturingGroups;
    }
    /**
     * @return list<string>
     */
    public function getMarkVerbs(): array
    {
        return $this->markVerbs;
    }
    public function getSubjectBaseType(): Type
    {
        return $this->subjectBaseType;
    }
}
