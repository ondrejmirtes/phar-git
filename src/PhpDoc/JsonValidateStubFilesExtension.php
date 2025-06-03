<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
#[\PHPStan\DependencyInjection\AutowiredService]
final class JsonValidateStubFilesExtension implements \PHPStan\PhpDoc\StubFilesExtension
{
    private PhpVersion $phpVersion;
    public function __construct(PhpVersion $phpVersion)
    {
        $this->phpVersion = $phpVersion;
    }
    public function getFiles() : array
    {
        if (!$this->phpVersion->supportsJsonValidate()) {
            return [];
        }
        return [__DIR__ . '/../../stubs/json_validate.stub'];
    }
}
