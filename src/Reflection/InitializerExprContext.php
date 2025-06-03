<?php

declare (strict_types=1);
namespace PHPStan\Reflection;

use PhpParser\Node\PropertyHook;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Function_;
use PHPStan\Analyser\Scope;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionFunction;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionParameter;
use PHPStan\BetterReflection\Reflection\ReflectionConstant;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\ShouldNotHappenException;
use function array_slice;
use function count;
use function explode;
use function implode;
use function sprintf;
/**
 * @api
 */
final class InitializerExprContext implements \PHPStan\Reflection\NamespaceAnswerer
{
    private ?string $file;
    /**
     * @var non-empty-string|null
     */
    private ?string $namespace;
    private ?string $className;
    private ?string $traitName;
    private ?string $function;
    private ?string $method;
    private ?string $property;
    /**
     * @param non-empty-string|null $namespace
     */
    private function __construct(?string $file, ?string $namespace, ?string $className, ?string $traitName, ?string $function, ?string $method, ?string $property)
    {
        $this->file = $file;
        $this->namespace = $namespace;
        $this->className = $className;
        $this->traitName = $traitName;
        $this->function = $function;
        $this->method = $method;
        $this->property = $property;
    }
    public static function fromScope(Scope $scope) : self
    {
        $function = $scope->getFunction();
        return new self($scope->getFile(), $scope->getNamespace(), $scope->isInClass() ? $scope->getClassReflection()->getName() : null, $scope->isInTrait() ? $scope->getTraitReflection()->getName() : null, $scope->isInAnonymousFunction() ? '{closure}' : ($function !== null ? $function->getName() : null), $scope->isInAnonymousFunction() ? '{closure}' : ($function instanceof \PHPStan\Reflection\MethodReflection ? sprintf('%s::%s', $function->getDeclaringClass()->getName(), $function->getName()) : ($function instanceof \PHPStan\Reflection\FunctionReflection ? $function->getName() : null)), $function instanceof PhpMethodFromParserNodeReflection && $function->isPropertyHook() ? $function->getHookedPropertyName() : null);
    }
    /**
     * @return non-empty-string|null
     */
    private static function parseNamespace(string $name) : ?string
    {
        $parts = explode('\\', $name);
        if (count($parts) > 1) {
            $ns = implode('\\', array_slice($parts, 0, -1));
            if ($ns === '') {
                throw new ShouldNotHappenException('Namespace cannot be empty.');
            }
            return $ns;
        }
        return null;
    }
    public static function fromClassReflection(\PHPStan\Reflection\ClassReflection $classReflection) : self
    {
        return self::fromClass($classReflection->getName(), $classReflection->getFileName());
    }
    public static function fromClass(string $className, ?string $fileName) : self
    {
        return new self($fileName, self::parseNamespace($className), $className, null, null, null, null);
    }
    public static function fromFunction(string $functionName, ?string $fileName) : self
    {
        return new self($fileName, self::parseNamespace($functionName), null, null, $functionName, $functionName, null);
    }
    public static function fromClassMethod(string $className, ?string $traitName, string $methodName, ?string $fileName) : self
    {
        return new self($fileName, self::parseNamespace($className), $className, $traitName, $methodName, sprintf('%s::%s', $className, $methodName), null);
    }
    public static function fromReflectionParameter(ReflectionParameter $parameter) : self
    {
        $declaringFunction = $parameter->getDeclaringFunction();
        if ($declaringFunction instanceof ReflectionFunction) {
            $file = $declaringFunction->getFileName();
            return new self($file === \false ? null : $file, self::parseNamespace($declaringFunction->getName()), null, null, $declaringFunction->getName(), $declaringFunction->getName(), null);
        }
        $file = $declaringFunction->getFileName();
        $betterReflection = $declaringFunction->getBetterReflection();
        return new self($file === \false ? null : $file, self::parseNamespace($betterReflection->getDeclaringClass()->getName()), $declaringFunction->getDeclaringClass()->getName(), $betterReflection->getDeclaringClass()->isTrait() ? $betterReflection->getDeclaringClass()->getName() : null, $declaringFunction->getName(), sprintf('%s::%s', $declaringFunction->getDeclaringClass()->getName(), $declaringFunction->getName()), null);
    }
    /**
     * @param \PhpParser\Node\Stmt\ClassMethod|\PhpParser\Node\Stmt\Function_|\PhpParser\Node\PropertyHook $function
     */
    public static function fromStubParameter(?string $className, string $stubFile, $function) : self
    {
        $namespace = null;
        if ($className !== null) {
            $namespace = self::parseNamespace($className);
        } else {
            if ($function instanceof Function_ && $function->namespacedName !== null) {
                $namespace = self::parseNamespace($function->namespacedName->toString());
            }
        }
        $functionName = null;
        $propertyName = null;
        if ($function instanceof Function_ && $function->namespacedName !== null) {
            $functionName = $function->namespacedName->toString();
        } elseif ($function instanceof ClassMethod) {
            $functionName = $function->name->toString();
        } elseif ($function instanceof PropertyHook) {
            $propertyName = $function->getAttribute('propertyName');
            $functionName = sprintf('$%s::%s', $propertyName, $function->name->toString());
        }
        $methodName = null;
        if ($function instanceof ClassMethod && $className !== null) {
            $methodName = sprintf('%s::%s', $className, $function->name->toString());
        } elseif ($function instanceof PropertyHook) {
            $propertyName = $function->getAttribute('propertyName');
            $methodName = sprintf('%s::$%s::%s', $className, $propertyName, $function->name->toString());
        } elseif ($function instanceof Function_ && $function->namespacedName !== null) {
            $methodName = $function->namespacedName->toString();
        }
        return new self($stubFile, $namespace, $className, null, $functionName, $methodName, $propertyName);
    }
    public static function fromGlobalConstant(ReflectionConstant $constant) : self
    {
        return new self($constant->getFileName(), $constant->getNamespaceName(), null, null, null, null, null);
    }
    public static function createEmpty() : self
    {
        return new self(null, null, null, null, null, null, null);
    }
    public function getFile() : ?string
    {
        return $this->file;
    }
    public function getClassName() : ?string
    {
        return $this->className;
    }
    public function getNamespace() : ?string
    {
        return $this->namespace;
    }
    public function getTraitName() : ?string
    {
        return $this->traitName;
    }
    public function getFunction() : ?string
    {
        return $this->function;
    }
    public function getMethod() : ?string
    {
        return $this->method;
    }
    public function getProperty() : ?string
    {
        return $this->property;
    }
}
