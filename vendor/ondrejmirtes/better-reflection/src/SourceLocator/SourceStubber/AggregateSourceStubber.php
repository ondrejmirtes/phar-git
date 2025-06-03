<?php

declare (strict_types=1);
namespace PHPStan\BetterReflection\SourceLocator\SourceStubber;

use function array_merge;
use function array_reduce;
use function array_values;
class AggregateSourceStubber implements \PHPStan\BetterReflection\SourceLocator\SourceStubber\SourceStubber
{
    /** @var list<SourceStubber> */
    private array $sourceStubbers;
    public function __construct(\PHPStan\BetterReflection\SourceLocator\SourceStubber\SourceStubber $sourceStubber, \PHPStan\BetterReflection\SourceLocator\SourceStubber\SourceStubber ...$otherSourceStubbers)
    {
        $this->sourceStubbers = array_values(array_merge([$sourceStubber], $otherSourceStubbers));
    }
    /** @param class-string|trait-string $className */
    public function generateClassStub(string $className) : ?\PHPStan\BetterReflection\SourceLocator\SourceStubber\StubData
    {
        foreach ($this->sourceStubbers as $sourceStubber) {
            $stubData = $sourceStubber->generateClassStub($className);
            if ($stubData !== null) {
                return $stubData;
            }
        }
        return null;
    }
    public function generateFunctionStub(string $functionName) : ?\PHPStan\BetterReflection\SourceLocator\SourceStubber\StubData
    {
        foreach ($this->sourceStubbers as $sourceStubber) {
            $stubData = $sourceStubber->generateFunctionStub($functionName);
            if ($stubData !== null) {
                return $stubData;
            }
        }
        return null;
    }
    public function generateConstantStub(string $constantName) : ?\PHPStan\BetterReflection\SourceLocator\SourceStubber\StubData
    {
        return array_reduce($this->sourceStubbers, static fn(?\PHPStan\BetterReflection\SourceLocator\SourceStubber\StubData $stubData, \PHPStan\BetterReflection\SourceLocator\SourceStubber\SourceStubber $sourceStubber): ?\PHPStan\BetterReflection\SourceLocator\SourceStubber\StubData => $stubData ?? $sourceStubber->generateConstantStub($constantName), null);
    }
}
