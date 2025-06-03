<?php

declare (strict_types=1);
namespace PHPStan\Node;

use PhpParser\Node;
use PhpParser\NodeAbstract;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Reflection\Php\PhpPropertyReflection;
/**
 * @api
 */
final class InPropertyHookNode extends NodeAbstract implements \PHPStan\Node\VirtualNode
{
    private ClassReflection $classReflection;
    private PhpMethodFromParserNodeReflection $hookReflection;
    private PhpPropertyReflection $propertyReflection;
    private Node\PropertyHook $originalNode;
    public function __construct(ClassReflection $classReflection, PhpMethodFromParserNodeReflection $hookReflection, PhpPropertyReflection $propertyReflection, Node\PropertyHook $originalNode)
    {
        $this->classReflection = $classReflection;
        $this->hookReflection = $hookReflection;
        $this->propertyReflection = $propertyReflection;
        $this->originalNode = $originalNode;
        parent::__construct($originalNode->getAttributes());
    }
    public function getClassReflection(): ClassReflection
    {
        return $this->classReflection;
    }
    public function getHookReflection(): PhpMethodFromParserNodeReflection
    {
        return $this->hookReflection;
    }
    public function getPropertyReflection(): PhpPropertyReflection
    {
        return $this->propertyReflection;
    }
    public function getOriginalNode(): Node\PropertyHook
    {
        return $this->originalNode;
    }
    public function getType(): string
    {
        return 'PHPStan_Node_InPropertyHookNode';
    }
    /**
     * @return string[]
     */
    public function getSubNodeNames(): array
    {
        return [];
    }
}
