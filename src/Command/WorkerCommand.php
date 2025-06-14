<?php

declare (strict_types=1);
namespace PHPStan\Command;

use _PHPStan_checksum\Clue\React\NDJson\Decoder;
use _PHPStan_checksum\Clue\React\NDJson\Encoder;
use PHPStan\Analyser\FileAnalyser;
use PHPStan\Analyser\InternalError;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Collectors\Registry as CollectorRegistry;
use PHPStan\DependencyInjection\Container;
use PHPStan\File\PathNotFoundException;
use PHPStan\Rules\Registry as RuleRegistry;
use PHPStan\ShouldNotHappenException;
use _PHPStan_checksum\React\EventLoop\StreamSelectLoop;
use _PHPStan_checksum\React\Socket\ConnectionInterface;
use _PHPStan_checksum\React\Socket\TcpConnector;
use _PHPStan_checksum\React\Stream\ReadableStreamInterface;
use _PHPStan_checksum\React\Stream\WritableStreamInterface;
use _PHPStan_checksum\Symfony\Component\Console\Command\Command;
use _PHPStan_checksum\Symfony\Component\Console\Input\InputArgument;
use _PHPStan_checksum\Symfony\Component\Console\Input\InputInterface;
use _PHPStan_checksum\Symfony\Component\Console\Input\InputOption;
use _PHPStan_checksum\Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use function array_fill_keys;
use function array_filter;
use function array_merge;
use function array_unshift;
use function array_values;
use function defined;
use function is_array;
use function is_bool;
use function is_string;
use function memory_get_peak_usage;
use function sprintf;
final class WorkerCommand extends Command
{
    /**
     * @var string[]
     */
    private array $composerAutoloaderProjectPaths;
    private const NAME = 'worker';
    private int $errorCount = 0;
    /**
     * @param string[] $composerAutoloaderProjectPaths
     */
    public function __construct(array $composerAutoloaderProjectPaths)
    {
        $this->composerAutoloaderProjectPaths = $composerAutoloaderProjectPaths;
        parent::__construct();
    }
    protected function configure(): void
    {
        $this->setName(self::NAME)->setDescription('(Internal) Support for parallel analysis.')->setDefinition([new InputArgument('paths', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Paths with source code to run analysis on'), new InputOption('configuration', 'c', InputOption::VALUE_REQUIRED, 'Path to project configuration file'), new InputOption(\PHPStan\Command\AnalyseCommand::OPTION_LEVEL, 'l', InputOption::VALUE_REQUIRED, 'Level of rule options - the higher the stricter'), new InputOption('autoload-file', 'a', InputOption::VALUE_REQUIRED, 'Project\'s additional autoload file path'), new InputOption('memory-limit', null, InputOption::VALUE_REQUIRED, 'Memory limit for analysis'), new InputOption('xdebug', null, InputOption::VALUE_NONE, 'Allow running with Xdebug for debugging purposes'), new InputOption('port', null, InputOption::VALUE_REQUIRED), new InputOption('identifier', null, InputOption::VALUE_REQUIRED), new InputOption('tmp-file', null, InputOption::VALUE_REQUIRED), new InputOption('instead-of', null, InputOption::VALUE_REQUIRED)])->setHidden(\true);
    }
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $paths = $input->getArgument('paths');
        $memoryLimit = $input->getOption('memory-limit');
        $autoloadFile = $input->getOption('autoload-file');
        $configuration = $input->getOption('configuration');
        $level = $input->getOption(\PHPStan\Command\AnalyseCommand::OPTION_LEVEL);
        $allowXdebug = $input->getOption('xdebug');
        $port = $input->getOption('port');
        $identifier = $input->getOption('identifier');
        $tmpFile = $input->getOption('tmp-file');
        $insteadOfFile = $input->getOption('instead-of');
        if (!is_array($paths) || !is_string($memoryLimit) && $memoryLimit !== null || !is_string($autoloadFile) && $autoloadFile !== null || !is_string($configuration) && $configuration !== null || !is_string($level) && $level !== null || !is_bool($allowXdebug) || !is_string($port) || !is_string($identifier) || !is_string($tmpFile) && $tmpFile !== null || !is_string($insteadOfFile) && $insteadOfFile !== null) {
            throw new ShouldNotHappenException();
        }
        try {
            $inceptionResult = \PHPStan\Command\CommandHelper::begin($input, $output, $paths, $memoryLimit, $autoloadFile, $this->composerAutoloaderProjectPaths, $configuration, null, $level, $allowXdebug, \false, $tmpFile, $insteadOfFile, \false);
        } catch (\PHPStan\Command\InceptionNotSuccessfulException $e) {
            return 1;
        }
        $loop = new StreamSelectLoop();
        $container = $inceptionResult->getContainer();
        try {
            [$analysedFiles] = $inceptionResult->getFiles();
            $analysedFiles = $this->switchTmpFile($analysedFiles, $insteadOfFile, $tmpFile);
        } catch (PathNotFoundException $e) {
            $inceptionResult->getErrorOutput()->writeLineFormatted(sprintf('<error>%s</error>', $e->getMessage()));
            return 1;
        } catch (\PHPStan\Command\InceptionNotSuccessfulException $e) {
            return 1;
        }
        $nodeScopeResolver = $container->getByType(NodeScopeResolver::class);
        $nodeScopeResolver->setAnalysedFiles($analysedFiles);
        $analysedFiles = array_fill_keys($analysedFiles, \true);
        $tcpConnector = new TcpConnector($loop);
        $tcpConnector->connect(sprintf('127.0.0.1:%d', $port))->then(function (ConnectionInterface $connection) use ($container, $identifier, $output, $analysedFiles, $tmpFile, $insteadOfFile): void {
            // phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly
            $jsonInvalidUtf8Ignore = defined('JSON_INVALID_UTF8_IGNORE') ? \JSON_INVALID_UTF8_IGNORE : 0;
            // phpcs:enable
            $out = new Encoder($connection, $jsonInvalidUtf8Ignore);
            $in = new Decoder($connection, \true, 512, $jsonInvalidUtf8Ignore, $container->getParameter('parallel')['buffer']);
            $out->write(['action' => 'hello', 'identifier' => $identifier]);
            $this->runWorker($container, $out, $in, $output, $analysedFiles, $tmpFile, $insteadOfFile);
        });
        $loop->run();
        if ($this->errorCount > 0) {
            return 1;
        }
        return 0;
    }
    /**
     * @param array<string, true> $analysedFiles
     */
    private function runWorker(Container $container, WritableStreamInterface $out, ReadableStreamInterface $in, OutputInterface $output, array $analysedFiles, ?string $tmpFile, ?string $insteadOfFile): void
    {
        $handleError = function (Throwable $error) use ($out, $output): void {
            $this->errorCount++;
            $output->writeln(sprintf('Error: %s', $error->getMessage()));
            $out->write(['action' => 'result', 'result' => ['errors' => [], 'internalErrors' => [new InternalError($error->getMessage(), 'communicating with main process in parallel worker', InternalError::prepareTrace($error), $error->getTraceAsString(), \true)], 'filteredPhpErrors' => [], 'allPhpErrors' => [], 'locallyIgnoredErrors' => [], 'linesToIgnore' => [], 'unmatchedLineIgnores' => [], 'collectedData' => [], 'memoryUsage' => memory_get_peak_usage(\true), 'dependencies' => [], 'exportedNodes' => [], 'files' => [], 'internalErrorsCount' => 1]]);
            $out->end();
        };
        $out->on('error', $handleError);
        $fileAnalyser = $container->getByType(FileAnalyser::class);
        $ruleRegistry = $container->getByType(RuleRegistry::class);
        $collectorRegistry = $container->getByType(CollectorRegistry::class);
        $in->on('data', static function (array $json) use ($fileAnalyser, $ruleRegistry, $collectorRegistry, $out, $analysedFiles, $tmpFile, $insteadOfFile): void {
            $action = $json['action'];
            if ($action !== 'analyse') {
                return;
            }
            $internalErrorsCount = 0;
            $files = $json['files'];
            $errors = [];
            $internalErrors = [];
            $filteredPhpErrors = [];
            $allPhpErrors = [];
            $locallyIgnoredErrors = [];
            $linesToIgnore = [];
            $unmatchedLineIgnores = [];
            $collectedData = [];
            $dependencies = [];
            $usedTraitDependencies = [];
            $exportedNodes = [];
            foreach ($files as $file) {
                try {
                    if ($file === $insteadOfFile) {
                        $file = $tmpFile;
                    }
                    $fileAnalyserResult = $fileAnalyser->analyseFile($file, $analysedFiles, $ruleRegistry, $collectorRegistry, null);
                    $fileErrors = $fileAnalyserResult->getErrors();
                    $filteredPhpErrors = array_merge($filteredPhpErrors, $fileAnalyserResult->getFilteredPhpErrors());
                    $allPhpErrors = array_merge($allPhpErrors, $fileAnalyserResult->getAllPhpErrors());
                    $linesToIgnore[$file] = $fileAnalyserResult->getLinesToIgnore();
                    $unmatchedLineIgnores[$file] = $fileAnalyserResult->getUnmatchedLineIgnores();
                    $dependencies[$file] = $fileAnalyserResult->getDependencies();
                    $usedTraitDependencies[$file] = $fileAnalyserResult->getUsedTraitDependencies();
                    $exportedNodes[$file] = $fileAnalyserResult->getExportedNodes();
                    foreach ($fileErrors as $fileError) {
                        $errors[] = $fileError;
                    }
                    foreach ($fileAnalyserResult->getLocallyIgnoredErrors() as $locallyIgnoredError) {
                        $locallyIgnoredErrors[] = $locallyIgnoredError;
                    }
                    foreach ($fileAnalyserResult->getCollectedData() as $collectedFile => $dataPerCollector) {
                        foreach ($dataPerCollector as $collectorType => $collectorData) {
                            foreach ($collectorData as $data) {
                                $collectedData[$collectedFile][$collectorType][] = $data;
                            }
                        }
                    }
                } catch (Throwable $t) {
                    $internalErrorsCount++;
                    $internalErrors[] = new InternalError($t->getMessage(), sprintf('analysing file %s', $file), InternalError::prepareTrace($t), $t->getTraceAsString(), \true);
                }
            }
            $out->write(['action' => 'result', 'result' => ['errors' => $errors, 'internalErrors' => $internalErrors, 'filteredPhpErrors' => $filteredPhpErrors, 'allPhpErrors' => $allPhpErrors, 'locallyIgnoredErrors' => $locallyIgnoredErrors, 'linesToIgnore' => $linesToIgnore, 'unmatchedLineIgnores' => $unmatchedLineIgnores, 'collectedData' => $collectedData, 'memoryUsage' => memory_get_peak_usage(\true), 'dependencies' => $dependencies, 'usedTraitDependencies' => $usedTraitDependencies, 'exportedNodes' => $exportedNodes, 'files' => $files, 'internalErrorsCount' => $internalErrorsCount]]);
        });
        $in->on('error', $handleError);
    }
    /**
     * @param string[] $analysedFiles
     * @return string[]
     */
    private function switchTmpFile(array $analysedFiles, ?string $insteadOfFile, ?string $tmpFile): array
    {
        if ($insteadOfFile === null) {
            return $analysedFiles;
        }
        $analysedFiles = array_values(array_filter($analysedFiles, static fn(string $file): bool => $file !== $insteadOfFile));
        if ($tmpFile !== null) {
            array_unshift($analysedFiles, $tmpFile);
        }
        return $analysedFiles;
    }
}
