<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use sndsgd\schema\Rule;
use sndsgd\yaml\Callback as YamlCallback;
use UnexpectedValueException;

final class MinValueRule implements Rule, YamlCallback
{
    public static function getName(): string
    {
        return "minValue";
    }

    public static function getAcceptableTypes(): array
    {
        return ["integer", "float"];
    }

    public static function getYamlCallbackTag(): string
    {
        return "!rule/min";
    }

    public static function executeYamlCallback(
        string $name,
        $value,
        int $flags,
        $context
    ) {
        $tag = self::getYamlCallbackTag();

        if ($name !== $tag) {
            throw new UnexpectedValueException(
                "yaml tag name must be '$tag'",
            );
        }

        if (!self::isValidMinValue($value)) {
            throw new UnexpectedValueException(
                "the '$tag' can only be used with a numeric value",
            );
        }

        return [
            "rule" => self::getName(),
            "minValue" => $value,
        ];
    }

    private static function isValidMinValue($value): bool
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

    private $minValue;
    private string $summary;
    private string $description;

    public function __construct(
        $minValue = 0,
        string $summary = "min:%s",
        string $description = "must be greater than or equal to %s"
    ) {
        if (!self::isValidMinValue($minValue)) {
            throw new \InvalidArgumentException("'minValue' must be numeric");
        }

        $this->minValue = $minValue;
        $this->summary = $summary;
        $this->description = $description;
    }

    public function getSummary(): string
    {
        return sprintf($this->summary, $this->minValue);
    }

    public function getDescription(): string
    {
        return sprintf($this->description, $this->minValue);
    }

    public function validate($value, string $path = "$")
    {
        if ($value >= $this->minValue) {
            return $value;
        }

        throw new \sndsgd\schema\exceptions\RuleValidationException(
            $path,
            $this->getDescription(),
        );
    }
}
