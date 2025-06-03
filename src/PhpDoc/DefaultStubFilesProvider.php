<?php

declare (strict_types=1);
namespace PHPStan\PhpDoc;

use PHPStan\DependencyInjection\Container;
use PHPStan\Internal\ComposerHelper;
use function array_filter;
use function array_values;
use function str_contains;
use function strtr;
final class DefaultStubFilesProvider implements \PHPStan\PhpDoc\StubFilesProvider
{
    private Container $container;
    /**
     * @var string[]
     */
    private array $stubFiles;
    /**
     * @var string[]
     */
    private array $composerAutoloaderProjectPaths;
    /** @var string[]|null */
    private ?array $cachedFiles = null;
    /** @var string[]|null */
    private ?array $cachedProjectFiles = null;
    /**
     * @param string[] $stubFiles
     * @param string[] $composerAutoloaderProjectPaths
     */
    public function __construct(Container $container, array $stubFiles, array $composerAutoloaderProjectPaths)
    {
        $this->container = $container;
        $this->stubFiles = $stubFiles;
        $this->composerAutoloaderProjectPaths = $composerAutoloaderProjectPaths;
    }
    public function getStubFiles(): array
    {
        if ($this->cachedFiles !== null) {
            return $this->cachedFiles;
        }
        $files = $this->stubFiles;
        $extensions = $this->container->getServicesByTag(\PHPStan\PhpDoc\StubFilesExtension::EXTENSION_TAG);
        foreach ($extensions as $extension) {
            foreach ($extension->getFiles() as $extensionFile) {
                $files[] = $extensionFile;
            }
        }
        return $this->cachedFiles = $files;
    }
    public function getProjectStubFiles(): array
    {
        if ($this->cachedProjectFiles !== null) {
            return $this->cachedProjectFiles;
        }
        $filteredStubFiles = $this->getStubFiles();
        foreach ($this->composerAutoloaderProjectPaths as $composerAutoloaderProjectPath) {
            $composerConfig = ComposerHelper::getComposerConfig($composerAutoloaderProjectPath);
            if ($composerConfig === null) {
                continue;
            }
            $vendorDir = ComposerHelper::getVendorDirFromComposerConfig($composerAutoloaderProjectPath, $composerConfig);
            $vendorDir = strtr($vendorDir, '\\', '/');
            $filteredStubFiles = array_filter($filteredStubFiles, static fn(string $file): bool => !str_contains(strtr($file, '\\', '/'), $vendorDir));
        }
        return $this->cachedProjectFiles = array_values($filteredStubFiles);
    }
}
