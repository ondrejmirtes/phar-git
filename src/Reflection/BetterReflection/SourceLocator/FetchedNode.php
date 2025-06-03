<?php

declare (strict_types=1);
namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PhpParser\Node;
use PHPStan\BetterReflection\SourceLocator\Located\LocatedSource;
/**
 * @template-covariant T of Node
 */
final class FetchedNode
{
    /**
     * @var T
     */
    private Node $node;
    private ?Node\Stmt\Namespace_ $namespace;
    private LocatedSource $locatedSource;
    /**
     * @param T $node
     */
    public function __construct(Node $node, ?Node\Stmt\Namespace_ $namespace, LocatedSource $locatedSource)
    {
        $this->node = $node;
        $this->namespace = $namespace;
        $this->locatedSource = $locatedSource;
    }
    /**
     * @return T
     */
    public function getNode(): Node
    {
        return $this->node;
    }
    public function getNamespace(): ?Node\Stmt\Namespace_
    {
        return $this->namespace;
    }
    public function getLocatedSource(): LocatedSource
    {
        return $this->locatedSource;
    }
}
