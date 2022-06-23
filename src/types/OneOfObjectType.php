<?php declare(strict_types=1);

namespace sndsgd\schema\types;

use Exception;
use sndsgd\schema\RuleList;
use sndsgd\schema\rules\ObjectRule;

/**
 * name: sndsgd.model.Attribute
 * description: A model attribute
 * key: type
 * types:
 *   string: sndsgd.model.StringAttribute
 *   integer: sndsgd.model.IntegerAttribute
 *   float: sndsgd.model.FloatAttribute
 */
class OneOfObjectType extends BaseType
{
    public const BASE_CLASSNAME = "sndsgd.types.OneOfObjectType";

    /**
     * @inheritDoc
     */
    public static function getDependencies(array $doc): array
    {
        return array_values($doc["types"]);
    }

    /**
     * @var string The key that each object contains and can be used to determine type
     */
    private string $key;

    /**
     * @var array<string,ObjectType>
     */
    private array $typeMap;

    public function __construct(
        string $name,
        string $description,
        string $key,
        array $typeMap
    ) {
        if ($name !== self::BASE_CLASSNAME && count($typeMap) < 2) {
            throw new Exception(
                "a 'oneofobject' must be defined with at least two types",
            );
        }

        $seenTypes = [];
        foreach ($typeMap as $keyValue => $type) {
            if (!($type instanceof ObjectType)) {
                throw new Exception(
                    "invalid value for '$keyValue'; expecting an object type",
                );
            }

            // the property that we use to determine type must be required
            if (!$type->isPropertyRequired($key)) {
                throw new Exception(
                    "failed to add '$keyValue'; property '$key' must be required",
                );
            }

            // the object must have the property that we use to determine
            // which type to use
            if (!$type->getProperties()->has($key)) {
                throw new Exception(
                    "failed to add '$keyValue'; missing property '$key' for oneof",
                );
            }

            $typeName = $type->getName();
            if (isset($seenTypes[$typeName])) {
                throw new Exception(
                    "failed to add '$keyValue'; the type '$typeName' is already in use",
                );
            }

            $seenTypes[$typeName] = true;
            $this->typeMap[$keyValue] = $type;
        }

        // TODO this needs to indicate that the value should be oneof ...
        $rules = new RuleList(new ObjectRule());

        parent::__construct($name, $description, $rules);
        $this->key = $key;
    }

    public function getParentName(): string
    {
        return $this->getName() === self::BASE_CLASSNAME ? "" : self::BASE_CLASSNAME;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getTypeMap(): array
    {
        return $this->typeMap;
    }

    public function getSignature(): string
    {
        $signatures = [];
        foreach ($this->getTypeMap() as $type) {
            $signatures[$type->getSignature()] = true;
        }
        return implode("|", array_keys($signatures));
    }
}
