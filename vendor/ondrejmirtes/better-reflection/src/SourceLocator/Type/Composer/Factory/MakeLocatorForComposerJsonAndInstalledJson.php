<?php

declare (strict_types=1);
namespace PHPStan\BetterReflection\SourceLocator\Type\Composer\Factory;

use PHPStan\BetterReflection\SourceLocator\Ast\Locator;
use PHPStan\BetterReflection\SourceLocator\Type\AggregateSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\InvalidProjectDirectory;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\MissingComposerJson;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Factory\Exception\MissingInstalledJson;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Psr\Psr0Mapping;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\Psr\Psr4Mapping;
use PHPStan\BetterReflection\SourceLocator\Type\Composer\PsrAutoloaderLocator;
use PHPStan\BetterReflection\SourceLocator\Type\DirectoriesSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\SingleFileSourceLocator;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use function array_filter;
use function array_map;
use function array_merge;
use function array_merge_recursive;
use function array_values;
use function assert;
use function file_get_contents;
use function is_dir;
use function is_file;
use function is_string;
use function json_decode;
use function realpath;
use function rtrim;
use const JSON_THROW_ON_ERROR;
/**
 * @psalm-import-type ComposerAutoload from MakeLocatorForComposerJson
 * @psalm-import-type ComposerPackage from MakeLocatorForComposerJson
 * @psalm-import-type Composer from MakeLocatorForComposerJson
 */
final class MakeLocatorForComposerJsonAndInstalledJson
{
    public function __invoke(string $installationPath, Locator $astLocator): SourceLocator
    {
        $realInstallationPath = (string) realpath($installationPath);
        if (!is_dir($realInstallationPath)) {
            throw InvalidProjectDirectory::atPath($installationPath);
        }
        $composerJsonPath = $realInstallationPath . '/composer.json';
        if (!is_file($composerJsonPath)) {
            throw MissingComposerJson::inProjectPath($installationPath);
        }
        $composerJsonContent = file_get_contents($composerJsonPath);
        assert(is_string($composerJsonContent));
        /** @psalm-var Composer $composer */
        $composer = json_decode($composerJsonContent, \true, 512, JSON_THROW_ON_ERROR);
        $vendorDir = rtrim($composer['config']['vendor-dir'] ?? 'vendor', '/');
        $installedJsonPath = $realInstallationPath . '/' . $vendorDir . '/composer/installed.json';
        if (!is_file($installedJsonPath)) {
            throw MissingInstalledJson::inProjectPath($realInstallationPath . '/' . $vendorDir);
        }
        $jsonContent = file_get_contents($installedJsonPath);
        assert(is_string($jsonContent));
        /** @psalm-var array{packages?: list<ComposerPackage>}|list<ComposerPackage> $installedJson */
        $installedJson = json_decode($jsonContent, \true, 512, JSON_THROW_ON_ERROR);
        /** @psalm-var list<ComposerPackage> $installed */
        $installed = $installedJson['packages'] ?? $installedJson;
        $classMapPaths = array_merge($this->prefixPaths($this->packageToClassMapPaths($composer), $realInstallationPath . '/'), ...array_map(fn(array $package): array => $this->prefixPaths($this->packageToClassMapPaths($package), $this->packagePrefixPath($realInstallationPath, $package, $vendorDir)), $installed));
        $classMapFiles = array_filter($classMapPaths, 'is_file');
        $classMapDirectories = array_values(array_filter($classMapPaths, 'is_dir'));
        $filePaths = array_merge($this->prefixPaths($this->packageToFilePaths($composer), $realInstallationPath . '/'), ...array_map(fn(array $package): array => $this->prefixPaths($this->packageToFilePaths($package), $this->packagePrefixPath($realInstallationPath, $package, $vendorDir)), $installed));
        return new AggregateSourceLocator(array_merge([new PsrAutoloaderLocator(Psr4Mapping::fromArrayMappings(array_merge_recursive($this->prefixWithInstallationPath($this->packageToPsr4AutoloadNamespaces($composer), $realInstallationPath), ...array_map(fn(array $package): array => $this->prefixWithPackagePath($this->packageToPsr4AutoloadNamespaces($package), $realInstallationPath, $package, $vendorDir), $installed))), $astLocator), new PsrAutoloaderLocator(Psr0Mapping::fromArrayMappings(array_merge_recursive($this->prefixWithInstallationPath($this->packageToPsr0AutoloadNamespaces($composer), $realInstallationPath), ...array_map(fn(array $package): array => $this->prefixWithPackagePath($this->packageToPsr0AutoloadNamespaces($package), $realInstallationPath, $package, $vendorDir), $installed))), $astLocator), new DirectoriesSourceLocator($classMapDirectories, $astLocator)], ...array_map(static function (string $file) use ($astLocator): array {
            assert($file !== '');
            return [new SingleFileSourceLocator($file, $astLocator)];
        }, array_merge($classMapFiles, $filePaths))));
    }
    /**
     * @param ComposerPackage|Composer $package
     *
     * @return array<string, list<string>>
     */
    private function packageToPsr4AutoloadNamespaces(array $package): array
    {
        return array_map(static fn($namespacePaths): array => (array) $namespacePaths, $package['autoload']['psr-4'] ?? []);
    }
    /**
     * @param ComposerPackage|Composer $package
     *
     * @return array<string, list<string>>
     */
    private function packageToPsr0AutoloadNamespaces(array $package): array
    {
        return array_map(static fn($namespacePaths): array => (array) $namespacePaths, $package['autoload']['psr-0'] ?? []);
    }
    /**
     * @param ComposerPackage|Composer $package
     *
     * @return list<string>
     */
    private function packageToClassMapPaths(array $package): array
    {
        return $package['autoload']['classmap'] ?? [];
    }
    /**
     * @param ComposerPackage|Composer $package
     *
     * @return list<string>
     */
    private function packageToFilePaths(array $package): array
    {
        return $package['autoload']['files'] ?? [];
    }
    /** @param ComposerPackage $package */
    private function packagePrefixPath(string $trimmedInstallationPath, array $package, string $vendorDir): string
    {
        return $trimmedInstallationPath . '/' . $vendorDir . '/' . $package['name'] . '/';
    }
    /**
     * @param array<string, list<string>> $paths
     * @param ComposerPackage             $package
     *
     * @return array<string, list<string>>
     */
    private function prefixWithPackagePath(array $paths, string $trimmedInstallationPath, array $package, string $vendorDir): array
    {
        $prefix = $this->packagePrefixPath($trimmedInstallationPath, $package, $vendorDir);
        return array_map(fn(array $paths): array => $this->prefixPaths($paths, $prefix), $paths);
    }
    /**
     * @param array<string, list<string>> $paths
     *
     * @return array<string, list<string>>
     */
    private function prefixWithInstallationPath(array $paths, string $trimmedInstallationPath): array
    {
        return array_map(fn(array $paths): array => $this->prefixPaths($paths, $trimmedInstallationPath . '/'), $paths);
    }
    /**
     * @param list<string> $paths
     *
     * @return list<string>
     */
    private function prefixPaths(array $paths, string $prefix): array
    {
        return array_map(static fn(string $path): string => $prefix . $path, $paths);
    }
}
