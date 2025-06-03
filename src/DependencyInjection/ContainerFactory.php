<?php

declare (strict_types=1);
namespace PHPStan\DependencyInjection;

use _PHPStan_checksum\Nette\Bootstrap\Extensions\PhpExtension;
use _PHPStan_checksum\Nette\DI\Config\Adapters\PhpAdapter;
use _PHPStan_checksum\Nette\DI\Definitions\Statement;
use _PHPStan_checksum\Nette\DI\Extensions\ExtensionsExtension;
use _PHPStan_checksum\Nette\DI\Helpers;
use _PHPStan_checksum\Nette\Schema\Context as SchemaContext;
use _PHPStan_checksum\Nette\Schema\Elements\AnyOf;
use _PHPStan_checksum\Nette\Schema\Elements\Structure;
use _PHPStan_checksum\Nette\Schema\Elements\Type;
use _PHPStan_checksum\Nette\Schema\Expect;
use _PHPStan_checksum\Nette\Schema\Processor;
use _PHPStan_checksum\Nette\Schema\Schema;
use _PHPStan_checksum\Nette\Utils\Strings;
use _PHPStan_checksum\Nette\Utils\Validators;
use Phar;
use PhpParser\Parser;
use PHPStan\BetterReflection\BetterReflection;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\SourceStubber\PhpStormStubsSourceStubber;
use PHPStan\BetterReflection\SourceLocator\Type\SourceLocator;
use PHPStan\Command\CommandHelper;
use PHPStan\File\FileHelper;
use PHPStan\Node\Printer\Printer;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\PhpVersionStaticAccessor;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ObjectType;
use function array_diff_key;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_unique;
use function count;
use function dirname;
use function extension_loaded;
use function getenv;
use function ini_get;
use function is_array;
use function is_file;
use function is_readable;
use function spl_object_id;
use function sprintf;
use function str_ends_with;
use function substr;
/**
 * @api
 */
final class ContainerFactory
{
    private string $currentWorkingDirectory;
    private FileHelper $fileHelper;
    private string $rootDirectory;
    private string $configDirectory;
    private static ?int $lastInitializedContainerId = null;
    private bool $journalContainer = \false;
    /** @api */
    public function __construct(string $currentWorkingDirectory)
    {
        $this->currentWorkingDirectory = $currentWorkingDirectory;
        $this->fileHelper = new FileHelper($currentWorkingDirectory);
        $rootDir = __DIR__ . '/../..';
        $originalRootDir = $this->fileHelper->normalizePath($rootDir);
        if (extension_loaded('phar')) {
            $pharPath = Phar::running(\false);
            if ($pharPath !== '') {
                $rootDir = dirname($pharPath);
            }
        }
        $this->rootDirectory = $this->fileHelper->normalizePath($rootDir);
        $this->configDirectory = $originalRootDir . '/conf';
    }
    public function setJournalContainer() : void
    {
        $this->journalContainer = \true;
    }
    /**
     * @param string[] $additionalConfigFiles
     * @param string[] $analysedPaths
     * @param string[] $composerAutoloaderProjectPaths
     * @param string[] $analysedPathsFromConfig
     */
    public function create(string $tempDirectory, array $additionalConfigFiles, array $analysedPaths, array $composerAutoloaderProjectPaths = [], array $analysedPathsFromConfig = [], string $usedLevel = CommandHelper::DEFAULT_LEVEL, ?string $generateBaselineFile = null, ?string $cliAutoloadFile = null, ?string $singleReflectionFile = null, ?string $singleReflectionInsteadOfFile = null) : \PHPStan\DependencyInjection\Container
    {
        [$allConfigFiles, $projectConfig] = $this->detectDuplicateIncludedFiles(array_merge([__DIR__ . '/../../conf/parametersSchema.neon'], $additionalConfigFiles), ['rootDir' => $this->rootDirectory, 'currentWorkingDirectory' => $this->currentWorkingDirectory, 'env' => getenv()]);
        $configurator = new \PHPStan\DependencyInjection\Configurator(new \PHPStan\DependencyInjection\LoaderFactory($this->fileHelper, $this->rootDirectory, $this->currentWorkingDirectory, $generateBaselineFile, $projectConfig['expandRelativePaths']), $this->journalContainer);
        $configurator->defaultExtensions = ['php' => PhpExtension::class, 'extensions' => ExtensionsExtension::class];
        $configurator->setDebugMode(\true);
        $configurator->setTempDirectory($tempDirectory);
        $configurator->addParameters(['rootDir' => $this->rootDirectory, 'currentWorkingDirectory' => $this->currentWorkingDirectory, 'cliArgumentsVariablesRegistered' => ini_get('register_argc_argv') === '1', 'tmpDir' => $tempDirectory, 'additionalConfigFiles' => $additionalConfigFiles, 'allConfigFiles' => $allConfigFiles, 'composerAutoloaderProjectPaths' => $composerAutoloaderProjectPaths, 'generateBaselineFile' => $generateBaselineFile, 'usedLevel' => $usedLevel, 'cliAutoloadFile' => $cliAutoloadFile, 'env' => getenv()]);
        $configurator->addDynamicParameters(['singleReflectionFile' => $singleReflectionFile, 'singleReflectionInsteadOfFile' => $singleReflectionInsteadOfFile, 'analysedPaths' => $analysedPaths, 'analysedPathsFromConfig' => $analysedPathsFromConfig]);
        $configurator->addConfig($this->configDirectory . '/config.neon');
        foreach ($additionalConfigFiles as $additionalConfigFile) {
            $configurator->addConfig($additionalConfigFile);
        }
        $configurator->setAllConfigFiles($allConfigFiles);
        $container = $configurator->createContainer()->getByType(\PHPStan\DependencyInjection\Container::class);
        $this->validateParameters($container->getParameters(), $projectConfig['parametersSchema']);
        self::postInitializeContainer($container);
        return $container;
    }
    /** @internal */
    public static function postInitializeContainer(\PHPStan\DependencyInjection\Container $container) : void
    {
        $containerId = spl_object_id($container);
        if ($containerId === self::$lastInitializedContainerId) {
            return;
        }
        self::$lastInitializedContainerId = $containerId;
        /** @var SourceLocator $sourceLocator */
        $sourceLocator = $container->getService('betterReflectionSourceLocator');
        /** @var Reflector $reflector */
        $reflector = $container->getService('betterReflectionReflector');
        /** @var Parser $phpParser */
        $phpParser = $container->getService('phpParserDecorator');
        BetterReflection::populate($container->getByType(PhpVersion::class)->getVersionId(), $sourceLocator, $reflector, $phpParser, $container->getByType(PhpStormStubsSourceStubber::class), $container->getByType(Printer::class));
        ReflectionProviderStaticAccessor::registerInstance($container->getByType(ReflectionProvider::class));
        PhpVersionStaticAccessor::registerInstance($container->getByType(PhpVersion::class));
        ObjectType::resetCaches();
        $container->getService('typeSpecifier');
        \PHPStan\DependencyInjection\BleedingEdgeToggle::setBleedingEdge($container->getParameter('featureToggles')['bleedingEdge']);
    }
    public function getCurrentWorkingDirectory() : string
    {
        return $this->currentWorkingDirectory;
    }
    public function getRootDirectory() : string
    {
        return $this->rootDirectory;
    }
    public function getConfigDirectory() : string
    {
        return $this->configDirectory;
    }
    /**
     * @param string[] $configFiles
     * @param array<string, mixed> $loaderParameters
     * @return array{list<string>, array<mixed>}
     * @throws DuplicateIncludedFilesException
     */
    private function detectDuplicateIncludedFiles(array $configFiles, array $loaderParameters) : array
    {
        $neonAdapter = new \PHPStan\DependencyInjection\NeonAdapter([]);
        $phpAdapter = new PhpAdapter();
        $allConfigFiles = [];
        $configArray = [];
        foreach ($configFiles as $configFile) {
            [$tmpConfigFiles, $tmpConfigArray] = self::getConfigFiles($this->fileHelper, $neonAdapter, $phpAdapter, $configFile, $loaderParameters, null);
            $allConfigFiles = array_merge($allConfigFiles, $tmpConfigFiles);
            /** @var array<mixed> $configArray */
            $configArray = \_PHPStan_checksum\Nette\Schema\Helpers::merge($tmpConfigArray, $configArray);
        }
        $normalized = array_map(fn(string $file): string => $this->fileHelper->normalizePath($file), $allConfigFiles);
        $deduplicated = array_unique($normalized);
        if (count($normalized) <= count($deduplicated)) {
            return [$normalized, $configArray];
        }
        $duplicateFiles = array_unique(array_diff_key($normalized, $deduplicated));
        throw new \PHPStan\DependencyInjection\DuplicateIncludedFilesException($duplicateFiles);
    }
    /**
     * @param array<string, string> $loaderParameters
     * @return array{list<string>, array<mixed>}
     */
    private static function getConfigFiles(FileHelper $fileHelper, \PHPStan\DependencyInjection\NeonAdapter $neonAdapter, PhpAdapter $phpAdapter, string $configFile, array $loaderParameters, ?string $generateBaselineFile) : array
    {
        if ($generateBaselineFile === $fileHelper->normalizePath($configFile)) {
            return [[], []];
        }
        if (!is_file($configFile) || !is_readable($configFile)) {
            return [[], []];
        }
        if (str_ends_with($configFile, '.php')) {
            $data = $phpAdapter->load($configFile);
        } else {
            $data = $neonAdapter->load($configFile);
        }
        $allConfigFiles = [$configFile];
        if (isset($data['includes'])) {
            Validators::assert($data['includes'], 'list', sprintf("section 'includes' in file '%s'", $configFile));
            $includes = Helpers::expand($data['includes'], $loaderParameters);
            foreach ($includes as $include) {
                $include = self::expandIncludedFile($include, $configFile);
                [$tmpConfigFiles, $tmpConfigArray] = self::getConfigFiles($fileHelper, $neonAdapter, $phpAdapter, $include, $loaderParameters, $generateBaselineFile);
                $allConfigFiles = array_merge($allConfigFiles, $tmpConfigFiles);
                /** @var array<mixed> $data */
                $data = \_PHPStan_checksum\Nette\Schema\Helpers::merge($tmpConfigArray, $data);
            }
        }
        return [$allConfigFiles, $data];
    }
    private static function expandIncludedFile(string $includedFile, string $mainFile) : string
    {
        return Strings::match($includedFile, '#([a-z]+:)?[/\\\\]#Ai') !== null ? $includedFile : dirname($mainFile) . '/' . $includedFile;
    }
    /**
     * @param array<mixed> $parameters
     * @param array<mixed> $parametersSchema
     */
    private function validateParameters(array $parameters, array $parametersSchema) : void
    {
        if (!(bool) $parameters['__validate']) {
            return;
        }
        $schema = $this->processArgument(new Statement('schema', [new Statement('structure', [$parametersSchema])]));
        $processor = new Processor();
        $processor->onNewContext[] = static function (SchemaContext $context) : void {
            $context->path = ['parameters'];
        };
        $processor->process($schema, $parameters);
        if (!array_key_exists('phpVersion', $parameters) || !is_array($parameters['phpVersion'])) {
            return;
        }
        $phpVersion = $parameters['phpVersion'];
        if ($phpVersion['max'] < $phpVersion['min']) {
            throw new \PHPStan\DependencyInjection\InvalidPhpVersionException('Invalid PHP version range: phpVersion.max should be greater or equal to phpVersion.min.');
        }
    }
    /**
     * @param Statement[] $statements
     */
    private function processSchema(array $statements, bool $required = \true) : Schema
    {
        if (count($statements) === 0) {
            throw new ShouldNotHappenException();
        }
        $parameterSchema = null;
        foreach ($statements as $statement) {
            $processedArguments = array_map(fn($argument) => $this->processArgument($argument), $statement->arguments);
            if ($parameterSchema === null) {
                /** @var Type|AnyOf|Structure $parameterSchema */
                $parameterSchema = Expect::{$statement->getEntity()}(...$processedArguments);
            } else {
                $parameterSchema->{$statement->getEntity()}(...$processedArguments);
            }
        }
        if ($required) {
            $parameterSchema->required();
        }
        return $parameterSchema;
    }
    /**
     * @param mixed $argument
     * @return mixed
     */
    private function processArgument($argument, bool $required = \true)
    {
        if ($argument instanceof Statement) {
            if ($argument->entity === 'schema') {
                $arguments = [];
                foreach ($argument->arguments as $schemaArgument) {
                    if (!$schemaArgument instanceof Statement) {
                        throw new ShouldNotHappenException('schema() should contain another statement().');
                    }
                    $arguments[] = $schemaArgument;
                }
                if (count($arguments) === 0) {
                    throw new ShouldNotHappenException('schema() should have at least one argument.');
                }
                return $this->processSchema($arguments, $required);
            }
            return $this->processSchema([$argument], $required);
        } elseif (is_array($argument)) {
            $processedArray = [];
            foreach ($argument as $key => $val) {
                $required = $key[0] !== '?';
                $key = $required ? $key : substr($key, 1);
                $processedArray[$key] = $this->processArgument($val, $required);
            }
            return $processedArray;
        }
        return $argument;
    }
}
