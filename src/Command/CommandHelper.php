<?php

declare (strict_types=1);
namespace PHPStan\Command;

use _PHPStan_checksum\Composer\Semver\Semver;
use _PHPStan_checksum\Composer\XdebugHandler\XdebugHandler;
use _PHPStan_checksum\Nette\DI\Helpers;
use _PHPStan_checksum\Nette\DI\InvalidConfigurationException;
use _PHPStan_checksum\Nette\DI\ServiceCreationException;
use _PHPStan_checksum\Nette\FileNotFoundException;
use _PHPStan_checksum\Nette\InvalidStateException;
use _PHPStan_checksum\Nette\Schema\ValidationException;
use _PHPStan_checksum\Nette\Utils\AssertionException;
use _PHPStan_checksum\Nette\Utils\Strings;
use PHPStan\Cache\FileCacheStorage;
use PHPStan\Command\Symfony\SymfonyOutput;
use PHPStan\Command\Symfony\SymfonyStyle;
use PHPStan\DependencyInjection\Container;
use PHPStan\DependencyInjection\ContainerFactory;
use PHPStan\DependencyInjection\DuplicateIncludedFilesException;
use PHPStan\DependencyInjection\InvalidExcludePathsException;
use PHPStan\DependencyInjection\InvalidIgnoredErrorPatternsException;
use PHPStan\DependencyInjection\LoaderFactory;
use PHPStan\DependencyInjection\MissingImplementedInterfaceInServiceWithTagException;
use PHPStan\ExtensionInstaller\GeneratedConfig;
use PHPStan\File\FileExcluder;
use PHPStan\File\FileFinder;
use PHPStan\File\FileHelper;
use PHPStan\File\ParentDirectoryRelativePathHelper;
use PHPStan\File\SimpleRelativePathHelper;
use PHPStan\Internal\ComposerHelper;
use PHPStan\Internal\DirectoryCreator;
use PHPStan\Internal\DirectoryCreatorException;
use PHPStan\PhpDoc\StubFilesProvider;
use PHPStan\ShouldNotHappenException;
use ReflectionClass;
use _PHPStan_checksum\Symfony\Component\Console\Input\InputInterface;
use _PHPStan_checksum\Symfony\Component\Console\Output\ConsoleOutputInterface;
use _PHPStan_checksum\Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use function array_filter;
use function array_key_exists;
use function array_map;
use function array_values;
use function class_exists;
use function count;
use function dirname;
use function error_get_last;
use function get_class;
use function getcwd;
use function getenv;
use function gettype;
use function implode;
use function ini_get;
use function ini_set;
use function is_dir;
use function is_file;
use function is_readable;
use function is_string;
use function register_shutdown_function;
use function spl_autoload_functions;
use function sprintf;
use function str_contains;
use function str_repeat;
use function sys_get_temp_dir;
use const DIRECTORY_SEPARATOR;
use const E_ERROR;
use const PHP_VERSION_ID;
final class CommandHelper
{
    public const DEFAULT_LEVEL = '0';
    private static ?string $reservedMemory = null;
    /**
     * @param string[] $paths
     * @param string[] $composerAutoloaderProjectPaths
     *
     * @throws InceptionNotSuccessfulException
     */
    public static function begin(InputInterface $input, OutputInterface $output, array $paths, ?string $memoryLimit, ?string $autoloadFile, array $composerAutoloaderProjectPaths, ?string $projectConfigFile, ?string $generateBaselineFile, ?string $level, bool $allowXdebug, bool $debugEnabled, ?string $singleReflectionFile, ?string $singleReflectionInsteadOfFile, bool $cleanupContainerCache): \PHPStan\Command\InceptionResult
    {
        $stdOutput = new SymfonyOutput($output, new SymfonyStyle(new \PHPStan\Command\ErrorsConsoleStyle($input, $output)));
        $errorOutput = (static function () use ($input, $output): \PHPStan\Command\Output {
            $symfonyErrorOutput = $output instanceof ConsoleOutputInterface ? $output->getErrorOutput() : $output;
            return new SymfonyOutput($symfonyErrorOutput, new SymfonyStyle(new \PHPStan\Command\ErrorsConsoleStyle($input, $symfonyErrorOutput)));
        })();
        if (!$allowXdebug) {
            $xdebug = new XdebugHandler('phpstan');
            $xdebug->setPersistent();
            $xdebug->check();
            unset($xdebug);
        }
        if ($allowXdebug) {
            if (!XdebugHandler::isXdebugActive()) {
                $errorOutput->getStyle()->note('You are running with "--xdebug" enabled, but the Xdebug PHP extension is not active. The process will not halt at breakpoints.');
            } else {
                $errorOutput->getStyle()->note("You are running with \"--xdebug\" enabled, and the Xdebug PHP extension is active.\nThe process will halt at breakpoints, but PHPStan will run much slower.\nUse this only if you are debugging PHPStan itself or your custom extensions.");
            }
        } elseif (XdebugHandler::isXdebugActive()) {
            $errorOutput->getStyle()->note('The Xdebug PHP extension is active, but "--xdebug" is not used. This may slow down performance and the process will not halt at breakpoints.');
        } elseif ($debugEnabled) {
            $v = XdebugHandler::getSkippedVersion();
            if ($v !== '') {
                $errorOutput->getStyle()->note("The Xdebug PHP extension is active, but \"--xdebug\" is not used.\n" . "The process was restarted and it will not halt at breakpoints.\n" . 'Use "--xdebug" if you want to halt at breakpoints.');
            }
        }
        if ($memoryLimit !== null) {
            if (Strings::match($memoryLimit, '#^-?\d+[kMG]?$#i') === null) {
                $errorOutput->writeLineFormatted(sprintf('Invalid memory limit format "%s".', $memoryLimit));
                throw new \PHPStan\Command\InceptionNotSuccessfulException();
            }
            if (ini_set('memory_limit', $memoryLimit) === \false) {
                $errorOutput->writeLineFormatted(sprintf('Memory limit "%s" cannot be set.', $memoryLimit));
                throw new \PHPStan\Command\InceptionNotSuccessfulException();
            }
        }
        self::$reservedMemory = str_repeat('PHPStan', 1463);
        // reserve 10 kB of space
        register_shutdown_function(static function () use ($errorOutput): void {
            self::$reservedMemory = null;
            $error = error_get_last();
            if ($error === null) {
                return;
            }
            if ($error['type'] !== E_ERROR) {
                return;
            }
            if (!str_contains($error['message'], 'Allowed memory size')) {
                return;
            }
            $errorOutput->writeLineFormatted('');
            $errorOutput->writeLineFormatted(sprintf('<error>PHPStan process crashed because it reached configured PHP memory limit</error>: %s', ini_get('memory_limit')));
            $errorOutput->writeLineFormatted('Increase your memory limit in php.ini or run PHPStan with --memory-limit CLI option.');
        });
        $currentWorkingDirectory = getcwd();
        if ($currentWorkingDirectory === \false) {
            throw new ShouldNotHappenException();
        }
        $currentWorkingDirectoryFileHelper = new FileHelper($currentWorkingDirectory);
        $currentWorkingDirectory = $currentWorkingDirectoryFileHelper->getWorkingDirectory();
        /** @var list<callable(string): void>|false $autoloadFunctionsBefore */
        $autoloadFunctionsBefore = spl_autoload_functions();
        if ($autoloadFile !== null) {
            $autoloadFile = $currentWorkingDirectoryFileHelper->absolutizePath($autoloadFile);
            if (!is_file($autoloadFile)) {
                $errorOutput->writeLineFormatted(sprintf('Autoload file "%s" not found.', $autoloadFile));
                throw new \PHPStan\Command\InceptionNotSuccessfulException();
            }
            (static function (string $file): void {
                require_once $file;
            })($autoloadFile);
        }
        if ($projectConfigFile === null) {
            $discoverableConfigNames = ['.phpstan.neon', 'phpstan.neon', '.phpstan.neon.dist', 'phpstan.neon.dist', '.phpstan.dist.neon', 'phpstan.dist.neon'];
            foreach ($discoverableConfigNames as $discoverableConfigName) {
                $discoverableConfigFile = $currentWorkingDirectory . DIRECTORY_SEPARATOR . $discoverableConfigName;
                if (is_file($discoverableConfigFile)) {
                    $projectConfigFile = $discoverableConfigFile;
                    $errorOutput->writeLineFormatted(sprintf('Note: Using configuration file %s.', $projectConfigFile));
                    break;
                }
            }
        } else {
            $projectConfigFile = $currentWorkingDirectoryFileHelper->absolutizePath($projectConfigFile);
        }
        if ($generateBaselineFile !== null) {
            $generateBaselineFile = $currentWorkingDirectoryFileHelper->normalizePath($currentWorkingDirectoryFileHelper->absolutizePath($generateBaselineFile));
        }
        if ($singleReflectionFile !== null) {
            $singleReflectionFile = $currentWorkingDirectoryFileHelper->normalizePath($currentWorkingDirectoryFileHelper->absolutizePath($singleReflectionFile));
            if (!is_file($singleReflectionFile)) {
                $errorOutput->writeLineFormatted(sprintf('File passed to <fg=cyan>--tmp-file</> option does not exist: <error>%s</error>', $singleReflectionFile));
                throw new \PHPStan\Command\InceptionNotSuccessfulException();
            }
            if ($singleReflectionInsteadOfFile === null) {
                $errorOutput->writeLineFormatted('Both <fg=cyan>--tmp-file</> and <fg=cyan>--instead-of</> options must be passed at the same time for editor mode to work.');
                throw new \PHPStan\Command\InceptionNotSuccessfulException();
            }
        }
        if ($singleReflectionInsteadOfFile !== null) {
            $singleReflectionInsteadOfFile = $currentWorkingDirectoryFileHelper->normalizePath($currentWorkingDirectoryFileHelper->absolutizePath($singleReflectionInsteadOfFile));
            if (!is_file($singleReflectionInsteadOfFile)) {
                $errorOutput->writeLineFormatted(sprintf('File passed to <fg=cyan>--instead-of</> option does not exist: <error>%s</error>', $singleReflectionInsteadOfFile));
                throw new \PHPStan\Command\InceptionNotSuccessfulException();
            }
            if ($singleReflectionFile === null) {
                $errorOutput->writeLineFormatted('Both <fg=cyan>--tmp-file</> and <fg=cyan>--instead-of</> options must be passed at the same time for editor mode to work.');
                throw new \PHPStan\Command\InceptionNotSuccessfulException();
            }
        }
        $defaultLevelUsed = \false;
        if ($projectConfigFile === null && $level === null) {
            $level = self::DEFAULT_LEVEL;
            $defaultLevelUsed = \true;
        }
        $paths = array_map(static fn(string $path): string => $currentWorkingDirectoryFileHelper->normalizePath($currentWorkingDirectoryFileHelper->absolutizePath($path)), $paths);
        $analysedPathsFromConfig = [];
        $containerFactory = new ContainerFactory($currentWorkingDirectory);
        if ($cleanupContainerCache) {
            $containerFactory->setJournalContainer();
        }
        $projectConfig = null;
        if ($projectConfigFile !== null) {
            if (!is_file($projectConfigFile)) {
                $errorOutput->writeLineFormatted(sprintf('Project config file at path %s does not exist.', $projectConfigFile));
                throw new \PHPStan\Command\InceptionNotSuccessfulException();
            }
            $loader = (new LoaderFactory($currentWorkingDirectoryFileHelper, $containerFactory->getRootDirectory(), $containerFactory->getCurrentWorkingDirectory(), $generateBaselineFile, ['[parameters][paths][]', '[parameters][tmpDir]']))->createLoader();
            try {
                $projectConfig = $loader->load($projectConfigFile, null);
            } catch (InvalidStateException|FileNotFoundException $e) {
                $errorOutput->writeLineFormatted($e->getMessage());
                throw new \PHPStan\Command\InceptionNotSuccessfulException();
            }
            $defaultParameters = ['rootDir' => $containerFactory->getRootDirectory(), 'currentWorkingDirectory' => $containerFactory->getCurrentWorkingDirectory(), 'env' => getenv()];
            if (isset($projectConfig['parameters']['tmpDir'])) {
                $tmpDir = Helpers::expand($projectConfig['parameters']['tmpDir'], $defaultParameters);
            }
            if ($level === null && isset($projectConfig['parameters']['level'])) {
                $level = (string) $projectConfig['parameters']['level'];
            }
            if (isset($projectConfig['parameters']['paths'])) {
                $analysedPathsFromConfig = Helpers::expand($projectConfig['parameters']['paths'], $defaultParameters);
            }
            if (count($paths) === 0) {
                $paths = $analysedPathsFromConfig;
            }
        }
        $additionalConfigFiles = [];
        if ($level !== null) {
            $levelConfigFile = sprintf('%s/config.level%s.neon', $containerFactory->getConfigDirectory(), $level);
            if (!is_file($levelConfigFile)) {
                $errorOutput->writeLineFormatted(sprintf('Level config file %s was not found.', $levelConfigFile));
                throw new \PHPStan\Command\InceptionNotSuccessfulException();
            }
            $additionalConfigFiles[] = $levelConfigFile;
        }
        if (class_exists('PHPStan\ExtensionInstaller\GeneratedConfig')) {
            $generatedConfigReflection = new ReflectionClass('PHPStan\ExtensionInstaller\GeneratedConfig');
            $generatedConfigDirectory = dirname($generatedConfigReflection->getFileName());
            foreach (GeneratedConfig::EXTENSIONS as $name => $extensionConfig) {
                foreach ($extensionConfig['extra']['includes'] ?? [] as $includedFile) {
                    if (!is_string($includedFile)) {
                        $errorOutput->writeLineFormatted(sprintf('Cannot include config from package %s, expecting string file path but got %s', $name, gettype($includedFile)));
                        throw new \PHPStan\Command\InceptionNotSuccessfulException();
                    }
                    $includedFilePath = null;
                    if (isset($extensionConfig['relative_install_path'])) {
                        $includedFilePath = sprintf('%s/%s/%s', $generatedConfigDirectory, $extensionConfig['relative_install_path'], $includedFile);
                        if (!is_file($includedFilePath) || !is_readable($includedFilePath)) {
                            $includedFilePath = null;
                        }
                    }
                    if ($includedFilePath === null) {
                        $includedFilePath = sprintf('%s/%s', $extensionConfig['install_path'], $includedFile);
                    }
                    if (!is_file($includedFilePath) || !is_readable($includedFilePath)) {
                        $errorOutput->writeLineFormatted(sprintf('Config file %s does not exist or isn\'t readable', $includedFilePath));
                        throw new \PHPStan\Command\InceptionNotSuccessfulException();
                    }
                    $additionalConfigFiles[] = $includedFilePath;
                }
            }
            if (count($additionalConfigFiles) > 0 && $generatedConfigReflection->hasConstant('PHPSTAN_VERSION_CONSTRAINT')) {
                $generatedConfigPhpStanVersionConstraint = $generatedConfigReflection->getConstant('PHPSTAN_VERSION_CONSTRAINT');
                if ($generatedConfigPhpStanVersionConstraint !== null) {
                    $phpstanSemverVersion = ComposerHelper::getPhpStanVersion();
                    if ($phpstanSemverVersion !== ComposerHelper::UNKNOWN_VERSION && !str_contains($phpstanSemverVersion, '@') && !Semver::satisfies($phpstanSemverVersion, $generatedConfigPhpStanVersionConstraint)) {
                        $errorOutput->writeLineFormatted('<error>Running PHPStan with incompatible extensions</error>');
                        $errorOutput->writeLineFormatted('You\'re running PHPStan from a different Composer project');
                        $errorOutput->writeLineFormatted('than the one where you installed extensions.');
                        $errorOutput->writeLineFormatted('');
                        $errorOutput->writeLineFormatted(sprintf('Your PHPStan version is: <fg=red>%s</>', $phpstanSemverVersion));
                        $errorOutput->writeLineFormatted(sprintf('Installed PHPStan extensions support: %s', $generatedConfigPhpStanVersionConstraint));
                        $errorOutput->writeLineFormatted('');
                        if (isset($_SERVER['argv'][0]) && is_file($_SERVER['argv'][0])) {
                            $mainScript = $_SERVER['argv'][0];
                            $errorOutput->writeLineFormatted(sprintf('PHPStan is running from: %s', $currentWorkingDirectoryFileHelper->absolutizePath(dirname($mainScript))));
                        }
                        $errorOutput->writeLineFormatted(sprintf('Extensions were installed in: %s', dirname($generatedConfigDirectory, 3)));
                        $errorOutput->writeLineFormatted('');
                        $simpleRelativePathHelper = new SimpleRelativePathHelper($currentWorkingDirectory);
                        $errorOutput->writeLineFormatted(sprintf('Run PHPStan with <fg=green>%s</> to fix this problem.', $simpleRelativePathHelper->getRelativePath(dirname($generatedConfigDirectory, 3) . '/bin/phpstan')));
                        $errorOutput->writeLineFormatted('');
                        throw new \PHPStan\Command\InceptionNotSuccessfulException();
                    }
                }
            }
        }
        if ($projectConfigFile !== null && $currentWorkingDirectoryFileHelper->normalizePath($projectConfigFile, '/') !== $currentWorkingDirectoryFileHelper->normalizePath(__DIR__ . '/../../conf/config.stubFiles.neon', '/')) {
            $additionalConfigFiles[] = $projectConfigFile;
        }
        $createDir = static function (string $path) use ($errorOutput): void {
            try {
                DirectoryCreator::ensureDirectoryExists($path, 0777);
            } catch (DirectoryCreatorException $e) {
                $errorOutput->writeLineFormatted($e->getMessage());
                throw new \PHPStan\Command\InceptionNotSuccessfulException();
            }
        };
        if (!isset($tmpDir)) {
            $tmpDir = sys_get_temp_dir() . '/phpstan';
            $createDir($tmpDir);
        }
        try {
            $container = $containerFactory->create($tmpDir, $additionalConfigFiles, $paths, $composerAutoloaderProjectPaths, $analysedPathsFromConfig, $level ?? self::DEFAULT_LEVEL, $generateBaselineFile, $autoloadFile, $singleReflectionFile, $singleReflectionInsteadOfFile);
        } catch (InvalidConfigurationException|AssertionException $e) {
            $errorOutput->writeLineFormatted('<error>Invalid configuration:</error>');
            $errorOutput->writeLineFormatted($e->getMessage());
            throw new \PHPStan\Command\InceptionNotSuccessfulException();
        } catch (InvalidIgnoredErrorPatternsException $e) {
            $errorOutput->writeLineFormatted(sprintf('<error>Invalid %s in ignoreErrors:</error>', count($e->getErrors()) === 1 ? 'entry' : 'entries'));
            foreach ($e->getErrors() as $error) {
                $errorOutput->writeLineFormatted($error);
                $errorOutput->writeLineFormatted('');
            }
            $errorOutput->writeLineFormatted('To ignore non-existent paths in ignoreErrors,');
            $errorOutput->writeLineFormatted('set <fg=cyan>reportUnmatchedIgnoredErrors: false</> in your configuration file.');
            $errorOutput->writeLineFormatted('');
            throw new \PHPStan\Command\InceptionNotSuccessfulException();
        } catch (MissingImplementedInterfaceInServiceWithTagException $e) {
            $errorOutput->writeLineFormatted('<error>Invalid service:</error>');
            $errorOutput->writeLineFormatted($e->getMessage());
            $errorOutput->writeLineFormatted('');
            throw new \PHPStan\Command\InceptionNotSuccessfulException();
        } catch (InvalidExcludePathsException $e) {
            $errorOutput->writeLineFormatted(sprintf('<error>Invalid %s in excludePaths:</error>', count($e->getErrors()) === 1 ? 'entry' : 'entries'));
            foreach ($e->getErrors() as $error) {
                $errorOutput->writeLineFormatted($error);
                $errorOutput->writeLineFormatted('');
            }
            $suggestOptional = $e->getSuggestOptional();
            if (count($suggestOptional) > 0) {
                $baselinePathHelper = null;
                if ($projectConfigFile !== null) {
                    $baselinePathHelper = new ParentDirectoryRelativePathHelper(dirname($projectConfigFile));
                }
                $errorOutput->writeLineFormatted('If the excluded path can sometimes exist, append <fg=cyan>(?)</>');
                $errorOutput->writeLineFormatted('to its config entry to mark it as optional. Example:');
                $errorOutput->writeLineFormatted('');
                $errorOutput->writeLineFormatted('<fg=cyan>parameters:</>');
                $errorOutput->writeLineFormatted("\t<fg=cyan>excludePaths:</>");
                foreach ($suggestOptional as $key => $suggestOptionalPaths) {
                    $errorOutput->writeLineFormatted(sprintf("\t\t<fg=cyan>%s:</>", $key));
                    foreach ($suggestOptionalPaths as $suggestOptionalPath) {
                        if ($baselinePathHelper === null) {
                            $errorOutput->writeLineFormatted(sprintf("\t\t\t- <fg=cyan>%s (?)</>", $suggestOptionalPath));
                            continue;
                        }
                        $errorOutput->writeLineFormatted(sprintf("\t\t\t- <fg=cyan>%s (?)</>", $baselinePathHelper->getRelativePath($suggestOptionalPath)));
                    }
                }
                $errorOutput->writeLineFormatted('');
            }
            throw new \PHPStan\Command\InceptionNotSuccessfulException();
        } catch (ValidationException $e) {
            foreach ($e->getMessages() as $message) {
                $errorOutput->writeLineFormatted('<error>Invalid configuration:</error>');
                $errorOutput->writeLineFormatted($message);
            }
            throw new \PHPStan\Command\InceptionNotSuccessfulException();
        } catch (ServiceCreationException $e) {
            $matches = Strings::match($e->getMessage(), '#Service of type (?<serviceType>[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\\\\]*[a-zA-Z0-9_\x7f-\xff]): Service of type (?<parserServiceType>[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\\\\]*[a-zA-Z0-9_\x7f-\xff]) needed by \$(?<parameterName>[a-zA-Z_\x7f-\xff][a-zA-Z_0-9\x7f-\xff]*) in (?<methodName>[a-zA-Z_\x7f-\xff][a-zA-Z_0-9\x7f-\xff]*)\(\)#');
            if ($matches === null) {
                throw $e;
            }
            if ($matches['parserServiceType'] !== 'PHPStan\Parser\Parser') {
                throw $e;
            }
            if ($matches['methodName'] !== '__construct') {
                throw $e;
            }
            $errorOutput->writeLineFormatted('<error>Invalid configuration:</error>');
            $errorOutput->writeLineFormatted(sprintf("Service of type <fg=cyan>%s</> is no longer autowired.\n", $matches['parserServiceType']));
            $errorOutput->writeLineFormatted('You need to choose one of the following services');
            $errorOutput->writeLineFormatted(sprintf('and use it in the %s argument of your service <fg=cyan>%s</>:', $matches['parameterName'], $matches['serviceType']));
            $errorOutput->writeLineFormatted('* <fg=cyan>defaultAnalysisParser</> (if you\'re parsing files from analysed paths)');
            $errorOutput->writeLineFormatted('* <fg=cyan>currentPhpVersionSimpleDirectParser</> (in most other situations)');
            $errorOutput->writeLineFormatted('');
            $errorOutput->writeLineFormatted('After fixing this problem, your configuration will look something like this:');
            $errorOutput->writeLineFormatted('');
            $errorOutput->writeLineFormatted('-');
            $errorOutput->writeLineFormatted(sprintf("\tclass: %s", $matches['serviceType']));
            $errorOutput->writeLineFormatted(sprintf("\targuments:"));
            $errorOutput->writeLineFormatted(sprintf("\t\t%s: @defaultAnalysisParser", $matches['parameterName']));
            $errorOutput->writeLineFormatted('');
            throw new \PHPStan\Command\InceptionNotSuccessfulException();
        } catch (DuplicateIncludedFilesException $e) {
            $format = "<error>These files are included multiple times:</error>\n- %s";
            if (count($e->getFiles()) === 1) {
                $format = "<error>This file is included multiple times:</error>\n- %s";
            }
            $errorOutput->writeLineFormatted(sprintf($format, implode("\n- ", $e->getFiles())));
            if (class_exists('PHPStan\ExtensionInstaller\GeneratedConfig')) {
                $errorOutput->writeLineFormatted('');
                $errorOutput->writeLineFormatted('It can lead to unexpected results. If you\'re using phpstan/extension-installer, make sure you have removed corresponding neon files from your project config file.');
            }
            throw new \PHPStan\Command\InceptionNotSuccessfulException();
        }
        if ($cleanupContainerCache) {
            $cacheStorage = $container->getService('cacheStorage');
            if ($cacheStorage instanceof FileCacheStorage) {
                $cacheStorage->clearUnusedFiles();
            }
        }
        /** @var bool|null $customRulesetUsed */
        $customRulesetUsed = $container->getParameter('customRulesetUsed');
        if ($customRulesetUsed === null) {
            $errorOutput->writeLineFormatted('');
            $errorOutput->writeLineFormatted('<comment>No rules detected</comment>');
            $errorOutput->writeLineFormatted('');
            $errorOutput->writeLineFormatted('You have the following choices:');
            $errorOutput->writeLineFormatted('');
            $errorOutput->writeLineFormatted('* while running the analyse option, use the <info>--level</info> option to adjust your rule level - the higher the stricter');
            $errorOutput->writeLineFormatted('');
            $errorOutput->writeLineFormatted(sprintf('* create your own <info>custom ruleset</info> by selecting which rules you want to check by copying the service definitions from the built-in config level files in <options=bold>%s</>.', $currentWorkingDirectoryFileHelper->normalizePath(__DIR__ . '/../../conf')));
            $errorOutput->writeLineFormatted('  * in this case, don\'t forget to define parameter <options=bold>customRulesetUsed</> in your config file.');
            $errorOutput->writeLineFormatted('');
            throw new \PHPStan\Command\InceptionNotSuccessfulException();
        } elseif ($customRulesetUsed) {
            $defaultLevelUsed = \false;
        }
        foreach ($container->getParameter('bootstrapFiles') as $bootstrapFileFromArray) {
            self::executeBootstrapFile($bootstrapFileFromArray, $container, $errorOutput, $debugEnabled);
        }
        /** @var list<callable(string): void>|false $autoloadFunctionsAfter */
        $autoloadFunctionsAfter = spl_autoload_functions();
        if ($autoloadFunctionsBefore !== \false && $autoloadFunctionsAfter !== \false) {
            $newAutoloadFunctions = $GLOBALS['__phpstanAutoloadFunctions'] ?? [];
            foreach ($autoloadFunctionsAfter as $after) {
                foreach ($autoloadFunctionsBefore as $before) {
                    if ($after === $before) {
                        continue 2;
                    }
                }
                $newAutoloadFunctions[] = $after;
            }
            $GLOBALS['__phpstanAutoloadFunctions'] = $newAutoloadFunctions;
        }
        if (PHP_VERSION_ID >= 80000) {
            require_once __DIR__ . '/../../stubs/runtime/Enum/UnitEnum.php';
            require_once __DIR__ . '/../../stubs/runtime/Enum/BackedEnum.php';
            require_once __DIR__ . '/../../stubs/runtime/Enum/ReflectionEnum.php';
            require_once __DIR__ . '/../../stubs/runtime/Enum/ReflectionEnumUnitCase.php';
            require_once __DIR__ . '/../../stubs/runtime/Enum/ReflectionEnumBackedCase.php';
        }
        foreach ($container->getParameter('scanFiles') as $scannedFile) {
            if (is_file($scannedFile)) {
                continue;
            }
            $errorOutput->writeLineFormatted(sprintf('Scanned file %s does not exist.', $scannedFile));
            throw new \PHPStan\Command\InceptionNotSuccessfulException();
        }
        foreach ($container->getParameter('scanDirectories') as $scannedDirectory) {
            if (is_dir($scannedDirectory)) {
                continue;
            }
            $errorOutput->writeLineFormatted(sprintf('Scanned directory %s does not exist.', $scannedDirectory));
            throw new \PHPStan\Command\InceptionNotSuccessfulException();
        }
        $alreadyAddedStubFiles = [];
        foreach ($container->getParameter('stubFiles') as $stubFile) {
            if (array_key_exists($stubFile, $alreadyAddedStubFiles)) {
                $errorOutput->writeLineFormatted(sprintf('Stub file %s is added multiple times.', $stubFile));
                throw new \PHPStan\Command\InceptionNotSuccessfulException();
            }
            $alreadyAddedStubFiles[$stubFile] = \true;
            if (is_file($stubFile)) {
                continue;
            }
            $errorOutput->writeLineFormatted(sprintf('Stub file %s does not exist.', $stubFile));
            throw new \PHPStan\Command\InceptionNotSuccessfulException();
        }
        /** @var FileFinder $fileFinder */
        $fileFinder = $container->getService('fileFinderAnalyse');
        $pathRoutingParser = $container->getService('pathRoutingParser');
        $stubFilesProvider = $container->getByType(StubFilesProvider::class);
        $filesCallback = static function () use ($currentWorkingDirectoryFileHelper, $stubFilesProvider, $fileFinder, $pathRoutingParser, $paths, $errorOutput): array {
            if (count($paths) === 0) {
                $errorOutput->writeLineFormatted('At least one path must be specified to analyse.');
                throw new \PHPStan\Command\InceptionNotSuccessfulException();
            }
            $fileFinderResult = $fileFinder->findFiles($paths);
            $files = $fileFinderResult->getFiles();
            $pathRoutingParser->setAnalysedFiles($files);
            $stubFilesExcluder = new FileExcluder($currentWorkingDirectoryFileHelper, $stubFilesProvider->getProjectStubFiles());
            $files = array_values(array_filter($files, static fn(string $file) => !$stubFilesExcluder->isExcludedFromAnalysing($file)));
            return [$files, $fileFinderResult->isOnlyFiles()];
        };
        return new \PHPStan\Command\InceptionResult($filesCallback, $stdOutput, $errorOutput, $container, $defaultLevelUsed, $projectConfigFile, $projectConfig, $generateBaselineFile, $singleReflectionFile, $singleReflectionInsteadOfFile);
    }
    /**
     * @throws InceptionNotSuccessfulException
     */
    private static function executeBootstrapFile(string $file, Container $container, \PHPStan\Command\Output $errorOutput, bool $debugEnabled): void
    {
        if (!is_file($file)) {
            $errorOutput->writeLineFormatted(sprintf('Bootstrap file %s does not exist.', $file));
            throw new \PHPStan\Command\InceptionNotSuccessfulException();
        }
        try {
            (static function (string $file) use ($container): void {
                require_once $file;
            })($file);
        } catch (Throwable $e) {
            $errorOutput->writeLineFormatted(sprintf('%s thrown in %s on line %d while loading bootstrap file %s: %s', get_class($e), $e->getFile(), $e->getLine(), $file, $e->getMessage()));
            if ($debugEnabled) {
                $errorOutput->writeLineFormatted($e->getTraceAsString());
            }
            throw new \PHPStan\Command\InceptionNotSuccessfulException();
        }
    }
}
