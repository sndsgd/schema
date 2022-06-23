<?php declare(strict_types=1);

namespace sndsgd\schema\types;

use LogicException;
use sndsgd\schema\helpers\TypeHelper;
use sndsgd\schema\RuleList;

class ScalarType extends BaseType
{
    public const BASE_STRING_CLASSNAME = "sndsgd.types.StringType";
    public const BASE_BOOLEAN_CLASSNAME = "sndsgd.types.BooleanType";
    public const BASE_INTEGER_CLASSNAME = "sndsgd.types.IntegerType";
    public const BASE_FLOAT_CLASSNAME = "sndsgd.types.FloatType";

    public const STRING_DEFAULT = "";
    public const BOOLEAN_DEFAULT = false;
    public const INTEGER_DEFAULT = 0;
    public const FLOAT_DEFAULT = 0.0;

    private const DEFAULTS = [
        "string" => self::STRING_DEFAULT,
        "boolean" => self::STRING_DEFAULT,
        "integer" => self::INTEGER_DEFAULT,
        "float" => self::FLOAT_DEFAULT,
    ];

    /**
     * @inheritDoc
     */
    public static function getDependencies(array $doc): array
    {
        return [];
    }

    private string $parentName;
    private $default;

    public function __construct(
        string $name,
        string $description,
        RuleList $rules,
        string $parentName,
        $default = null
    ) {
        parent::__construct($name, $description, $rules);

        // verify the default
        $shortTypeName = self::getTypeName($name, $parentName);

        if ($default !== null) {
            // we use 'float', php uses 'double'
            $defaultType = gettype($default);
            if ($defaultType === "double") {
                $defaultType = "float";
            }

            if ($defaultType !== $shortTypeName) {
                throw new LogicException(
                    "default type '$defaultType' does not match type '$shortTypeName' for '$name'",
                );
            }
        } else {
            $default = self::DEFAULTS[$shortTypeName];
        }

        $this->parentName = $parentName;
        $this->default = $default;
    }

    private static function getTypeName(string $name, string $parentName): string
    {
        $shortTypeName = TypeHelper::resolveShortTypeName($parentName);
        if ($shortTypeName !== "") {
            return $shortTypeName;
        }

        switch ($name) {
            case self::BASE_STRING_CLASSNAME:
                return "string";
            case self::BASE_BOOLEAN_CLASSNAME:
                return "boolean";
            case self::BASE_INTEGER_CLASSNAME:
                return "integer";
            case self::BASE_FLOAT_CLASSNAME:
                return "float";
        }

        throw new LogicException("failed to determine type name for $name");
    }

    public function getParentName(): string
    {
        return $this->parentName;
    }

    public function getSignature(): string
    {
        $name = $this->getParentName() ?: $this->getName();
        return TypeHelper::resolveShortTypeName($name);
    }
}
