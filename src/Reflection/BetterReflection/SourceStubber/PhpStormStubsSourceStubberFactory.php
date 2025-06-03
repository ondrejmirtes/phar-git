<?php

declare (strict_types=1);
namespace PHPStan\Reflection\BetterReflection\SourceStubber;

use PhpParser\Parser;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\Node\Printer\Printer;
use PHPStan\Php\PhpVersion;
final class PhpStormStubsSourceStubberFactory
{
    private Parser $phpParser;
    private Printer $printer;
    private PhpVersion $phpVersion;
    public function __construct(Parser $phpParser, Printer $printer, PhpVersion $phpVersion)
    {
        $this->phpParser = $phpParser;
        $this->printer = $printer;
        $this->phpVersion = $phpVersion;
    }
    public function create(): PhpStormStubsSourceStubber
    {
        return new PhpStormStubsSourceStubber($this->phpParser, $this->printer, $this->phpVersion->getVersionId());
    }
}
