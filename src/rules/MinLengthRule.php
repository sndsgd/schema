<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use sndsgd\schema\exceptions\RuleValidationException;
use sndsgd\schema\Rule;
use sndsgd\yaml\Callback as YamlCallback;
use UnexpectedValueException;

final class MinLengthRule implements Rule, YamlCallback
{
    public static function getName(): string
    {
        return "minLength";
    }

    public static function getAcceptableTypes(): array
    {
        return ["string"];
    }

    public static function getYamlCallbackTag(): string
    {
        return "!rule/minLength";
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
            "minLength" => (int) $value,
        ];
    }

    public readonly int $minLength;
    private string $summary;
    private string $description;

    public function __construct(
        int $minLength,
        string $summary = "minLength:%d",
        string $description = "must be at least %d characters",
    ) {
        if ($minLength < 1) {
            throw new UnexpectedValueException(
                "'minLength' must be greater than or equal to 1",
            );
        }

        $this->minLength = $minLength;
        $this->summary = $summary;
        $this->description = $description;
    }

    public function getSummary(): string
    {
        return sprintf($this->summary, $this->minLength);
    }

    public function getDescription(): string
    {
        return sprintf($this->description, $this->minLength);
    }

    public function validate($value, string $path = "$"): string
    {
        if (strlen($value) >= $this->minLength) {
            return $value;
        }

        throw new RuleValidationException(
            $path,
            $this->getDescription(),
        );
    }
}
