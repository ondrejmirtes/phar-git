<?php

declare (strict_types=1);
namespace PHPStan\Analyser;

use PhpParser\Internal\TokenStream;
use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\Parser;
use PHPStan\File\FileReader;
use PHPStan\Fixable\PhpPrinter;
use PHPStan\Fixable\PhpPrinterIndentationDetectorVisitor;
use PHPStan\Fixable\ReplacingNodeVisitor;
use PHPStan\Fixable\UnwrapVirtualNodesVisitor;
use PHPStan\Node\VirtualNode;
use PHPStan\Rules\FileRuleError;
use PHPStan\Rules\FixableNodeRuleError;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\LineRuleError;
use PHPStan\Rules\MetadataRuleError;
use PHPStan\Rules\NonIgnorableRuleError;
use PHPStan\Rules\RuleError;
use PHPStan\Rules\TipRuleError;
use PHPStan\ShouldNotHappenException;
use _PHPStan_checksum\SebastianBergmann\Diff\Differ;
use _PHPStan_checksum\SebastianBergmann\Diff\Output\UnifiedDiffOutputBuilder;
use function get_class;
use function sha1;
use function str_repeat;
final class RuleErrorTransformer
{
    private Parser $parser;
    private Differ $differ;
    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
        $this->differ = new Differ(new UnifiedDiffOutputBuilder('', \true));
    }
    /**
     * @param Node\Stmt[] $fileNodes
     */
    public function transform(RuleError $ruleError, \PHPStan\Analyser\Scope $scope, array $fileNodes, Node $node) : \PHPStan\Analyser\Error
    {
        $line = $node->getStartLine();
        $canBeIgnored = \true;
        $fileName = $scope->getFileDescription();
        $filePath = $scope->getFile();
        $traitFilePath = null;
        $tip = null;
        $identifier = null;
        $metadata = [];
        if ($scope->isInTrait()) {
            $traitReflection = $scope->getTraitReflection();
            if ($traitReflection->getFileName() !== null) {
                $traitFilePath = $traitReflection->getFileName();
            }
        }
        if ($ruleError instanceof LineRuleError && $ruleError->getLine() !== -1) {
            $line = $ruleError->getLine();
        }
        if ($ruleError instanceof FileRuleError && $ruleError->getFile() !== '') {
            $fileName = $ruleError->getFileDescription();
            $filePath = $ruleError->getFile();
            $traitFilePath = null;
        }
        if ($ruleError instanceof TipRuleError) {
            $tip = $ruleError->getTip();
        }
        if ($ruleError instanceof IdentifierRuleError) {
            $identifier = $ruleError->getIdentifier();
        }
        if ($ruleError instanceof MetadataRuleError) {
            $metadata = $ruleError->getMetadata();
        }
        if ($ruleError instanceof NonIgnorableRuleError) {
            $canBeIgnored = \false;
        }
        $fixedErrorDiff = null;
        if ($ruleError instanceof FixableNodeRuleError) {
            if ($ruleError->getOriginalNode() instanceof VirtualNode) {
                throw new ShouldNotHappenException('Cannot fix virtual node');
            }
            $fixingFile = $filePath;
            if ($traitFilePath !== null) {
                $fixingFile = $traitFilePath;
            }
            $oldCode = FileReader::read($fixingFile);
            $this->parser->parse($oldCode);
            $hash = sha1($oldCode);
            $oldTokens = $this->parser->getTokens();
            $indentTraverser = new NodeTraverser();
            $indentDetector = new PhpPrinterIndentationDetectorVisitor(new TokenStream($oldTokens, PhpPrinter::TAB_WIDTH));
            $indentTraverser->addVisitor($indentDetector);
            $indentTraverser->traverse($fileNodes);
            $cloningTraverser = new NodeTraverser();
            $cloningTraverser->addVisitor(new UnwrapVirtualNodesVisitor());
            $cloningTraverser->addVisitor(new CloningVisitor());
            /** @var Stmt[] $newStmts */
            $newStmts = $cloningTraverser->traverse($fileNodes);
            $traverser = new NodeTraverser();
            $visitor = new ReplacingNodeVisitor($ruleError->getOriginalNode(), $ruleError->getNewNodeCallable());
            $traverser->addVisitor($visitor);
            /** @var Stmt[] $newStmts */
            $newStmts = $traverser->traverse($newStmts);
            if ($visitor->isFound()) {
                $printer = new PhpPrinter(['indent' => str_repeat($indentDetector->indentCharacter, $indentDetector->indentSize)]);
                $newCode = $printer->printFormatPreserving($newStmts, $fileNodes, $oldTokens);
                if ($oldCode !== $newCode) {
                    $fixedErrorDiff = new \PHPStan\Analyser\FixedErrorDiff($hash, $this->differ->diff($oldCode, $newCode));
                }
            }
        }
        return new \PHPStan\Analyser\Error($ruleError->getMessage(), $fileName, $line, $canBeIgnored, $filePath, $traitFilePath, $tip, $node->getStartLine(), get_class($node), $identifier, $metadata, $fixedErrorDiff);
    }
}
