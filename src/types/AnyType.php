<?php declare(strict_types=1);

namespace sndsgd\schema\types;

class AnyType extends BaseType
{
    public const BASE_CLASSNAME = "sndsgd.types.AnyType";

    public static function getDependencies(array $doc): array
    {
        return [];
    }

    public function getParentName(): string
    {
        return "";
    }

    public function getSignature(): string
    {
        return "*";
    }
}
