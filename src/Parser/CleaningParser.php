<?php

declare (strict_types=1);
namespace PHPStan\Parser;

use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PHPStan\Php\PhpVersion;
final class CleaningParser implements \PHPStan\Parser\Parser
{
    private \PHPStan\Parser\Parser $wrappedParser;
    private NodeTraverser $traverser;
    public function __construct(\PHPStan\Parser\Parser $wrappedParser, PhpVersion $phpVersion)
    {
        $this->wrappedParser = $wrappedParser;
        $this->traverser = new NodeTraverser();
        $this->traverser->addVisitor(new \PHPStan\Parser\CleaningVisitor());
        $this->traverser->addVisitor(new \PHPStan\Parser\RemoveUnusedCodeByPhpVersionIdVisitor($phpVersion->getVersionString()));
    }
    public function parseFile(string $file): array
    {
        return $this->clean($this->wrappedParser->parseFile($file));
    }
    public function parseString(string $sourceCode): array
    {
        return $this->clean($this->wrappedParser->parseString($sourceCode));
    }
    /**
     * @param Stmt[] $ast
     * @return Stmt[]
     */
    private function clean(array $ast): array
    {
        /** @var Stmt[] */
        return $this->traverser->traverse($ast);
    }
}
