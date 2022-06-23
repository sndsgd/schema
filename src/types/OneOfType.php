<?php declare(strict_types=1);

namespace sndsgd\schema\types;

use Exception;
use sndsgd\schema\helpers\TypeHelper;
use sndsgd\schema\RuleList;
use sndsgd\schema\rules\OneOfRule;
use sndsgd\schema\Type;

class OneOfType extends BaseType
{
    public const BASE_CLASSNAME = "sndsgd.types.OneOfType";

    /**
     * @inheritDoc
     */
    public static function getDependencies(array $doc): array
    {
        $ret = [];
        foreach ($doc["types"] as $type) {
            $type = TypeHelper::normalizeStringToTypeArray($type);
            $ret[] = $type["type"];
        }
        return $ret;
    }

    /**
     * @var array<Type>
     */
    private array $types;

    public function __construct(
        string $name,
        string $description,
        Type ...$types
    ) {
        if ($name !== self::BASE_CLASSNAME && count($types) < 2) {
            throw new Exception(
                "a 'oneof' must be defined with at least two types",
            );
        }

        // TODO creating a rule list when it is not really needed
        $rules = [];
        foreach ($types as $type) {
            $rules[] = $type->getRules()->getTypeRule();
        }
        $rules = new RuleList(new OneOfRule($rules));

        // verify the types are unique
        $typeSignatures = [];
        foreach ($types as $type) {
            $fullName = $type->getName();
            $signature = $type->getSignature();

            $existingName = $typeSignatures[$signature] ?? "";
            if ($existingName !== "") {
                throw new Exception(
                    "failed to add '$fullName'; "
                    . "the type '$signature' is already defined by '$existingName'",
                );
            }

            $typeSignatures[$signature] = $fullName;
        }

        parent::__construct($name, $description, $rules);
        $this->types = $types;
    }

    public function getParentName(): string
    {
        return $this->getName() === self::BASE_CLASSNAME ? "" : self::BASE_CLASSNAME;
    }

    public function getTypes(): array
    {
        return $this->types;
    }

    public function getSignature(): string
    {
        $sigs = [];
        foreach ($this->getTypes() as $type) {
            $sigs[$type->getSignature()] = true;
        }
        return implode("|", array_keys($sigs));
    }
}
