<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use LogicException;
use sndsgd\schema\exceptions\RuleValidationException;
use sndsgd\schema\Rule;
use sndsgd\schema\NamedRule;
use sndsgd\yaml\Callback as YamlCallback;
use UnexpectedValueException;

final class UniqueRule implements Rule, NamedRule, YamlCallback
{
    public static function getName(): string
    {
        return "unique";
    }

    public static function getAcceptableTypes(): array
    {
        return ["array"];
    }

    public static function getYamlCallbackTag(): string
    {
        return "!rule/unique";
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

    private string $summary;
    private string $description;

    public function __construct(
        string $summary = "unique",
        string $description = "all values must be unique",
    ) {
        $this->summary = $summary;
        $this->description = $description;
    }

    public function getSummary(): string
    {
        return $this->summary;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function validate($value, string $path = "$"): array
    {
        if (!is_array($value)) {
            throw new LogicException(
                "refusing to validate a non array",
            );
        }

        // TODO this does not handle objects correctly!

        if (count(array_unique($value)) === count($value))  {
            return $value;
        }

        throw new RuleValidationException(
            $path,
            $this->getDescription(),
        );
    }
}
