<?php

declare (strict_types=1);
namespace PHPStan\Php;

use _PHPStan_checksum\Composer\Semver\VersionParser;
use _PHPStan_checksum\Nette\Utils\Strings;
use PHPStan\Internal\ComposerHelper;
use function count;
use function end;
use function is_string;
use function min;
use function sprintf;
final class ComposerPhpVersionFactory
{
    /**
     * @var string[]
     */
    private array $composerAutoloaderProjectPaths;
    private ?\PHPStan\Php\PhpVersion $minVersion = null;
    private ?\PHPStan\Php\PhpVersion $maxVersion = null;
    private bool $initialized = \false;
    /**
     * @param string[] $composerAutoloaderProjectPaths
     */
    public function __construct(array $composerAutoloaderProjectPaths)
    {
        $this->composerAutoloaderProjectPaths = $composerAutoloaderProjectPaths;
    }
    private function initializeVersions() : void
    {
        $this->initialized = \true;
        // don't limit minVersion... PHPStan can analyze even PHP5
        $this->maxVersion = new \PHPStan\Php\PhpVersion(\PHPStan\Php\PhpVersionFactory::MAX_PHP_VERSION);
        // fallback to composer.json based php-version constraint
        $composerPhpVersion = $this->getComposerRequireVersion();
        if ($composerPhpVersion === null) {
            return;
        }
        $parser = new VersionParser();
        $constraint = $parser->parseConstraints($composerPhpVersion);
        if (!$constraint->getLowerBound()->isZero()) {
            $minVersion = $this->buildVersion($constraint->getLowerBound()->getVersion(), \false);
            if ($minVersion !== null) {
                $this->minVersion = new \PHPStan\Php\PhpVersion($minVersion->getVersionId());
            }
        }
        if ($constraint->getUpperBound()->isPositiveInfinity()) {
            return;
        }
        $this->maxVersion = $this->buildVersion($constraint->getUpperBound()->getVersion(), \true);
    }
    public function getMinVersion() : ?\PHPStan\Php\PhpVersion
    {
        if ($this->initialized === \false) {
            $this->initializeVersions();
        }
        return $this->minVersion;
    }
    public function getMaxVersion() : ?\PHPStan\Php\PhpVersion
    {
        if ($this->initialized === \false) {
            $this->initializeVersions();
        }
        return $this->maxVersion;
    }
    private function getComposerRequireVersion() : ?string
    {
        $composerPhpVersion = null;
        if (count($this->composerAutoloaderProjectPaths) > 0) {
            $composer = ComposerHelper::getComposerConfig(end($this->composerAutoloaderProjectPaths));
            if ($composer !== null) {
                $requiredVersion = $composer['require']['php'] ?? null;
                if (is_string($requiredVersion)) {
                    $composerPhpVersion = $requiredVersion;
                }
            }
        }
        return $composerPhpVersion;
    }
    private function buildVersion(string $version, bool $isMaxVersion) : ?\PHPStan\Php\PhpVersion
    {
        $matches = Strings::match($version, '#^(\\d+)\\.(\\d+)(?:\\.(\\d+))?#');
        if ($matches === null) {
            return null;
        }
        $major = $matches[1];
        $minor = $matches[2];
        $patch = $matches[3] ?? 0;
        $versionId = (int) sprintf('%d%02d%02d', $major, $minor, $patch);
        if ($isMaxVersion && $version === '6.0.0.0-dev') {
            $versionId = min($versionId, \PHPStan\Php\PhpVersionFactory::MAX_PHP5_VERSION);
        } elseif ($isMaxVersion && $version === '8.0.0.0-dev') {
            $versionId = min($versionId, \PHPStan\Php\PhpVersionFactory::MAX_PHP7_VERSION);
        } else {
            $versionId = min($versionId, \PHPStan\Php\PhpVersionFactory::MAX_PHP_VERSION);
        }
        return new \PHPStan\Php\PhpVersion($versionId);
    }
}
