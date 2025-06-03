<?php

declare (strict_types=1);
namespace PHPStan\Rules;

use function array_merge;
/**
 * @api
 */
final class RuleLevelHelperAcceptsResult
{
    /**
     * @readonly
     */
    public bool $result;
    /**
     * @readonly
     * @var list<string>
     */
    public array $reasons;
    /**
     * @param list<string> $reasons
     */
    public function __construct(bool $result, array $reasons)
    {
        $this->result = $result;
        $this->reasons = $reasons;
    }
    public function and(self $other): self
    {
        return new self($this->result && $other->result, array_merge($this->reasons, $other->reasons));
    }
    /**
     * @param callable(string): string $cb
     */
    public function decorateReasons(callable $cb): self
    {
        $reasons = [];
        foreach ($this->reasons as $reason) {
            $reasons[] = $cb($reason);
        }
        return new self($this->result, $reasons);
    }
}
