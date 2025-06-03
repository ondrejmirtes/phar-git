<?php

declare (strict_types=1);
namespace PHPStan\Type;

use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use function count;
/** @api */
class BenevolentUnionType extends \PHPStan\Type\UnionType
{
    /**
     * @api
     * @param Type[] $types
     */
    public function __construct(array $types, bool $normalized = \false)
    {
        parent::__construct($types, $normalized);
    }
    public function filterTypes(callable $filterCb): \PHPStan\Type\Type
    {
        $result = parent::filterTypes($filterCb);
        if (!$result instanceof self && $result instanceof \PHPStan\Type\UnionType) {
            return \PHPStan\Type\TypeUtils::toBenevolentUnion($result);
        }
        return $result;
    }
    public function describe(\PHPStan\Type\VerbosityLevel $level): string
    {
        return '(' . parent::describe($level) . ')';
    }
    protected function unionTypes(callable $getType): \PHPStan\Type\Type
    {
        $resultTypes = [];
        foreach ($this->getTypes() as $type) {
            $result = $getType($type);
            if ($result instanceof \PHPStan\Type\ErrorType) {
                continue;
            }
            $resultTypes[] = $result;
        }
        if (count($resultTypes) === 0) {
            return new \PHPStan\Type\ErrorType();
        }
        return \PHPStan\Type\TypeUtils::toBenevolentUnion(\PHPStan\Type\TypeCombinator::union(...$resultTypes));
    }
    protected function pickFromTypes(callable $getValues, callable $criteria): array
    {
        $values = [];
        foreach ($this->getTypes() as $type) {
            $innerValues = $getValues($type);
            if ($innerValues === [] && $criteria($type)) {
                return [];
            }
            foreach ($innerValues as $innerType) {
                $values[] = $innerType;
            }
        }
        return $values;
    }
    public function getOffsetValueType(\PHPStan\Type\Type $offsetType): \PHPStan\Type\Type
    {
        $types = [];
        foreach ($this->getTypes() as $innerType) {
            $valueType = $innerType->getOffsetValueType($offsetType);
            if ($valueType instanceof \PHPStan\Type\ErrorType) {
                continue;
            }
            $types[] = $valueType;
        }
        if (count($types) === 0) {
            return new \PHPStan\Type\ErrorType();
        }
        return \PHPStan\Type\TypeUtils::toBenevolentUnion(\PHPStan\Type\TypeCombinator::union(...$types));
    }
    protected function unionResults(callable $getResult): TrinaryLogic
    {
        return TrinaryLogic::createNo()->lazyOr($this->getTypes(), $getResult);
    }
    public function isAcceptedBy(\PHPStan\Type\Type $acceptingType, bool $strictTypes): \PHPStan\Type\AcceptsResult
    {
        $result = \PHPStan\Type\AcceptsResult::createNo();
        foreach ($this->getTypes() as $innerType) {
            $result = $result->or($acceptingType->accepts($innerType, $strictTypes));
        }
        return $result;
    }
    public function inferTemplateTypes(\PHPStan\Type\Type $receivedType): TemplateTypeMap
    {
        $types = TemplateTypeMap::createEmpty();
        foreach ($this->getTypes() as $type) {
            $types = $types->benevolentUnion($type->inferTemplateTypes($receivedType));
        }
        return $types;
    }
    public function inferTemplateTypesOn(\PHPStan\Type\Type $templateType): TemplateTypeMap
    {
        $types = TemplateTypeMap::createEmpty();
        foreach ($this->getTypes() as $type) {
            $types = $types->benevolentUnion($templateType->inferTemplateTypes($type));
        }
        return $types;
    }
    public function traverse(callable $cb): \PHPStan\Type\Type
    {
        $types = [];
        $changed = \false;
        foreach ($this->getTypes() as $type) {
            $newType = $cb($type);
            if ($type !== $newType) {
                $changed = \true;
            }
            $types[] = $newType;
        }
        if ($changed) {
            return \PHPStan\Type\TypeUtils::toBenevolentUnion(\PHPStan\Type\TypeCombinator::union(...$types));
        }
        return $this;
    }
    public function traverseSimultaneously(\PHPStan\Type\Type $right, callable $cb): \PHPStan\Type\Type
    {
        $types = [];
        $changed = \false;
        if (!$right instanceof \PHPStan\Type\UnionType) {
            return $this;
        }
        if (count($this->getTypes()) !== count($right->getTypes())) {
            return $this;
        }
        foreach ($this->getSortedTypes() as $i => $leftType) {
            $rightType = $right->getSortedTypes()[$i];
            $newType = $cb($leftType, $rightType);
            if ($leftType !== $newType) {
                $changed = \true;
            }
            $types[] = $newType;
        }
        if ($changed) {
            return \PHPStan\Type\TypeUtils::toBenevolentUnion(\PHPStan\Type\TypeCombinator::union(...$types));
        }
        return $this;
    }
}
