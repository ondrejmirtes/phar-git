<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
#[\PHPStan\DependencyInjection\AutowiredService]
final class ReflectionClassStubFilesExtension implements \PHPStan\PhpDoc\StubFilesExtension
{
    private PhpVersion $phpVersion;
    public function __construct(PhpVersion $phpVersion)
    {
        $this->phpVersion = $phpVersion;
    }
    public function getFiles(): array
    {
        if (!$this->phpVersion->supportsLazyObjects()) {
            return [__DIR__ . '/../../stubs/ReflectionClass.stub'];
        }
        return [__DIR__ . '/../../stubs/ReflectionClassWithLazyObjects.stub'];
    }
}
