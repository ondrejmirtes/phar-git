<?php

declare (strict_types=1);
namespace PHPStan\Parser;

use PhpParser\Lexer;
use PhpParser\Parser\Php7;
use PhpParser\Parser\Php8;
use PhpParser\ParserAbstract;
use PHPStan\Php\PhpVersion;
final class PhpParserFactory
{
    private Lexer $lexer;
    private PhpVersion $phpVersion;
    public function __construct(Lexer $lexer, PhpVersion $phpVersion)
    {
        $this->lexer = $lexer;
        $this->phpVersion = $phpVersion;
    }
    public function create() : ParserAbstract
    {
        $phpVersion = \PhpParser\PhpVersion::fromString($this->phpVersion->getVersionString());
        if ($this->phpVersion->getVersionId() >= 80000) {
            return new Php8($this->lexer, $phpVersion);
        }
        return new Php7($this->lexer, $phpVersion);
    }
}
