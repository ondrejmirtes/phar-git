<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
#[\PHPStan\DependencyInjection\AutowiredService]
final class SocketSelectStubFilesExtension implements \PHPStan\PhpDoc\StubFilesExtension
{
    private PhpVersion $phpVersion;
    public function __construct(PhpVersion $phpVersion)
    {
        $this->phpVersion = $phpVersion;
    }
    public function getFiles() : array
    {
        if ($this->phpVersion->getVersionId() >= 80000) {
            return [__DIR__ . '/../../stubs/socket_select_php8.stub'];
        }
        return [__DIR__ . '/../../stubs/socket_select.stub'];
    }
}
