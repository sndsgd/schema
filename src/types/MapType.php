<?php declare(strict_types=1);

namespace sndsgd\schema\types;

use sndsgd\schema\RuleList;
use sndsgd\schema\Type;

class MapType extends BaseType
{
    public const BASE_CLASSNAME = "sndsgd.types.MapType";

    /**
     * @inheritDoc
     */
    public static function getDependencies(array $doc): array
    {
        return [
            $doc["key"]["type"] ?? $doc["key"],
            $doc["value"]["type"] ?? $doc["value"],
        ];
    }

    private ScalarType $keyType;
    private Type $valueType;

    public function __construct(
        string $name,
        string $description,
        RuleList $rules,
        ScalarType $keyType,
        Type $valueType
    ) {
        parent::__construct($name, $description, $rules);
        $this->keyType = $keyType;
        $this->valueType = $valueType;
    }

    public function getParentName(): string
    {
        return $this->getName() === self::BASE_CLASSNAME ? "" : self::BASE_CLASSNAME;
    }

    public function getKeyType(): Type
    {
        return $this->keyType;
    }

    public function getValueType(): Type
    {
        return $this->valueType;
    }

    public function getSignature(): string
    {
        return sprintf(
            "map<%s,%s>",
            $this->getKeyType()->getSignature(),
            $this->getValueType()->getSignature(),
        );
    }
}
