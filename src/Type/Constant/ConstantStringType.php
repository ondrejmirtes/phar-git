<?php

declare (strict_types=1);
namespace PHPStan\Type\Constant;

use _PHPStan_checksum\Nette\Utils\RegexpException;
use _PHPStan_checksum\Nette\Utils\Strings;
use PhpParser\Node\Name;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\Php\PhpVersion;
use PHPStan\PhpDocParser\Ast\ConstExpr\ConstExprStringNode;
use PHPStan\PhpDocParser\Ast\Type\ConstTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\Reflection\Callables\FunctionCallableVariant;
use PHPStan\Reflection\ClassConstantReflection;
use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\InaccessibleMethod;
use PHPStan\Reflection\PhpVersionStaticAccessor;
use PHPStan\Reflection\ReflectionProviderStaticAccessor;
use PHPStan\Reflection\TrivialParametersAcceptor;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryLiteralStringType;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\CompoundType;
use PHPStan\Type\ConstantScalarType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\GeneralizePrecision;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StaticType;
use PHPStan\Type\StringType;
use PHPStan\Type\Traits\ConstantScalarTypeTrait;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\VerbosityLevel;
use function addcslashes;
use function in_array;
use function is_float;
use function is_int;
use function is_numeric;
use function key;
use function strlen;
use function strtolower;
use function strtoupper;
use function substr;
use function substr_count;
/** @api */
class ConstantStringType extends StringType implements ConstantScalarType
{
    private string $value;
    private bool $isClassString;
    private const DESCRIBE_LIMIT = 20;
    use ConstantScalarTypeTrait;
    use \PHPStan\Type\Constant\ConstantScalarToBooleanTrait;
    private ?ObjectType $objectType = null;
    private ?Type $arrayKeyType = null;
    /** @api */
    public function __construct(string $value, bool $isClassString = \false)
    {
        $this->value = $value;
        $this->isClassString = $isClassString;
        parent::__construct();
    }
    public function getValue(): string
    {
        return $this->value;
    }
    public function getConstantStrings(): array
    {
        return [$this];
    }
    public function isClassString(): TrinaryLogic
    {
        if ($this->isClassString) {
            return TrinaryLogic::createYes();
        }
        $reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
        return TrinaryLogic::createFromBoolean($reflectionProvider->hasClass($this->value));
    }
    public function getClassStringObjectType(): Type
    {
        if ($this->isClassString()->yes()) {
            return new ObjectType($this->value);
        }
        return new ErrorType();
    }
    public function getObjectTypeOrClassStringObjectType(): Type
    {
        return $this->getClassStringObjectType();
    }
    public function describe(VerbosityLevel $level): string
    {
        return $level->handle(static fn(): string => 'string', function (): string {
            $value = $this->value;
            if (!$this->isClassString) {
                try {
                    $value = Strings::truncate($value, self::DESCRIBE_LIMIT);
                } catch (RegexpException $e) {
                    $value = substr($value, 0, self::DESCRIBE_LIMIT) . "…";
                }
            }
            return self::export($value);
        }, fn(): string => self::export($this->value));
    }
    private function export(string $value): string
    {
        $escapedValue = addcslashes($value, "\x00..\x1f");
        if ($escapedValue !== $value) {
            return '"' . addcslashes($value, "\x00..\x1f\\\"") . '"';
        }
        return "'" . addcslashes($value, '\\\'') . "'";
    }
    public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
    {
        if ($type instanceof GenericClassStringType) {
            $genericType = $type->getGenericType();
            if ($genericType instanceof MixedType) {
                return IsSuperTypeOfResult::createMaybe();
            }
            if ($genericType instanceof StaticType) {
                $genericType = $genericType->getStaticObjectType();
            }
            // We are transforming constant class-string to ObjectType. But we need to filter out
            // an uncertainty originating in possible ObjectType's class subtypes.
            $objectType = $this->getObjectType();
            // Do not use TemplateType's isSuperTypeOf handling directly because it takes ObjectType
            // uncertainty into account.
            if ($genericType instanceof TemplateType) {
                $isSuperType = $genericType->getBound()->isSuperTypeOf($objectType);
            } else {
                $isSuperType = $genericType->isSuperTypeOf($objectType);
            }
            // Explicitly handle the uncertainty for Yes & Maybe.
            if ($isSuperType->yes()) {
                return IsSuperTypeOfResult::createMaybe();
            }
            return IsSuperTypeOfResult::createNo();
        }
        if ($type instanceof ClassStringType) {
            return $this->isClassString()->yes() ? IsSuperTypeOfResult::createMaybe() : IsSuperTypeOfResult::createNo();
        }
        if ($type instanceof self) {
            return $this->value === $type->value ? IsSuperTypeOfResult::createYes() : IsSuperTypeOfResult::createNo();
        }
        if ($type instanceof parent) {
            return IsSuperTypeOfResult::createMaybe();
        }
        if ($type instanceof CompoundType) {
            return $type->isSubTypeOf($this);
        }
        return IsSuperTypeOfResult::createNo();
    }
    public function isCallable(): TrinaryLogic
    {
        if ($this->value === '') {
            return TrinaryLogic::createNo();
        }
        $reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
        // 'my_function'
        if ($reflectionProvider->hasFunction(new Name($this->value), null)) {
            return TrinaryLogic::createYes();
        }
        // 'MyClass::myStaticFunction'
        $matches = Strings::match($this->value, '#^([a-zA-Z_\x7f-\xff\\\\][a-zA-Z0-9_\x7f-\xff\\\\]*)::([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\z#');
        if ($matches !== null) {
            if (!$reflectionProvider->hasClass($matches[1])) {
                return TrinaryLogic::createMaybe();
            }
            $phpVersion = PhpVersionStaticAccessor::getInstance();
            $classRef = $reflectionProvider->getClass($matches[1]);
            if ($classRef->hasMethod($matches[2])) {
                $method = $classRef->getMethod($matches[2], new OutOfClassScope());
                if (!$phpVersion->supportsCallableInstanceMethods() && !$method->isStatic()) {
                    return TrinaryLogic::createNo();
                }
                return TrinaryLogic::createYes();
            }
            if (!$classRef->isFinalByKeyword()) {
                return TrinaryLogic::createMaybe();
            }
            return TrinaryLogic::createNo();
        }
        return TrinaryLogic::createNo();
    }
    public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array
    {
        if ($this->value === '') {
            return [];
        }
        $reflectionProvider = ReflectionProviderStaticAccessor::getInstance();
        // 'my_function'
        $functionName = new Name($this->value);
        if ($reflectionProvider->hasFunction($functionName, null)) {
            $function = $reflectionProvider->getFunction($functionName, null);
            return FunctionCallableVariant::createFromVariants($function, $function->getVariants());
        }
        // 'MyClass::myStaticFunction'
        $matches = Strings::match($this->value, '#^([a-zA-Z_\x7f-\xff\\\\][a-zA-Z0-9_\x7f-\xff\\\\]*)::([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\z#');
        if ($matches !== null) {
            if (!$reflectionProvider->hasClass($matches[1])) {
                return [new TrivialParametersAcceptor()];
            }
            $classReflection = $reflectionProvider->getClass($matches[1]);
            if ($classReflection->hasMethod($matches[2])) {
                $method = $classReflection->getMethod($matches[2], $scope);
                if (!$scope->canCallMethod($method)) {
                    return [new InaccessibleMethod($method)];
                }
                return FunctionCallableVariant::createFromVariants($method, $method->getVariants());
            }
            if (!$classReflection->isFinalByKeyword()) {
                return [new TrivialParametersAcceptor()];
            }
        }
        throw new ShouldNotHappenException();
    }
    public function toNumber(): Type
    {
        if (is_numeric($this->value)) {
            $value = $this->value;
            $value = +$value;
            if (is_float($value)) {
                return new \PHPStan\Type\Constant\ConstantFloatType($value);
            }
            return new \PHPStan\Type\Constant\ConstantIntegerType($value);
        }
        return new ErrorType();
    }
    public function toAbsoluteNumber(): Type
    {
        return $this->toNumber()->toAbsoluteNumber();
    }
    public function toInteger(): Type
    {
        return new \PHPStan\Type\Constant\ConstantIntegerType((int) $this->value);
    }
    public function toFloat(): Type
    {
        return new \PHPStan\Type\Constant\ConstantFloatType((float) $this->value);
    }
    public function toArrayKey(): Type
    {
        if ($this->arrayKeyType !== null) {
            return $this->arrayKeyType;
        }
        /** @var int|string $offsetValue */
        $offsetValue = key([$this->value => null]);
        return $this->arrayKeyType = is_int($offsetValue) ? new \PHPStan\Type\Constant\ConstantIntegerType($offsetValue) : new \PHPStan\Type\Constant\ConstantStringType($offsetValue);
    }
    public function isString(): TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function isNumericString(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean(is_numeric($this->getValue()));
    }
    public function isNonEmptyString(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean($this->getValue() !== '');
    }
    public function isNonFalsyString(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean(!in_array($this->getValue(), ['', '0'], \true));
    }
    public function isLiteralString(): TrinaryLogic
    {
        return TrinaryLogic::createYes();
    }
    public function isLowercaseString(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean(strtolower($this->value) === $this->value);
    }
    public function isUppercaseString(): TrinaryLogic
    {
        return TrinaryLogic::createFromBoolean(strtoupper($this->value) === $this->value);
    }
    public function hasOffsetValueType(Type $offsetType): TrinaryLogic
    {
        if ($offsetType->isInteger()->yes()) {
            $strlen = strlen($this->value);
            $strLenType = IntegerRangeType::fromInterval(-$strlen, $strlen - 1);
            return $strLenType->isSuperTypeOf($offsetType)->result;
        }
        return parent::hasOffsetValueType($offsetType);
    }
    public function getOffsetValueType(Type $offsetType): Type
    {
        if ($offsetType->isInteger()->yes()) {
            $strlen = strlen($this->value);
            $strLenType = IntegerRangeType::fromInterval(-$strlen, $strlen - 1);
            if ($offsetType instanceof \PHPStan\Type\Constant\ConstantIntegerType) {
                if ($strLenType->isSuperTypeOf($offsetType)->yes()) {
                    return new self($this->value[$offsetType->getValue()]);
                }
                return new ErrorType();
            }
            $intersected = TypeCombinator::intersect($strLenType, $offsetType);
            if ($intersected instanceof IntegerRangeType) {
                $finiteTypes = $intersected->getFiniteTypes();
                if ($finiteTypes === []) {
                    return parent::getOffsetValueType($offsetType);
                }
                $chars = [];
                foreach ($finiteTypes as $constantInteger) {
                    $chars[] = new self($this->value[$constantInteger->getValue()]);
                }
                if (!$strLenType->isSuperTypeOf($offsetType)->yes()) {
                    $chars[] = new self('');
                }
                return TypeCombinator::union(...$chars);
            }
        }
        return parent::getOffsetValueType($offsetType);
    }
    public function setOffsetValueType(?Type $offsetType, Type $valueType, bool $unionValues = \true): Type
    {
        $valueStringType = $valueType->toString();
        if ($valueStringType instanceof ErrorType) {
            return new ErrorType();
        }
        if ($offsetType instanceof \PHPStan\Type\Constant\ConstantIntegerType && $valueStringType instanceof \PHPStan\Type\Constant\ConstantStringType) {
            $value = $this->value;
            $offsetValue = $offsetType->getValue();
            if ($offsetValue < 0) {
                return new ErrorType();
            }
            $stringValue = $valueStringType->getValue();
            if (strlen($stringValue) !== 1) {
                return new ErrorType();
            }
            $value[$offsetValue] = $stringValue;
            return new self($value);
        }
        return parent::setOffsetValueType($offsetType, $valueType);
    }
    public function setExistingOffsetValueType(Type $offsetType, Type $valueType): Type
    {
        return parent::setOffsetValueType($offsetType, $valueType);
    }
    public function append(self $otherString): self
    {
        return new self($this->getValue() . $otherString->getValue());
    }
    public function generalize(GeneralizePrecision $precision): Type
    {
        if ($this->isClassString) {
            if ($precision->isMoreSpecific()) {
                return new ClassStringType();
            }
            return new StringType();
        }
        if ($this->getValue() !== '' && $precision->isMoreSpecific()) {
            $accessories = [new StringType(), new AccessoryLiteralStringType()];
            if (is_numeric($this->getValue())) {
                $accessories[] = new AccessoryNumericStringType();
            }
            if ($this->getValue() !== '0') {
                $accessories[] = new AccessoryNonFalsyStringType();
            } else {
                $accessories[] = new AccessoryNonEmptyStringType();
            }
            if (strtolower($this->getValue()) === $this->getValue()) {
                $accessories[] = new AccessoryLowercaseStringType();
            }
            if (strtoupper($this->getValue()) === $this->getValue()) {
                $accessories[] = new AccessoryUppercaseStringType();
            }
            return new IntersectionType($accessories);
        }
        if ($precision->isMoreSpecific()) {
            return new IntersectionType([new StringType(), new AccessoryLiteralStringType()]);
        }
        return new StringType();
    }
    public function getSmallerType(PhpVersion $phpVersion): Type
    {
        $subtractedTypes = [new \PHPStan\Type\Constant\ConstantBooleanType(\true), IntegerRangeType::createAllGreaterThanOrEqualTo((float) $this->value)];
        if ($this->value === '') {
            $subtractedTypes[] = new NullType();
            $subtractedTypes[] = new StringType();
        }
        if (!(bool) $this->value) {
            $subtractedTypes[] = new \PHPStan\Type\Constant\ConstantBooleanType(\false);
        }
        return TypeCombinator::remove(new MixedType(), TypeCombinator::union(...$subtractedTypes));
    }
    public function getSmallerOrEqualType(PhpVersion $phpVersion): Type
    {
        $subtractedTypes = [IntegerRangeType::createAllGreaterThan((float) $this->value)];
        if (!(bool) $this->value) {
            $subtractedTypes[] = new \PHPStan\Type\Constant\ConstantBooleanType(\true);
        }
        return TypeCombinator::remove(new MixedType(), TypeCombinator::union(...$subtractedTypes));
    }
    public function getGreaterType(PhpVersion $phpVersion): Type
    {
        $subtractedTypes = [new \PHPStan\Type\Constant\ConstantBooleanType(\false), IntegerRangeType::createAllSmallerThanOrEqualTo((float) $this->value)];
        if ((bool) $this->value) {
            $subtractedTypes[] = new \PHPStan\Type\Constant\ConstantBooleanType(\true);
        }
        return TypeCombinator::remove(new MixedType(), TypeCombinator::union(...$subtractedTypes));
    }
    public function getGreaterOrEqualType(PhpVersion $phpVersion): Type
    {
        $subtractedTypes = [IntegerRangeType::createAllSmallerThan((float) $this->value)];
        if ((bool) $this->value) {
            $subtractedTypes[] = new \PHPStan\Type\Constant\ConstantBooleanType(\false);
        }
        return TypeCombinator::remove(new MixedType(), TypeCombinator::union(...$subtractedTypes));
    }
    public function canAccessConstants(): TrinaryLogic
    {
        return $this->isClassString();
    }
    public function hasConstant(string $constantName): TrinaryLogic
    {
        return $this->getObjectType()->hasConstant($constantName);
    }
    public function getConstant(string $constantName): ClassConstantReflection
    {
        return $this->getObjectType()->getConstant($constantName);
    }
    private function getObjectType(): ObjectType
    {
        return $this->objectType ??= new ObjectType($this->value);
    }
    public function toPhpDocNode(): TypeNode
    {
        if (substr_count($this->value, "\n") > 0) {
            return $this->generalize(GeneralizePrecision::moreSpecific())->toPhpDocNode();
        }
        return new ConstTypeNode(new ConstExprStringNode($this->value, ConstExprStringNode::SINGLE_QUOTED));
    }
}
