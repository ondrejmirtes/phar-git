<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
/**
 * @api
 */
final class AcceptsResult
{
    /**
     * @readonly
     */
    public TrinaryLogic $result;
    /**
     * @readonly
     * @var list<string>
     */
    public array $reasons;
    /**
     * @api
     * @param list<string> $reasons
     */
    public function __construct(TrinaryLogic $result, array $reasons)
    {
        $this->result = $result;
        $this->reasons = $reasons;
    }
    public function yes(): bool
    {
        return $this->result->yes();
    }
    public function maybe(): bool
    {
        return $this->result->maybe();
    }
    public function no(): bool
    {
        return $this->result->no();
    }
    public static function createYes(): self
    {
        return new self(TrinaryLogic::createYes(), []);
    }
    /**
     * @param list<string> $reasons
     */
    public static function createNo(array $reasons = []): self
    {
        return new self(TrinaryLogic::createNo(), $reasons);
    }
    public static function createMaybe(): self
    {
        return new self(TrinaryLogic::createMaybe(), []);
    }
    public static function createFromBoolean(bool $value): self
    {
        return new self(TrinaryLogic::createFromBoolean($value), []);
    }
    public function and(self $other): self
    {
        return new self($this->result->and($other->result), array_values(array_unique(array_merge($this->reasons, $other->reasons))));
    }
    public function or(self $other): self
    {
        return new self($this->result->or($other->result), array_values(array_unique(array_merge($this->reasons, $other->reasons))));
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
    public static function extremeIdentity(self ...$operands): self
    {
        if ($operands === []) {
            throw new ShouldNotHappenException();
        }
        $result = TrinaryLogic::extremeIdentity(...array_map(static fn(self $result) => $result->result, $operands));
        $reasons = [];
        foreach ($operands as $operand) {
            foreach ($operand->reasons as $reason) {
                $reasons[] = $reason;
            }
        }
        return new self($result, array_values(array_unique($reasons)));
    }
    public static function maxMin(self ...$operands): self
    {
        if ($operands === []) {
            throw new ShouldNotHappenException();
        }
        $result = TrinaryLogic::maxMin(...array_map(static fn(self $result) => $result->result, $operands));
        $reasons = [];
        foreach ($operands as $operand) {
            foreach ($operand->reasons as $reason) {
                $reasons[] = $reason;
            }
        }
        return new self($result, array_values(array_unique($reasons)));
    }
}
