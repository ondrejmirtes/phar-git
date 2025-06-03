<?php

declare (strict_types=1);
namespace PHPStan\Reflection\SignatureMap;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
#[AutowiredService]
final class SignatureMapProviderFactory
{
    private PhpVersion $phpVersion;
    private \PHPStan\Reflection\SignatureMap\FunctionSignatureMapProvider $functionSignatureMapProvider;
    private \PHPStan\Reflection\SignatureMap\Php8SignatureMapProvider $php8SignatureMapProvider;
    public function __construct(PhpVersion $phpVersion, \PHPStan\Reflection\SignatureMap\FunctionSignatureMapProvider $functionSignatureMapProvider, \PHPStan\Reflection\SignatureMap\Php8SignatureMapProvider $php8SignatureMapProvider)
    {
        $this->phpVersion = $phpVersion;
        $this->functionSignatureMapProvider = $functionSignatureMapProvider;
        $this->php8SignatureMapProvider = $php8SignatureMapProvider;
    }
    public function create(): \PHPStan\Reflection\SignatureMap\SignatureMapProvider
    {
        if ($this->phpVersion->getVersionId() < 80000) {
            return $this->functionSignatureMapProvider;
        }
        return $this->php8SignatureMapProvider;
    }
}
