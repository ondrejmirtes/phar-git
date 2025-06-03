<?php

declare (strict_types=1);
namespace PHPStan\DependencyInjection;

use _PHPStan_checksum\Nette\DI\Config\Loader;
use PHPStan\File\FileHelper;
use function getenv;
final class LoaderFactory
{
    private FileHelper $fileHelper;
    private string $rootDir;
    private string $currentWorkingDirectory;
    private ?string $generateBaselineFile;
    /**
     * @var list<string>
     */
    private array $expandRelativePaths;
    /**
     * @param list<string> $expandRelativePaths
     */
    public function __construct(FileHelper $fileHelper, string $rootDir, string $currentWorkingDirectory, ?string $generateBaselineFile, array $expandRelativePaths)
    {
        $this->fileHelper = $fileHelper;
        $this->rootDir = $rootDir;
        $this->currentWorkingDirectory = $currentWorkingDirectory;
        $this->generateBaselineFile = $generateBaselineFile;
        $this->expandRelativePaths = $expandRelativePaths;
    }
    public function createLoader() : Loader
    {
        $neonAdapter = new \PHPStan\DependencyInjection\NeonAdapter($this->expandRelativePaths);
        $loader = new \PHPStan\DependencyInjection\NeonLoader($this->fileHelper, $this->generateBaselineFile);
        $loader->addAdapter('dist', $neonAdapter);
        $loader->addAdapter('neon', $neonAdapter);
        $loader->setParameters(['rootDir' => $this->rootDir, 'currentWorkingDirectory' => $this->currentWorkingDirectory, 'env' => getenv()]);
        return $loader;
    }
}
