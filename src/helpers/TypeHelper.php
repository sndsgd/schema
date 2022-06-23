<?php declare(strict_types=1);

namespace sndsgd\schema\helpers;

use Exception;
use sndsgd\Arr;
use sndsgd\schema\DefinedRules;
use sndsgd\schema\DefinedTypes;
use sndsgd\schema\exceptions\InvalidTypeDefinitionException;
use sndsgd\schema\Property;
use sndsgd\schema\PropertyList;
use sndsgd\schema\RuleList;
use sndsgd\schema\Type;
use sndsgd\schema\types\ArrayType;
use sndsgd\schema\types\MapType;
use sndsgd\schema\types\ObjectType;
use sndsgd\schema\types\OneOfObjectType;
use sndsgd\schema\types\OneOfType;
use sndsgd\schema\types\ScalarType;

class TypeHelper
{
    /**
     * A map of schema types to the associated classnames
     *
     * @var array,string,string>
     */
    private const TYPE_TO_CLASSNAME = [
        "string" => ScalarType::class,
        "boolean" => ScalarType::class,
        "integer" => ScalarType::class,
        "float" => ScalarType::class,
        "array" => ArrayType::class,
        "object" => ObjectType::class,
        "map" => MapType::class,
        "oneof" => OneOfType::class,
        "oneofobject" => OneOfObjectType::class,
    ];

    // TODO this map is also defined in DefinedTypes
    private const TYPE_CLASSNAME_MAP = [
        "string" => ScalarType::BASE_STRING_CLASSNAME,
        "boolean" => ScalarType::BASE_BOOLEAN_CLASSNAME,
        "integer" => ScalarType::BASE_INTEGER_CLASSNAME,
        "float" => ScalarType::BASE_FLOAT_CLASSNAME,
        "array" => ArrayType::BASE_CLASSNAME,
        "object" => ObjectType::BASE_CLASSNAME,
        "map" => MapType::BASE_CLASSNAME,
        "oneof" => OneOfType::BASE_CLASSNAME,
        "oneofobject" => OneOfObjectType::BASE_CLASSNAME,
    ];

    public static function resolveFullTypeName(string $typeName): string
    {
        return self::TYPE_CLASSNAME_MAP[$typeName] ?? $typeName;
    }

    public static function resolveShortTypeName(string $typeName): string
    {
        $map = array_flip(self::TYPE_CLASSNAME_MAP);
        return $map[$typeName] ?? $typeName;
    }

    private $definedTypes;
    private $definedRules;

    public function __construct(
        DefinedTypes $definedTypes,
        DefinedRules $definedRules
    ) {
        $this->definedTypes = $definedTypes;
        $this->definedRules = $definedRules;
    }

    public function getDefinedTypes(): DefinedTypes
    {
        return $this->definedTypes;
    }

    public function getBaseTypeName(string $typeName): string
    {
        while (!DefinedTypes::isBaseType($typeName)) {
            if (!$this->definedTypes->hasType($typeName)) {
                return $typeName;
            }
            $typeName = $this->definedTypes->getType($typeName)->getParentName();
        }

        return $typeName;
    }

    // it is expected that the caller has context as to what the file,
    // doc index, and complete doc contain. so it can catch the exception
    // and report more detail.
    public function getDependenciesFromDoc(array $doc): array
    {
        $docType = $doc["type"] ?? "";
        if ($docType === "") {
            throw new InvalidTypeDefinitionException("missing required property 'type'");
        }

        if (!is_string($docType)) {
            throw new InvalidTypeDefinitionException("invalid type for property 'type'; expecting a string");
        }

        $baseTypeName = $this->getBaseTypeName($doc["type"]);
        $baseClass = self::TYPE_TO_CLASSNAME[$baseTypeName] ?? "";
        if ($baseClass === "") {
            throw new InvalidTypeDefinitionException(
                "failed to resolve base type for '$docType'",
            );
        }

        return $baseClass::getDependencies($doc);
    }

    public static function normalizeStringToTypeArray($value): array
    {
        return is_string($value) ? ["type" => $value] : $value;
    }

    public function rawDocToType(array $doc): Type
    {
        if (!isset($doc["name"])) {
            throw new InvalidTypeDefinitionException("missing required property 'name'");
        }

        if (!isset($doc["type"])) {
            throw new InvalidTypeDefinitionException("missing required property 'type'");
        }

        $parentName = self::resolveFullTypeName($doc["type"]);
        if (!$this->definedTypes->hasType($parentName)) {
            throw new Exception(
                "failed to resolve type for '$parentName'; provided name was "
                . $doc["type"],
            );
        }

        $parentType = $this->definedTypes->getType($doc["type"]);

        // shared properties for creating types
        $name = $doc["name"];
        $description = $doc["description"] ?? $parentType->getDescription();
        $rules = self::mergeRules(
            $this->definedRules,
            $parentType->getRules(),
            $doc["rules"] ?? [],
        );

        switch (get_class($parentType)) {
            case ArrayType::class:
                if (!isset($doc["value"])) {
                    throw new InvalidTypeDefinitionException("missing required key `value`");
                }

                if (is_string($doc["value"])) {
                    $doc["value"] = ["type" => $doc["value"]];
                }

                return new ArrayType(
                    $name,
                    $description,
                    $rules,
                    $this->createSubType($name, $doc["value"], "ArrayValue"),
                );
            case ObjectType::class:
                return new ObjectType(
                    $name,
                    $description,
                    $rules,
                    $this->createProperties(
                        $name,
                        $parentType->getProperties()->toArray(),
                        $doc["properties"] ?? [],
                    ),
                    array_values($doc["required"] ?? $parentType->getRequiredProperties()),
                    $doc["defaults"] ?? $parentType->getDefaults()
                );
            case MapType::class:

                $keyType = $this->createSubType($name, self::normalizeStringToTypeArray($doc["key"]), "MapKey");
                $valueType = $this->createSubType($name, self::normalizeStringToTypeArray($doc["value"]), "MapValue");

                if (!($keyType instanceof ScalarType)) {
                    throw new \Exception("'type' must be scalar");
                }

                return new MapType(
                    $name,
                    $description,
                    $rules,
                    $keyType,
                    $valueType,
                );
            case OneOfType::class:
                return new OneOfType(
                    $name,
                    $description,
                    ...$this->createOneOfTypes($name, $doc["types"]),
                );
            case OneOfObjectType::class:
                $types = $this->createOneOfTypes($name, $doc["types"]);

                return new OneOfObjectType(
                    $name,
                    $description,
                    $doc["key"],
                    array_combine(array_keys($doc["types"]), $types),
                );
            case ScalarType::class:
                return new ScalarType(
                    $name,
                    $description,
                    $rules,
                    $this->getBaseTypeName($doc["type"]),
                    $doc["default"] ?? null,
                );
        }

        throw new \Exception("unknown type " . get_class($parentType));
    }

    public function createTypeFromDoc(array $doc): Type
    {
        $doc = self::rewriteRawDoc($doc);

        try {
            return $this->rawDocToType($doc);
        } catch (\Throwable | \TypeError $ex) {
            $message = sprintf(
                "failed to create type from raw doc; %s:\n%s",
                $ex->getMessage(),
                yaml_emit($doc),
            );

            throw new \Exception($message, $ex->getCode(), $ex);
        }
    }

    private function createProperties(string $objectClassname, array $properties, array $docs)
    {
        foreach ($docs as $propertyName => $doc) {
            if (is_string($doc)) {
                $doc = ["type" => $doc];
            }

            if (array_keys($doc) === ["type"]) {
                // we can just use the type that has already been defined
                $propertyType = $this->definedTypes->getType($doc["type"]);
            } else {
                // we need to create a one off type because we cannot just use
                // the parent type.
                if (!isset($doc["name"])) {
                    $doc["name"] = sprintf(
                        "%s_%s",
                        $objectClassname,
                        ucfirst($propertyName),
                    );
                }

                $propertyType = $this->createTypeFromDoc($doc);
                $this->definedTypes->addType($propertyType);
            }

            try {
                $property = new Property($propertyName, $propertyType);
            } catch (\Throwable | \TypeError $ex) {
                throw new \Exception("failed to create property", 0, $ex);
            }

            $properties[] = $property;
        }

        return new PropertyList(...array_values($properties));
    }

    private function createSubType(
        string $parentClassname,
        array $doc,
        string $classSuffix
    ): Type {
        // if only a type is listed, we can just use that type.
        if (
            array_keys($doc) === ["type"]
            && !DefinedTypes::isBaseType($doc["type"])
        ) {
            return $this->definedTypes->getType($doc["type"]);
        }

        if (!isset($doc["name"])) {
            $doc["name"] = sprintf("%s_%s", $parentClassname, $classSuffix);
        }

        $valueType = $this->createTypeFromDoc($doc);
        $this->definedTypes->addType($valueType);
        return $valueType;
    }

    private function createOneOfTypes(string $parentName, array $types): array
    {
        $ret = [];
        foreach ($types as $doc) {
            if (!is_string($doc)) {
                throw new Exception("'oneof' types must be referenced as strings");
            }

            $ret[] = $this->definedTypes->getType($doc);
        }

        return $ret;
    }

    private static function mergeRules(
        DefinedRules $definedRules,
        RuleList $existingRules,
        array $docRules
    ): RuleList {
        $rules = $existingRules->toArray();

        // add all the additional rules to the initial list
        foreach ($docRules as $ruleInfo) {
            $name = $ruleInfo["rule"] ?? "";
            if ($name === "") {
                throw new Exception("empty rule provided");
            }
            $args = Arr::without($ruleInfo, "rule");
            $rules[] = $definedRules->instantiateRule($name, $args);
        }

        return new RuleList(...$rules);
    }

    /**
     * Hack to make objects with `oneof`
     *
     * @param array $doc [description]
     * @return array
     */
    public static function rewriteRawDoc(array $doc): array
    {
        $type = $doc["type"] ?? "";
        if (
            $type !== "object"
            || isset($doc["properties"])
            || !isset($doc["oneof"])
        ) {
            return $doc;
        }

        // rewrite the thing to a oneofobject
        $doc["type"] = "oneofobject";
        foreach (["key", "types"] as $oneOfKey) {
            $doc[$oneOfKey] = $doc["oneof"][$oneOfKey];
        }
        unset($doc["oneof"]);

        echo yaml_emit($doc);

        return $doc;
    }
}
