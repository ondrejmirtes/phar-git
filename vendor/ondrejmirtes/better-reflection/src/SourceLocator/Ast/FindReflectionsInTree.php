<?php

declare (strict_types=1);
namespace PHPStan\BetterReflection\SourceLocator\Ast;

use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitorAbstract;
use PHPStan\BetterReflection\Identifier\IdentifierType;
use PHPStan\BetterReflection\Reflection\Exception\InvalidConstantNode;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\BetterReflection\Reflection\ReflectionFunction;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use PHPStan\BetterReflection\Reflector\Reflector;
use PHPStan\BetterReflection\SourceLocator\Ast\Strategy\AstConversionStrategy;
use PHPStan\BetterReflection\SourceLocator\Located\LocatedSource;
use PHPStan\BetterReflection\Util\ConstantNodeChecker;
use function assert;
use function count;
/** @internal */
final class FindReflectionsInTree
{
    private AstConversionStrategy $astConversionStrategy;
    public function __construct(AstConversionStrategy $astConversionStrategy)
    {
        $this->astConversionStrategy = $astConversionStrategy;
    }
    /**
     * Find all reflections of a given type in an Abstract Syntax Tree
     *
     * @param Node[] $ast
     *
     * @return list<ReflectionClass|ReflectionFunction|ReflectionConstant>
     */
    public function __invoke(Reflector $reflector, array $ast, IdentifierType $identifierType, LocatedSource $locatedSource): array
    {
        $nodeVisitor = new class($reflector, $identifierType, $locatedSource, $this->astConversionStrategy) extends NodeVisitorAbstract
        {
            private Reflector $reflector;
            private IdentifierType $identifierType;
            private LocatedSource $locatedSource;
            private AstConversionStrategy $astConversionStrategy;
            /** @var list<ReflectionClass|ReflectionFunction|ReflectionConstant> */
            private array $reflections = [];
            /**
             * @var \PhpParser\Node\Stmt\Namespace_|null
             */
            private $currentNamespace = null;
            public function __construct(Reflector $reflector, IdentifierType $identifierType, LocatedSource $locatedSource, AstConversionStrategy $astConversionStrategy)
            {
                $this->reflector = $reflector;
                $this->identifierType = $identifierType;
                $this->locatedSource = $locatedSource;
                $this->astConversionStrategy = $astConversionStrategy;
            }
            /**
             * {@inheritDoc}
             */
            public function enterNode(Node $node)
            {
                if ($node instanceof Namespace_) {
                    $this->currentNamespace = $node;
                }
                return null;
            }
            /**
             * {@inheritDoc}
             */
            public function leaveNode(Node $node)
            {
                if ($this->identifierType->isClass() && ($node instanceof Node\Stmt\Class_ || $node instanceof Node\Stmt\Interface_ || $node instanceof Node\Stmt\Trait_ || $node instanceof Node\Stmt\Enum_)) {
                    $classNamespace = $node->name === null ? null : $this->currentNamespace;
                    $this->reflections[] = $this->astConversionStrategy->__invoke($this->reflector, $node, $this->locatedSource, $classNamespace);
                    return null;
                }
                if ($this->identifierType->isConstant()) {
                    if ($node instanceof Node\Stmt\Const_) {
                        for ($i = 0; $i < count($node->consts); $i++) {
                            $this->reflections[] = $this->astConversionStrategy->__invoke($this->reflector, $node, $this->locatedSource, $this->currentNamespace, $i);
                        }
                        return null;
                    }
                    if ($node instanceof Node\Stmt\Expression && $node->expr instanceof Node\Expr\FuncCall) {
                        $functionCall = $node->expr;
                        try {
                            ConstantNodeChecker::assertValidDefineFunctionCall($functionCall);
                        } catch (InvalidConstantNode $exception) {
                            return null;
                        }
                        if ($functionCall->name->hasAttribute('namespacedName')) {
                            $namespacedName = $functionCall->name->getAttribute('namespacedName');
                            assert($namespacedName instanceof Name);
                            try {
                                $this->reflector->reflectFunction($namespacedName->toString());
                                return null;
                            } catch (IdentifierNotFound $exception) {
                                // Global define()
                            }
                        }
                        $nodeDocComment = $node->getDocComment();
                        if ($nodeDocComment !== null) {
                            $functionCall->setDocComment($nodeDocComment);
                        }
                        $this->reflections[] = $this->astConversionStrategy->__invoke($this->reflector, $functionCall, $this->locatedSource, $this->currentNamespace);
                        return null;
                    }
                }
                if ($this->identifierType->isFunction() && $node instanceof Node\Stmt\Function_) {
                    $this->reflections[] = $this->astConversionStrategy->__invoke($this->reflector, $node, $this->locatedSource, $this->currentNamespace);
                }
                if ($node instanceof Namespace_) {
                    $this->currentNamespace = null;
                }
                return null;
            }
            /** @return list<ReflectionClass|ReflectionFunction|ReflectionConstant> */
            public function getReflections(): array
            {
                return $this->reflections;
            }
        };
        $nodeTraverser = new NodeTraverser(new NameResolver(), $nodeVisitor);
        $nodeTraverser->traverse($ast);
        return $nodeVisitor->getReflections();
    }
}
