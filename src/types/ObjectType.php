<?php declare(strict_types=1);

namespace sndsgd\schema\types;

use LogicException;
use sndsgd\schema\PropertyList;
use sndsgd\schema\RuleList;
use sndsgd\schema\Type;

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
    private array $defaults = [];

    public function __construct(
        string $name,
        string $description,
        RuleList $rules,
        PropertyList $properties,
        array $requiredProperties,
        array $defaults
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
        $this->setDefaults($defaults);
    }

    private function setRequiredProperties(string ...$names): void
    {
        $this->requiredProperties = array_flip($names);
    }

    private function isDefaultPossibleForType(
        Type $type,
        $default
    ): bool
    {
        if ($type instanceof ScalarType) {
            return true;
        }

        return 
            $type instanceof ArrayType
            && $default === []
        ;
    }

    private function setDefaults(array $defaults): void
    {
        $properties = $this->getProperties();

        foreach ($defaults as $key => $value) {
            if (!$properties->has($key)) {
                throw new \Exception(
                    "cannot set default value for undefined property '$key'",
                );
            }

            // only scalars and arrays with empty array defaults can have
            // default values
            $type = $properties->get($key)->getType();
            if (!$this->isDefaultPossibleForType($type, $value)) {
                throw new \Exception(
                    "cannot set default value for '$key'; " .
                    "only scalar and empty arrays are acceptable defaults",
                );
            }

            foreach ($type->getRules() as $rule) {
                try {
                    $rule->validate($value);
                } catch (\Throwable $ex) {
                    $message = sprintf(
                        "failed to set default value for '%s'; %s",
                        $key,
                        $ex->getMessage(),
                    );

                    throw new \Exception($message);
                }
            }

            // TODO need to make sure the value validates
            $this->defaults[$key] = $value;
        }
    }

    public function getDefaults()
    {
        return $this->defaults;
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
