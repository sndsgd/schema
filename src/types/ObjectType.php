<?php declare(strict_types=1);

namespace sndsgd\schema\types;

use LogicException;
use sndsgd\schema\PropertyList;
use sndsgd\schema\RuleList;

class ObjectType extends BaseType
{
    public const BASE_CLASSNAME = "sndsgd.types.ObjectType";

    /**
     * @inheritDoc
     */
    public static function getDependencies(array $doc): array
    {
        $ret = [$doc["type"]];
        foreach ($doc["properties"] as $property) {
            if (is_string($property)) {
                $property = ["type" => $property];
            }
            $ret[] = $property["type"];
        }
        return $ret;
    }

    private PropertyList $properties;
    private array $requiredProperties;

    public function __construct(
        string $name,
        string $description,
        RuleList $rules,
        PropertyList $properties,
        array $requiredProperties
    ) {
        // only allow the base object to be created without any properties
        if ($name !== self::BASE_CLASSNAME && count($properties) === 0) {
            throw new LogicException(
                "failed to define object '$name' without properties",
            );
        }

        parent::__construct($name, $description, $rules);
        $this->properties = $properties;
        $this->setRequiredProperties(...$requiredProperties);
    }

    private function setRequiredProperties(string ...$names): void
    {
        $this->requiredProperties = array_flip($names);
    }

    public function getDefault()
    {
        return null;
    }

    public function getParentName(): string
    {
        return $this->getName() === self::BASE_CLASSNAME ? "" : self::BASE_CLASSNAME;
    }

    public function getProperties(): \sndsgd\schema\PropertyList
    {
        return $this->properties;
    }

    public function isPropertyRequired(string $name): bool
    {
        return isset($this->requiredProperties[$name]);
    }

    public function getRequiredProperties(): array
    {
        return array_keys($this->requiredProperties);
    }

    public function getSignature(): string
    {
        $sigs = [];
        foreach ($this->getProperties()->toArray() as $property) {
            $sigs[$property->getType()->getSignature()] = true;
        }
        return sprintf(
            "object<string,%s>",
            implode("|", array_keys($sigs)),
        );
    }
}
