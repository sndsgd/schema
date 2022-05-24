<?php declare(strict_types=1);

namespace sndsgd\schema;

use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

class ReflectionUtil
{
    /**
     * ReflectionParameter->getType()->__toString() results in a different
     * value than gettype()
     */
    private const TYPE_NORMALIZE_MAP = [
        "int" => "integer",
        "double" => "float",
        "bool" => "boolean",
    ];

    public static function normalizeType(
        string $type,
    ): string {
        return self::TYPE_NORMALIZE_MAP[$type] ?? $type;
    }

    public static function getParameterTypes(
        ReflectionParameter $parameter,
    ): array {
        $types = [];
        $parameterType = $parameter->getType();
        if ($parameterType instanceof ReflectionNamedType) {
            $type = strval($parameterType->getName());
            $types[ReflectionUtil::normalizeType($type)] = true;
        } elseif ($parameterType instanceof ReflectionUnionType) {
            foreach ($parameterType->getTypes() as $subType) {
                $type = strval($subType->getName());
                $types[ReflectionUtil::normalizeType($type)] = true;
            }
        }

        if ($parameterType->allowsNull()) {
            $types["null"] = true;
        }

        ksort($types);

        return array_keys($types);
    }
}
