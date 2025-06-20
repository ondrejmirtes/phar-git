<?php

declare (strict_types=1);
namespace PHPStan\PhpDocParser\Ast;

/**
 * Inspired by https://github.com/nikic/PHP-Parser/tree/36a6dcd04e7b0285e8f0868f44bd4927802f7df1
 *
 * Copyright (c) 2011, Nikita Popov
 * All rights reserved.
 */
interface NodeVisitor
{
    /**
     * Called once before traversal.
     *
     * Return value semantics:
     *  * null:      $nodes stays as-is
     *  * otherwise: $nodes is set to the return value
     *
     * @param Node[] $nodes Array of nodes
     *
     * @return Node[]|null Array of nodes
     */
    public function beforeTraverse(array $nodes): ?array;
    /**
     * Called when entering a node.
     *
     * Return value semantics:
     *  * null
     *        => $node stays as-is
     *  * array (of Nodes)
     *        => The return value is merged into the parent array (at the position of the $node)
     *  * NodeTraverser::REMOVE_NODE
     *        => $node is removed from the parent array
     *  * NodeTraverser::DONT_TRAVERSE_CHILDREN
     *        => Children of $node are not traversed. $node stays as-is
     *  * NodeTraverser::DONT_TRAVERSE_CURRENT_AND_CHILDREN
     *        => Further visitors for the current node are skipped, and its children are not
     *           traversed. $node stays as-is.
     *  * NodeTraverser::STOP_TRAVERSAL
     *        => Traversal is aborted. $node stays as-is
     *  * otherwise
     *        => $node is set to the return value
     *
     * @param Node $node Node
     *
     * @return Node|Node[]|NodeTraverser::*|null Replacement node (or special return value)
     */
    public function enterNode(\PHPStan\PhpDocParser\Ast\Node $node);
    /**
     * Called when leaving a node.
     *
     * Return value semantics:
     *  * null
     *        => $node stays as-is
     *  * NodeTraverser::REMOVE_NODE
     *        => $node is removed from the parent array
     *  * NodeTraverser::STOP_TRAVERSAL
     *        => Traversal is aborted. $node stays as-is
     *  * array (of Nodes)
     *        => The return value is merged into the parent array (at the position of the $node)
     *  * otherwise
     *        => $node is set to the return value
     *
     * @param Node $node Node
     *
     * @return Node|Node[]|NodeTraverser::REMOVE_NODE|NodeTraverser::STOP_TRAVERSAL|null Replacement node (or special return value)
     */
    public function leaveNode(\PHPStan\PhpDocParser\Ast\Node $node);
    /**
     * Called once after traversal.
     *
     * Return value semantics:
     *  * null:      $nodes stays as-is
     *  * otherwise: $nodes is set to the return value
     *
     * @param Node[] $nodes Array of nodes
     *
     * @return Node[]|null Array of nodes
     */
    public function afterTraverse(array $nodes): ?array;
}
