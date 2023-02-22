<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use sndsgd\schema\exceptions\RuleValidationException;
use sndsgd\schema\Rule;
use sndsgd\schema\NamedRule;
use sndsgd\yaml\Callback as YamlCallback;
use UnexpectedValueException;

final class RegexRule implements Rule, NamedRule, YamlCallback
{
    public static function getName(): string
    {
        return "regex";
    }

    public static function getAcceptableTypes(): array
    {
        return ["string"];
    }

    public static function getYamlCallbackTag(): string
    {
        return "!rule/regex";
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

        if (
            !is_string($value)
            || $value === ""
            || @preg_match($value, "") === false
        ) {
            throw new UnexpectedValueException(
                "the '$tag' must be used with a valid regex",
            );
        }

        return [
            "rule" => self::getName(),
            "regex" => $value,
        ];
    }

    private string $regex;
    private string $summary;
    private string $description;

    public function __construct(
        string $regex,
        string $summary = "regex:%s",
        string $description = "must match a regular expression '%s'",
    ) {
        if (@preg_match($regex, "") === false) {
            throw new UnexpectedValueException("'$regex' is not a valid regex");
        }

        $this->regex = $regex;
        $this->summary = $summary;
        $this->description = $description;
    }

    public function getSummary(): string
    {
        return sprintf($this->summary, $this->regex);
    }

    public function getDescription(): string
    {
        return sprintf($this->description, $this->regex);
    }

    public function validate($value, string $path = "$"): string
    {
        if (preg_match($this->regex, $value) === 1) {
            return $value;
        }

        throw new RuleValidationException(
            $path,
            $this->getDescription(),
        );
    }
}
