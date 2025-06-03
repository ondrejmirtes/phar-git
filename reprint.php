<?php

require_once __DIR__ . '/vendor/autoload.php';
$parserFactory = new \PhpParser\ParserFactory();
$parser = $parserFactory->createForNewestSupportedVersion();
$iterator = new RecursiveDirectoryIterator(__DIR__);
$iterator->setFlags(RecursiveDirectoryIterator::SKIP_DOTS);
$files = new RecursiveIteratorIterator($iterator);
$printer = new \PhpParser\PrettyPrinter\Standard();
foreach ($files as $file) {
    $path = $file->getPathname();
    if ($file->getExtension() !== 'php') {
        continue;
    }
    $contents = file_get_contents($path);
    $ast = $parser->parse($contents);
    file_put_contents($path, $printer->prettyPrintFile($ast) . "\n");
}
