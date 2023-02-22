<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use sndsgd\schema\exceptions\RuleValidationException;
use sndsgd\schema\Rule;
use sndsgd\schema\NamedRule;
use sndsgd\yaml\Callback as YamlCallback;
use UnexpectedValueException;

final class MaxLengthRule implements Rule, NamedRule, YamlCallback
{
    public static function getName(): string
    {
        return "maxLength";
    }

    public static function getAcceptableTypes(): array
    {
        return ["string"];
    }

    public static function getYamlCallbackTag(): string
    {
        return "!rule/maxLength";
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

        if (!is_scalar($value) || !ctype_digit($value)) {
            throw new UnexpectedValueException(
                "the '$tag' can only be used with an integer value",
            );
        }

        return [
            "rule" => self::getName(),
            "maxLength" => (int) $value,
        ];
    }

    public readonly int $maxLength;
    private string $summary;
    private string $description;

    public function __construct(
        int $maxLength,
        string $summary = "maxLength:%d",
        string $description = "must be no longer than %d characters",
    ) {
        if ($maxLength < 1) {
            throw new UnexpectedValueException(
                "'maxLength' must be greater than or equal to 1",
            );
        }

        $this->maxLength = $maxLength;
        $this->summary = $summary;
        $this->description = $description;
    }

    public function getSummary(): string
    {
        return sprintf($this->summary, $this->maxLength);
    }

    public function getDescription(): string
    {
        return sprintf($this->description, $this->maxLength);
    }

    public function validate($value, string $path = "$")
    {
        if (strlen($value) <= $this->maxLength) {
            return $value;
        }

        throw new RuleValidationException(
            $path,
            $this->getDescription(),
        );
    }
}
