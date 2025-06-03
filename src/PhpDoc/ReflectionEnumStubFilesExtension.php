<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
#[\PHPStan\DependencyInjection\AutowiredService]
final class ReflectionEnumStubFilesExtension implements \PHPStan\PhpDoc\StubFilesExtension
{
    private PhpVersion $phpVersion;
    public function __construct(PhpVersion $phpVersion)
    {
        $this->phpVersion = $phpVersion;
    }
    public function getFiles() : array
    {
        if (!$this->phpVersion->supportsEnums()) {
            return [];
        }
        if (!$this->phpVersion->supportsLazyObjects()) {
            return [__DIR__ . '/../../stubs/ReflectionEnum.stub'];
        }
        return [__DIR__ . '/../../stubs/ReflectionEnumWithLazyObjects.stub'];
    }
}
