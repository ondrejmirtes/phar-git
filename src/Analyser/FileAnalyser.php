<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\AnalysedCodeException;
use PHPStan\BetterReflection\NodeCompiler\Exception\UnableToCompileNode;
use PHPStan\BetterReflection\Reflection\Exception\CircularReference;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\Collectors\CollectedData;
use PHPStan\Collectors\Registry as CollectorRegistry;
use PHPStan\Dependency\DependencyResolver;
use PHPStan\Node\FileNode;
use PHPStan\Node\InClassNode;
use PHPStan\Node\InTraitNode;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ParserErrorsException;
use PHPStan\Rules\Registry as RuleRegistry;
use function array_keys;
use function array_unique;
use function array_values;
use function count;
use function error_reporting;
use function get_class;
use function is_dir;
use function is_file;
use function restore_error_handler;
use function set_error_handler;
use function sprintf;
use const E_DEPRECATED;
use const E_ERROR;
use const E_NOTICE;
use const E_PARSE;
use const E_STRICT;
use const E_USER_DEPRECATED;
use const E_USER_ERROR;
use const E_USER_NOTICE;
use const E_USER_WARNING;
use const E_WARNING;
/**
 * @phpstan-import-type CollectorData from CollectedData
 */
final class FileAnalyser
{
    private \PHPStan\Analyser\ScopeFactory $scopeFactory;
    private \PHPStan\Analyser\NodeScopeResolver $nodeScopeResolver;
    private Parser $parser;
    private DependencyResolver $dependencyResolver;
    private \PHPStan\Analyser\IgnoreErrorExtensionProvider $ignoreErrorExtensionProvider;
    private \PHPStan\Analyser\RuleErrorTransformer $ruleErrorTransformer;
    private \PHPStan\Analyser\LocalIgnoresProcessor $localIgnoresProcessor;
    /** @var list<Error> */
    private array $allPhpErrors = [];
    /** @var list<Error> */
    private array $filteredPhpErrors = [];
    public function __construct(\PHPStan\Analyser\ScopeFactory $scopeFactory, \PHPStan\Analyser\NodeScopeResolver $nodeScopeResolver, Parser $parser, DependencyResolver $dependencyResolver, \PHPStan\Analyser\IgnoreErrorExtensionProvider $ignoreErrorExtensionProvider, \PHPStan\Analyser\RuleErrorTransformer $ruleErrorTransformer, \PHPStan\Analyser\LocalIgnoresProcessor $localIgnoresProcessor)
    {
        $this->scopeFactory = $scopeFactory;
        $this->nodeScopeResolver = $nodeScopeResolver;
        $this->parser = $parser;
        $this->dependencyResolver = $dependencyResolver;
        $this->ignoreErrorExtensionProvider = $ignoreErrorExtensionProvider;
        $this->ruleErrorTransformer = $ruleErrorTransformer;
        $this->localIgnoresProcessor = $localIgnoresProcessor;
    }
    /**
     * @param array<string, true> $analysedFiles
     * @param callable(Node $node, Scope $scope): void|null $outerNodeCallback
     */
    public function analyseFile(string $file, array $analysedFiles, RuleRegistry $ruleRegistry, CollectorRegistry $collectorRegistry, ?callable $outerNodeCallback): \PHPStan\Analyser\FileAnalyserResult
    {
        /** @var list<Error> $fileErrors */
        $fileErrors = [];
        /** @var list<Error> $locallyIgnoredErrors */
        $locallyIgnoredErrors = [];
        /** @var CollectorData $fileCollectedData */
        $fileCollectedData = [];
        $fileDependencies = [];
        $usedTraitFileDependencies = [];
        $exportedNodes = [];
        $linesToIgnore = [];
        $unmatchedLineIgnores = [];
        if (is_file($file)) {
            try {
                $this->collectErrors($analysedFiles);
                $parserNodes = $this->parser->parseFile($file);
                $linesToIgnore = $unmatchedLineIgnores = [$file => $this->getLinesToIgnoreFromTokens($parserNodes)];
                $temporaryFileErrors = [];
                $nodeCallback = function (Node $node, \PHPStan\Analyser\Scope $scope) use (&$fileErrors, &$fileCollectedData, &$fileDependencies, &$usedTraitFileDependencies, &$exportedNodes, $file, $ruleRegistry, $collectorRegistry, $outerNodeCallback, $analysedFiles, &$linesToIgnore, &$unmatchedLineIgnores, &$temporaryFileErrors, $parserNodes): void {
                    if ($node instanceof Node\Stmt\Trait_) {
                        foreach (array_keys($linesToIgnore[$file] ?? []) as $lineToIgnore) {
                            if ($lineToIgnore < $node->getStartLine() || $lineToIgnore > $node->getEndLine()) {
                                continue;
                            }
                            unset($unmatchedLineIgnores[$file][$lineToIgnore]);
                        }
                    }
                    if ($node instanceof InTraitNode) {
                        $traitNode = $node->getOriginalNode();
                        $linesToIgnore[$scope->getFileDescription()] = $this->getLinesToIgnoreFromTokens([$traitNode]);
                    }
                    if ($scope->isInTrait()) {
                        $traitReflection = $scope->getTraitReflection();
                        if ($traitReflection->getFileName() !== null) {
                            $traitFilePath = $traitReflection->getFileName();
                            $parserNodes = $this->parser->parseFile($traitFilePath);
                        }
                    }
                    if ($outerNodeCallback !== null) {
                        $outerNodeCallback($node, $scope);
                    }
                    $uniquedAnalysedCodeExceptionMessages = [];
                    $nodeType = get_class($node);
                    foreach ($ruleRegistry->getRules($nodeType) as $rule) {
                        try {
                            $ruleErrors = $rule->processNode($node, $scope);
                        } catch (AnalysedCodeException $e) {
                            if (isset($uniquedAnalysedCodeExceptionMessages[$e->getMessage()])) {
                                continue;
                            }
                            $uniquedAnalysedCodeExceptionMessages[$e->getMessage()] = \true;
                            $fileErrors[] = (new \PHPStan\Analyser\Error($e->getMessage(), $file, $node->getStartLine(), $e, null, null, $e->getTip()))->withIdentifier('phpstan.internal')->withMetadata([\PHPStan\Analyser\InternalError::STACK_TRACE_METADATA_KEY => \PHPStan\Analyser\InternalError::prepareTrace($e), \PHPStan\Analyser\InternalError::STACK_TRACE_AS_STRING_METADATA_KEY => $e->getTraceAsString()]);
                            continue;
                        } catch (IdentifierNotFound $e) {
                            $fileErrors[] = (new \PHPStan\Analyser\Error(sprintf('Reflection error: %s not found.', $e->getIdentifier()->getName()), $file, $node->getStartLine(), $e, null, null, 'Learn more at https://phpstan.org/user-guide/discovering-symbols'))->withIdentifier('phpstan.reflection')->withMetadata([\PHPStan\Analyser\InternalError::STACK_TRACE_METADATA_KEY => \PHPStan\Analyser\InternalError::prepareTrace($e), \PHPStan\Analyser\InternalError::STACK_TRACE_AS_STRING_METADATA_KEY => $e->getTraceAsString()]);
                            continue;
                        } catch (UnableToCompileNode|CircularReference $e) {
                            $fileErrors[] = (new \PHPStan\Analyser\Error(sprintf('Reflection error: %s', $e->getMessage()), $file, $node->getStartLine(), $e))->withIdentifier('phpstan.reflection')->withMetadata([\PHPStan\Analyser\InternalError::STACK_TRACE_METADATA_KEY => \PHPStan\Analyser\InternalError::prepareTrace($e), \PHPStan\Analyser\InternalError::STACK_TRACE_AS_STRING_METADATA_KEY => $e->getTraceAsString()]);
                            continue;
                        }
                        foreach ($ruleErrors as $ruleError) {
                            $error = $this->ruleErrorTransformer->transform($ruleError, $scope, $parserNodes, $node);
                            if ($error->canBeIgnored()) {
                                foreach ($this->ignoreErrorExtensionProvider->getExtensions() as $ignoreErrorExtension) {
                                    if ($ignoreErrorExtension->shouldIgnore($error, $node, $scope)) {
                                        continue 2;
                                    }
                                }
                            }
                            $temporaryFileErrors[] = $error;
                        }
                    }
                    foreach ($collectorRegistry->getCollectors($nodeType) as $collector) {
                        try {
                            $collectedData = $collector->processNode($node, $scope);
                        } catch (AnalysedCodeException $e) {
                            if (isset($uniquedAnalysedCodeExceptionMessages[$e->getMessage()])) {
                                continue;
                            }
                            $uniquedAnalysedCodeExceptionMessages[$e->getMessage()] = \true;
                            $fileErrors[] = (new \PHPStan\Analyser\Error($e->getMessage(), $file, $node->getStartLine(), $e, null, null, $e->getTip()))->withIdentifier('phpstan.internal')->withMetadata([\PHPStan\Analyser\InternalError::STACK_TRACE_METADATA_KEY => \PHPStan\Analyser\InternalError::prepareTrace($e), \PHPStan\Analyser\InternalError::STACK_TRACE_AS_STRING_METADATA_KEY => $e->getTraceAsString()]);
                            continue;
                        } catch (IdentifierNotFound $e) {
                            $fileErrors[] = (new \PHPStan\Analyser\Error(sprintf('Reflection error: %s not found.', $e->getIdentifier()->getName()), $file, $node->getStartLine(), $e, null, null, 'Learn more at https://phpstan.org/user-guide/discovering-symbols'))->withIdentifier('phpstan.reflection')->withMetadata([\PHPStan\Analyser\InternalError::STACK_TRACE_METADATA_KEY => \PHPStan\Analyser\InternalError::prepareTrace($e), \PHPStan\Analyser\InternalError::STACK_TRACE_AS_STRING_METADATA_KEY => $e->getTraceAsString()]);
                            continue;
                        } catch (UnableToCompileNode|CircularReference $e) {
                            $fileErrors[] = (new \PHPStan\Analyser\Error(sprintf('Reflection error: %s', $e->getMessage()), $file, $node->getStartLine(), $e))->withIdentifier('phpstan.reflection')->withMetadata([\PHPStan\Analyser\InternalError::STACK_TRACE_METADATA_KEY => \PHPStan\Analyser\InternalError::prepareTrace($e), \PHPStan\Analyser\InternalError::STACK_TRACE_AS_STRING_METADATA_KEY => $e->getTraceAsString()]);
                            continue;
                        }
                        if ($collectedData === null) {
                            continue;
                        }
                        $fileCollectedData[$scope->getFile()][get_class($collector)][] = $collectedData;
                    }
                    try {
                        $dependencies = $this->dependencyResolver->resolveDependencies($node, $scope);
                        foreach ($dependencies->getFileDependencies($scope->getFile(), $analysedFiles) as $dependentFile) {
                            $fileDependencies[] = $dependentFile;
                        }
                        if ($dependencies->getExportedNode() !== null) {
                            $exportedNodes[] = $dependencies->getExportedNode();
                        }
                    } catch (AnalysedCodeException $e) {
                        // pass
                    } catch (IdentifierNotFound $e) {
                        // pass
                    } catch (UnableToCompileNode $e) {
                        // pass
                    }
                    if (!$node instanceof InClassNode) {
                        return;
                    }
                    $usedTraitDependencies = $this->dependencyResolver->resolveUsedTraitDependencies($node);
                    foreach ($usedTraitDependencies->getFileDependencies($scope->getFile(), $analysedFiles) as $dependentFile) {
                        $usedTraitFileDependencies[] = $dependentFile;
                    }
                };
                $scope = $this->scopeFactory->create(\PHPStan\Analyser\ScopeContext::create($file));
                $nodeCallback(new FileNode($parserNodes), $scope);
                $this->nodeScopeResolver->processNodes($parserNodes, $scope, $nodeCallback);
                $localIgnoresProcessorResult = $this->localIgnoresProcessor->process($temporaryFileErrors, $linesToIgnore, $unmatchedLineIgnores);
                foreach ($localIgnoresProcessorResult->getFileErrors() as $fileError) {
                    $fileErrors[] = $fileError;
                }
                foreach ($localIgnoresProcessorResult->getLocallyIgnoredErrors() as $locallyIgnoredError) {
                    $locallyIgnoredErrors[] = $locallyIgnoredError;
                }
                $linesToIgnore = $localIgnoresProcessorResult->getLinesToIgnore();
                $unmatchedLineIgnores = $localIgnoresProcessorResult->getUnmatchedLineIgnores();
            } catch (\PhpParser\Error $e) {
                $fileErrors[] = (new \PHPStan\Analyser\Error($e->getRawMessage(), $file, $e->getStartLine() !== -1 ? $e->getStartLine() : null, $e))->withIdentifier('phpstan.parse');
            } catch (ParserErrorsException $e) {
                foreach ($e->getErrors() as $error) {
                    $fileErrors[] = (new \PHPStan\Analyser\Error($error->getMessage(), $e->getParsedFile() ?? $file, $error->getLine() !== -1 ? $error->getStartLine() : null, $e))->withIdentifier('phpstan.parse');
                }
            } catch (AnalysedCodeException $e) {
                $fileErrors[] = (new \PHPStan\Analyser\Error($e->getMessage(), $file, null, $e, null, null, $e->getTip()))->withIdentifier('phpstan.internal')->withMetadata([\PHPStan\Analyser\InternalError::STACK_TRACE_METADATA_KEY => \PHPStan\Analyser\InternalError::prepareTrace($e), \PHPStan\Analyser\InternalError::STACK_TRACE_AS_STRING_METADATA_KEY => $e->getTraceAsString()]);
            } catch (IdentifierNotFound $e) {
                $fileErrors[] = (new \PHPStan\Analyser\Error(sprintf('Reflection error: %s not found.', $e->getIdentifier()->getName()), $file, null, $e, null, null, 'Learn more at https://phpstan.org/user-guide/discovering-symbols'))->withIdentifier('phpstan.reflection')->withMetadata([\PHPStan\Analyser\InternalError::STACK_TRACE_METADATA_KEY => \PHPStan\Analyser\InternalError::prepareTrace($e), \PHPStan\Analyser\InternalError::STACK_TRACE_AS_STRING_METADATA_KEY => $e->getTraceAsString()]);
            } catch (UnableToCompileNode|CircularReference $e) {
                $fileErrors[] = (new \PHPStan\Analyser\Error(sprintf('Reflection error: %s', $e->getMessage()), $file, null, $e))->withIdentifier('phpstan.reflection')->withMetadata([\PHPStan\Analyser\InternalError::STACK_TRACE_METADATA_KEY => \PHPStan\Analyser\InternalError::prepareTrace($e), \PHPStan\Analyser\InternalError::STACK_TRACE_AS_STRING_METADATA_KEY => $e->getTraceAsString()]);
            }
        } elseif (is_dir($file)) {
            $fileErrors[] = (new \PHPStan\Analyser\Error(sprintf('File %s is a directory.', $file), $file, null, \false))->withIdentifier('phpstan.path');
        } else {
            $fileErrors[] = (new \PHPStan\Analyser\Error(sprintf('File %s does not exist.', $file), $file, null, \false))->withIdentifier('phpstan.path');
        }
        $this->restoreCollectErrorsHandler();
        foreach ($linesToIgnore as $fileKey => $lines) {
            if (count($lines) > 0) {
                continue;
            }
            unset($linesToIgnore[$fileKey]);
        }
        foreach ($unmatchedLineIgnores as $fileKey => $lines) {
            if (count($lines) > 0) {
                continue;
            }
            unset($unmatchedLineIgnores[$fileKey]);
        }
        return new \PHPStan\Analyser\FileAnalyserResult($fileErrors, $this->filteredPhpErrors, $this->allPhpErrors, $locallyIgnoredErrors, $fileCollectedData, array_values(array_unique($fileDependencies)), array_values(array_unique($usedTraitFileDependencies)), $exportedNodes, $linesToIgnore, $unmatchedLineIgnores);
    }
    /**
     * @param Node[] $nodes
     * @return array<int, non-empty-list<string>|null>
     */
    private function getLinesToIgnoreFromTokens(array $nodes): array
    {
        if (!isset($nodes[0])) {
            return [];
        }
        /** @var array<int, non-empty-list<string>|null> */
        return $nodes[0]->getAttribute('linesToIgnore', []);
    }
    /**
     * @param array<string, true> $analysedFiles
     */
    private function collectErrors(array $analysedFiles): void
    {
        $this->filteredPhpErrors = [];
        $this->allPhpErrors = [];
        set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) use ($analysedFiles): bool {
            if ((error_reporting() & $errno) === 0) {
                // silence @ operator
                return \true;
            }
            $errorMessage = sprintf('%s: %s', $this->getErrorLabel($errno), $errstr);
            $this->allPhpErrors[] = (new \PHPStan\Analyser\Error($errorMessage, $errfile, $errline, \false))->withIdentifier('phpstan.php');
            if ($errno === E_DEPRECATED) {
                return \true;
            }
            if (!isset($analysedFiles[$errfile])) {
                return \true;
            }
            $this->filteredPhpErrors[] = (new \PHPStan\Analyser\Error($errorMessage, $errfile, $errline, $errno === E_USER_DEPRECATED))->withIdentifier('phpstan.php');
            return \true;
        });
    }
    private function restoreCollectErrorsHandler(): void
    {
        restore_error_handler();
    }
    private function getErrorLabel(int $errno): string
    {
        switch ($errno) {
            case E_ERROR:
                return 'Fatal error';
            case E_WARNING:
                return 'Warning';
            case E_PARSE:
                return 'Parse error';
            case E_NOTICE:
                return 'Notice';
            case E_DEPRECATED:
                return 'Deprecated';
            case E_USER_ERROR:
                return 'User error (E_USER_ERROR)';
            case E_USER_WARNING:
                return 'User warning (E_USER_WARNING)';
            case E_USER_NOTICE:
                return 'User notice (E_USER_NOTICE)';
            case E_USER_DEPRECATED:
                return 'Deprecated (E_USER_DEPRECATED)';
            case E_STRICT:
                return 'Strict error (E_STRICT)';
        }
        return 'Unknown PHP error';
    }
}
