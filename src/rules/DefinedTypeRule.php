<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use LogicException;
use sndsgd\schema\DefinedTypes;
use sndsgd\schema\exceptions\RuleValidationException;
use sndsgd\schema\Rule;
use sndsgd\schema\NamedRule;
use sndsgd\yaml\Callback as YamlCallback;
use UnexpectedValueException;

final class DefinedTypeRule implements Rule, NamedRule, YamlCallback
{
    public static function getName(): string
    {
        return "definedType";
    }

    public static function getAcceptableTypes(): array
    {
        return ["string"];
    }

    public static function getYamlCallbackTag(): string
    {
        return "!rule/definedType";
    }

    public static function executeYamlCallback(
        string $name,
        $value,
        int $flags,
        $context,
    ) {
        $tag = self::getYamlCallbackTag();

        if ($name !== $tag) {
            throw new UnexpectedValueException(
                "yaml tag name must be '$tag'",
            );
        }

        if ($value !== "") {
            throw new LogicException(
                "the '$tag' yaml callback cannot be used with a value",
            );
        }

        return [
            "rule" => self::getName(),
        ];
    }

    public function __construct(
        private string $summary = "isDefined",
        private string $description = "must be a defined type",
    ) {}

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function validate($value, string $path = "$"): string
    {
        if (DefinedTypes::getInstance()->hasType($value)) {
            return $value;
        }

        throw new RuleValidationException(
            $path,
            "'$value' is undefined",
        );
    }
}
