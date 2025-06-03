<?php

declare (strict_types=1);
namespace PHPStan\BetterReflection\NodeCompiler;

use PhpParser\ConstExprEvaluator;
use PhpParser\Node;
use PHPStan\BetterReflection\Reflection\ReflectionClass;
use PHPStan\BetterReflection\Reflection\ReflectionClassConstant;
use PHPStan\BetterReflection\Reflection\ReflectionEnum;
use PHPStan\BetterReflection\Reflection\ReflectionMethod;
use PHPStan\BetterReflection\Reflector\Exception\IdentifierNotFound;
use function array_map;
use function assert;
use function class_exists;
use function constant;
use function defined;
use function dirname;
use function explode;
use function in_array;
use function is_file;
use function sprintf;
/** @internal */
class CompileNodeToValue
{
    private const TRUE_FALSE_NULL = ['true', 'false', 'null'];
    /**
     * Compile an expression from a node into a value.
     *
     * @param Node\Stmt\Expression|Node\Expr $node Node has to be processed by the PhpParser\NodeVisitor\NameResolver
     *
     * @throws Exception\UnableToCompileNode
     */
    public function __invoke(Node $node, \PHPStan\BetterReflection\NodeCompiler\CompilerContext $context) : \PHPStan\BetterReflection\NodeCompiler\CompiledValue
    {
        if ($node instanceof Node\Stmt\Expression) {
            return $this($node->expr, $context);
        }
        $constantName = null;
        if ($node instanceof Node\Expr\ConstFetch && !in_array($node->name->toLowerString(), self::TRUE_FALSE_NULL, \true)) {
            $constantName = $this->resolveConstantName($node, $context);
        } elseif ($node instanceof Node\Expr\ClassConstFetch) {
            $constantName = $this->resolveClassConstantName($node, $context);
        }
        $constExprEvaluator = new ConstExprEvaluator(function (Node\Expr $node) use($context, $constantName) {
            if ($node instanceof Node\Expr\ConstFetch) {
                return $this->getConstantValue($node, $constantName, $context);
            }
            if ($node instanceof Node\Expr\ClassConstFetch) {
                return $this->getClassConstantValue($node, $constantName, $context);
            }
            if ($node instanceof Node\Expr\New_) {
                return $this->compileNew($node, $context);
            }
            if ($node instanceof Node\Scalar\MagicConst\Dir) {
                return $this->compileDirConstant($context, $node);
            }
            if ($node instanceof Node\Scalar\MagicConst\File) {
                return $this->compileFileConstant($context, $node);
            }
            if ($node instanceof Node\Scalar\MagicConst\Class_) {
                return $this->compileClassConstant($context);
            }
            if ($node instanceof Node\Scalar\MagicConst\Line) {
                return $node->getStartLine();
            }
            if ($node instanceof Node\Scalar\MagicConst\Namespace_) {
                return $context->getNamespace() ?? '';
            }
            if ($node instanceof Node\Scalar\MagicConst\Property) {
                $method = $context->getFunction();
                if ($method !== null && $method instanceof ReflectionMethod && $method->isHook()) {
                    return $method->getHookProperty()->getName();
                }
                return '';
            }
            if ($node instanceof Node\Scalar\MagicConst\Method) {
                $class = $context->getClass();
                $function = $context->getFunction();
                if ($class !== null && $function !== null) {
                    return sprintf('%s::%s', $class->getName(), $function->getName());
                }
                if ($function !== null) {
                    return $function->getName();
                }
                return '';
            }
            if ($node instanceof Node\Scalar\MagicConst\Function_) {
                return (($nullsafeVariable1 = $context->getFunction()) ? $nullsafeVariable1->getName() : null) ?? '';
            }
            if ($node instanceof Node\Scalar\MagicConst\Trait_) {
                $class = $context->getClass();
                if ($class !== null && $class->isTrait()) {
                    return $class->getName();
                }
                return '';
            }
            if ($node instanceof Node\Expr\FuncCall && $node->name instanceof Node\Name && $node->name->toLowerString() === 'constant' && $node->args[0] instanceof Node\Arg && $node->args[0]->value instanceof Node\Scalar\String_ && defined($node->args[0]->value->value)) {
                return constant($node->args[0]->value->value);
            }
            if ($node instanceof Node\Expr\PropertyFetch && $node->var instanceof Node\Expr\ClassConstFetch) {
                return $this->getEnumPropertyValue($node, $context);
            }
            throw \PHPStan\BetterReflection\NodeCompiler\Exception\UnableToCompileNode::forUnRecognizedExpressionInContext($node, $context);
        });
        /** @psalm-var mixed $value */
        $value = $constExprEvaluator->evaluateDirectly($node);
        return new \PHPStan\BetterReflection\NodeCompiler\CompiledValue($value, $constantName);
    }
    /**
     * @return mixed
     */
    private function getEnumPropertyValue(Node\Expr\PropertyFetch $node, \PHPStan\BetterReflection\NodeCompiler\CompilerContext $context)
    {
        assert($node->var instanceof Node\Expr\ClassConstFetch);
        assert($node->var->class instanceof Node\Name);
        $className = $this->resolveClassName($node->var->class->toString(), $context);
        $class = $context->getReflector()->reflectClass($className);
        if (!$class instanceof ReflectionEnum) {
            throw \PHPStan\BetterReflection\NodeCompiler\Exception\UnableToCompileNode::becauseOfInvalidEnumCasePropertyFetch($context, $class, $node);
        }
        assert($node->var->name instanceof Node\Identifier);
        $caseName = $node->var->name->name;
        $case = $class->getCase($caseName);
        if ($case === null) {
            throw \PHPStan\BetterReflection\NodeCompiler\Exception\UnableToCompileNode::becauseOfInvalidEnumCasePropertyFetch($context, $class, $node);
        }
        assert($node->name instanceof Node\Identifier);
        switch ($node->name->toString()) {
            case 'value':
                return $case->getValue();
            case 'name':
                return $case->getName();
            default:
                throw \PHPStan\BetterReflection\NodeCompiler\Exception\UnableToCompileNode::becauseOfInvalidEnumCasePropertyFetch($context, $class, $node);
        }
    }
    private function resolveConstantName(Node\Expr\ConstFetch $constNode, \PHPStan\BetterReflection\NodeCompiler\CompilerContext $context) : string
    {
        $constantName = $constNode->name->toString();
        $namespace = $context->getNamespace() ?? '';
        if ($constNode->name->isUnqualified()) {
            $namespacedConstantName = sprintf('%s\\%s', $namespace, $constantName);
            if ($this->constantExists($namespacedConstantName, $context)) {
                return $namespacedConstantName;
            }
        }
        if ($this->constantExists($constantName, $context)) {
            return $constantName;
        }
        throw \PHPStan\BetterReflection\NodeCompiler\Exception\UnableToCompileNode::becauseOfNotFoundConstantReference($context, $constNode, $constantName);
    }
    private function constantExists(string $constantName, \PHPStan\BetterReflection\NodeCompiler\CompilerContext $context) : bool
    {
        if (defined($constantName)) {
            return \true;
        }
        try {
            $context->getReflector()->reflectConstant($constantName);
            return \true;
        } catch (IdentifierNotFound $exception) {
            return \false;
        }
    }
    /**
     * @return mixed
     */
    private function getConstantValue(Node\Expr\ConstFetch $node, ?string $constantName, \PHPStan\BetterReflection\NodeCompiler\CompilerContext $context)
    {
        // It's not resolved when constant value is expression
        // @infection-ignore-all Assignment, AssignCoalesce: There's no difference, ??= is just optimization
        $constantName ??= $this->resolveConstantName($node, $context);
        if (defined($constantName)) {
            return constant($constantName);
        }
        return $context->getReflector()->reflectConstant($constantName)->getValue();
    }
    private function resolveClassConstantName(Node\Expr\ClassConstFetch $node, \PHPStan\BetterReflection\NodeCompiler\CompilerContext $context) : string
    {
        assert($node->name instanceof Node\Identifier);
        $constantName = $node->name->name;
        assert($node->class instanceof Node\Name);
        $className = $node->class->toString();
        return sprintf('%s::%s', $this->resolveClassName($className, $context), $constantName);
    }
    /**
     * @return mixed
     */
    private function getClassConstantValue(Node\Expr\ClassConstFetch $node, ?string $classConstantName, \PHPStan\BetterReflection\NodeCompiler\CompilerContext $context)
    {
        // It's not resolved when constant value is expression
        // @infection-ignore-all Assignment, AssignCoalesce: There's no difference, ??= is just optimization
        $classConstantName ??= $this->resolveClassConstantName($node, $context);
        [$className, $constantName] = explode('::', $classConstantName);
        assert($constantName !== '');
        if ($constantName === 'class') {
            return $className;
        }
        $classContext = $context->getClass();
        $classReflection = $classContext !== null && $classContext->getName() === $className ? $classContext : $context->getReflector()->reflectClass($className);
        if ($classReflection instanceof ReflectionEnum) {
            if ($classReflection->hasCase($constantName)) {
                return constant(sprintf('%s::%s', $className, $constantName));
            }
        }
        if ($classReflection instanceof ReflectionEnum) {
            if ($classReflection->hasCase($constantName)) {
                throw \PHPStan\BetterReflection\NodeCompiler\Exception\UnableToCompileNode::becauseOfValueIsEnum($context, $classReflection, $node);
            }
        }
        $reflectionConstant = $classReflection->getConstant($constantName);
        if (!$reflectionConstant instanceof ReflectionClassConstant) {
            throw \PHPStan\BetterReflection\NodeCompiler\Exception\UnableToCompileNode::becauseOfNotFoundClassConstantReference($context, $classReflection, $node);
        }
        return $reflectionConstant->getValue();
    }
    private function compileNew(Node\Expr\New_ $node, \PHPStan\BetterReflection\NodeCompiler\CompilerContext $context) : object
    {
        assert($node->class instanceof Node\Name);
        /** @psalm-var class-string $className */
        $className = $node->class->toString();
        if (!class_exists($className)) {
            throw \PHPStan\BetterReflection\NodeCompiler\Exception\UnableToCompileNode::becauseOfClassCannotBeLoaded($context, $node, $className);
        }
        $arguments = [];
        foreach ($node->args as $argNo => $arg) {
            $arguments[(($argName = $arg->name) ? $argName->toString() : null) ?? $argNo] = $this($arg->value, $context)->value;
        }
        return new $className(...$arguments);
    }
    /**
     * Compile a __DIR__ node
     */
    private function compileDirConstant(\PHPStan\BetterReflection\NodeCompiler\CompilerContext $context, Node\Scalar\MagicConst\Dir $node) : string
    {
        $fileName = $context->getFileName();
        if ($fileName === null) {
            throw \PHPStan\BetterReflection\NodeCompiler\Exception\UnableToCompileNode::becauseOfMissingFileName($context, $node);
        }
        if (!is_file($fileName)) {
            throw \PHPStan\BetterReflection\NodeCompiler\Exception\UnableToCompileNode::becauseOfNonexistentFile($context, $fileName);
        }
        return dirname($fileName);
    }
    /**
     * Compile a __FILE__ node
     */
    private function compileFileConstant(\PHPStan\BetterReflection\NodeCompiler\CompilerContext $context, Node\Scalar\MagicConst\File $node) : string
    {
        $fileName = $context->getFileName();
        if ($fileName === null) {
            throw \PHPStan\BetterReflection\NodeCompiler\Exception\UnableToCompileNode::becauseOfMissingFileName($context, $node);
        }
        if (!is_file($fileName)) {
            throw \PHPStan\BetterReflection\NodeCompiler\Exception\UnableToCompileNode::becauseOfNonexistentFile($context, $fileName);
        }
        return $fileName;
    }
    /**
     * Compiles magic constant __CLASS__
     */
    private function compileClassConstant(\PHPStan\BetterReflection\NodeCompiler\CompilerContext $context) : string
    {
        return (($nullsafeVariable2 = $context->getClass()) ? $nullsafeVariable2->getName() : null) ?? '';
    }
    private function resolveClassName(string $className, \PHPStan\BetterReflection\NodeCompiler\CompilerContext $context) : string
    {
        if ($className !== 'self' && $className !== 'static' && $className !== 'parent') {
            return $className;
        }
        $classContext = $context->getClass();
        assert($classContext !== null);
        if ($className !== 'parent') {
            return $classContext->getName();
        }
        $parentClass = $classContext->getParentClass();
        assert($parentClass instanceof ReflectionClass);
        return $parentClass->getName();
    }
}
