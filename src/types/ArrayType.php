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
        if (is_string($doc["value"])) {
            $doc["value"] = ["type" => $doc["value"]];
        }

        return [
            $doc["type"],
            $doc["value"]["type"],
        ];
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
        return "array<" . $this->getValue()->getSignature() . ">";
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
