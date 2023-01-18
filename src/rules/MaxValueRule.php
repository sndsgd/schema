<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use InvalidArgumentException;
use sndsgd\schema\exceptions\RuleValidationException;
use sndsgd\schema\Rule;
use sndsgd\yaml\Callback as YamlCallback;
use UnexpectedValueException;

final class MaxValueRule implements Rule, YamlCallback
{
    public static function getName(): string
    {
        return "maxValue";
    }

    public static function getAcceptableTypes(): array
    {
        return ["integer", "float"];
    }

    public static function getYamlCallbackTag(): string
    {
        return "!rule/max";
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

        if (!self::isValidMaxValue($value)) {
            throw new UnexpectedValueException(
                "the '$tag' can only be used with a numeric value",
            );
        }

        return [
            "rule" => self::getName(),
            "maxValue" => $value,
        ];
    }

    private static function isValidMaxValue($value): bool
    {
        if (is_int($value) || is_float($value)) {
            return true;
        }

        return
            is_string($value)
            && $value !== ""
            && preg_match("/^-*\d*\.?\d*$/", $value)
        ;
    }

    public readonly string $maxValue;
    private string $summary;
    private string $description;

    public function __construct(
        $maxValue = 0,
        string $summary = "max:%s",
        string $description = "must be less than or equal to %s",
    ) {
        if (!self::isValidMaxValue($maxValue)) {
            throw new InvalidArgumentException("'maxValue' must be numeric");
        }

        $this->maxValue = strval($maxValue);
        $this->summary = $summary;
        $this->description = $description;
    }

    public function getSummary(): string
    {
        return sprintf($this->summary, $this->maxValue);
    }

    public function getDescription(): string
    {
        return sprintf($this->description, $this->maxValue);
    }

    public function validate($value, string $path = "$")
    {
        if ($value <= $this->maxValue) {
            return $value;
        }

        throw new RuleValidationException(
            $path,
            $this->getDescription(),
        );
    }
}
