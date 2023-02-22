<?php declare(strict_types=1);

namespace sndsgd\schema\types;

use JsonSerializable;
use sndsgd\schema\RuleList;
use sndsgd\schema\Type;

class ArrayType extends BaseType implements JsonSerializable
{
    public const BASE_CLASSNAME = "sndsgd.types.ArrayType";

    /**
     * @inheritDoc
     */
    public static function getDependencies(array $doc): array
    {
        // TODO currently this has to be extra safe because
        // we aren't validating the doc yet.
        $deps = [];
        if (array_key_exists("type", $doc) && is_string($doc["type"])) {
            $deps[] = $doc["type"];
        }

        if (array_key_exists("value", $doc)) {
            if (is_string($doc["value"])) {
                $deps[] = $doc["value"];
            } elseif (
                array_key_exists("type", $doc["value"])
                && is_string($doc["value"]["type"])
            ) {
                $deps[] = $doc["value"]["type"];
            }
        }

        return $deps;
    }

    private Type $value;

    public function __construct(
        string $name,
        string $description,
        RuleList $rules,
        Type $value,
    ) {
        parent::__construct($name, $description, $rules);
        $this->value = $value;
    }

    public function getParentName(): string
    {
        return $this->getName() === self::BASE_CLASSNAME ? "" : self::BASE_CLASSNAME;
    }

    public function getValue(): Type
    {
        return $this->value;
    }

    public function getSignature(): string
    {
        return sprintf(
            "array<%s>",
            $this->getValue()->getSignature(),
        );
    }

    // not sure why i'm adding this
    public function jsonSerialize(): array
    {
        return [
            "name" => $this->getName(),
            "description" => $this->getDescription(),
            "value" => $this->getValue()->getName(),
            "rules" => [],
        ];
    }
}
