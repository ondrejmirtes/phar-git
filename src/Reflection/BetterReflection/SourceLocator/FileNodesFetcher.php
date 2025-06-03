<?php

declare (strict_types=1);
namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PhpParser\NodeTraverser;
use PHPStan\File\FileReader;
use PHPStan\Parser\Parser;
use PHPStan\Parser\ParserErrorsException;
final class FileNodesFetcher
{
    private \PHPStan\Reflection\BetterReflection\SourceLocator\CachingVisitor $cachingVisitor;
    private Parser $parser;
    public function __construct(\PHPStan\Reflection\BetterReflection\SourceLocator\CachingVisitor $cachingVisitor, Parser $parser)
    {
        $this->cachingVisitor = $cachingVisitor;
        $this->parser = $parser;
    }
    public function fetchNodes(string $fileName) : \PHPStan\Reflection\BetterReflection\SourceLocator\FetchedNodesResult
    {
        $nodeTraverser = new NodeTraverser();
        $nodeTraverser->addVisitor($this->cachingVisitor);
        $contents = FileReader::read($fileName);
        try {
            $ast = $this->parser->parseFile($fileName);
        } catch (ParserErrorsException $e) {
            return new \PHPStan\Reflection\BetterReflection\SourceLocator\FetchedNodesResult([], [], []);
        }
        $this->cachingVisitor->reset($fileName, $contents);
        $nodeTraverser->traverse($ast);
        $result = new \PHPStan\Reflection\BetterReflection\SourceLocator\FetchedNodesResult($this->cachingVisitor->getClassNodes(), $this->cachingVisitor->getFunctionNodes(), $this->cachingVisitor->getConstantNodes());
        $this->cachingVisitor->reset($fileName, $contents);
        return $result;
    }
}
