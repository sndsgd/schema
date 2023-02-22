<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use LogicException;
use sndsgd\schema\exceptions\RuleValidationException;
use sndsgd\schema\Rule;
use sndsgd\schema\NamedRule;
use sndsgd\yaml\Callback as YamlCallback;
use UnexpectedValueException;

final class HostnameRule implements Rule, NamedRule, YamlCallback
{
    public static function getName(): string
    {
        return "hostname";
    }

    public static function getAcceptableTypes(): array
    {
        return ["string"];
    }

    public static function getYamlCallbackTag(): string
    {
        return "!rule/hostname";
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
        string $summary = "hostname",
        string $description = "must be a valid hostname",
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

    public function validate($value, string $path = "$")
    {
        if (filter_var($value, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            return $value;
        }

        throw new RuleValidationException(
            $path,
            $this->getDescription(),
        );
    }
}
