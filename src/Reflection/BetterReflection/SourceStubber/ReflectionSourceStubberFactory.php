<?php

declare (strict_types=1);
namespace PHPStan\Reflection\BetterReflection\SourceStubber;

use PHPStan\BetterReflection\SourceLocator\SourceStubber\ReflectionSourceStubber;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Printer\Printer;
use PHPStan\Php\PhpVersion;
#[\PHPStan\DependencyInjection\AutowiredService]
final class ReflectionSourceStubberFactory
{
    private Printer $printer;
    private PhpVersion $phpVersion;
    public function __construct(Printer $printer, PhpVersion $phpVersion)
    {
        $this->printer = $printer;
        $this->phpVersion = $phpVersion;
    }
    public function create() : ReflectionSourceStubber
    {
        return new ReflectionSourceStubber($this->printer, $this->phpVersion->getVersionId());
    }
}
