<?php declare(strict_types=1);

namespace sndsgd\schema\types;

use sndsgd\schema\helpers\TypeHelper;
use sndsgd\schema\RuleList;

class ScalarType extends BaseType
{
    public const BASE_STRING_CLASSNAME = "sndsgd.types.StringType";
    public const BASE_BOOLEAN_CLASSNAME = "sndsgd.types.BooleanType";
    public const BASE_INTEGER_CLASSNAME = "sndsgd.types.IntegerType";
    public const BASE_FLOAT_CLASSNAME = "sndsgd.types.FloatType";

    /**
     * @inheritDoc
     */
    public static function getDependencies(array $doc): array
    {
        return [];
    }

    private string $parentName;

    public function __construct(
        string $name,
        string $description,
        RuleList $rules,
        string $parentName,
    ) {
        parent::__construct($name, $description, $rules);
        $this->parentName = $parentName;
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
