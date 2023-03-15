<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use InvalidArgumentException;
use sndsgd\schema\exceptions\RuleValidationException;
use sndsgd\schema\NamedRule;
use sndsgd\schema\Rule;
use sndsgd\yaml\Callback as YamlCallback;
use UnexpectedValueException;

final class MaxDecimalsRule implements Rule, NamedRule, YamlCallback
{
    private const ARBITRARY_MAX = 24;

    public static function getName(): string
    {
        return "maxDecimals";
    }

    public static function getAcceptableTypes(): array
    {
        return ["float"];
    }

    public static function getYamlCallbackTag(): string
    {
        return "!rule/maxDecimals";
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

        if (!self::isValidValue($value)) {
            throw new UnexpectedValueException(
                "the '$tag' can only be used with a single integer value",
            );
        }

        return [
            "rule" => self::getName(),
            "maxDecimals" => intval($value),
        ];
    }

    private static function isValidValue($value): bool
    {
        if (is_string($value)) {
            if (!ctype_digit($value)) {
                return false;
            }
        } elseif (!is_int($value)) {
            return false;
        }

        $value = intval($value);
        return ($value > 0 && $value <= self::ARBITRARY_MAX);
    }

    public readonly int $maxDecimals;
    private string $summary;
    private string $description;

    public function __construct(
        int $maxDecimals = 10,
        string $summary = "maxDecimals:%s",
        string $description = "must use no more than %s decimal places",
    ) {
        if (!self::isValidValue($maxDecimals)) {
            throw new InvalidArgumentException(
                "'maxDecimals' must be a greater than 0 and less than " .
                self::ARBITRARY_MAX,
            );
        }

        $this->maxDecimals = $maxDecimals;
        $this->summary = $summary;
        $this->description = $description;
    }

    public function getSummary(): string
    {
        return sprintf($this->summary, $this->maxDecimals);
    }

    public function getDescription(): string
    {
        return sprintf($this->description, $this->maxDecimals);
    }

    public function validate($value, string $path = "$")
    {
        if (!is_float($value)) {
            throw new LogicException("provided value must be a float");
        }

        $parts = explode(".", strval($value));
        $decimals = $parts[1] ?? "";

        if (strlen($decimals) <= $this->maxDecimals) {
            return $value;
        }

        throw new RuleValidationException(
            $path,
            $this->getDescription(),
        );
    }
}
