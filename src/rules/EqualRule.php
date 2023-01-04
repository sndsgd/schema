<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use LogicException;
use sndsgd\schema\exceptions\RuleValidationException;
use sndsgd\schema\Rule;
use sndsgd\yaml\Callback as YamlCallback;
use UnexpectedValueException;

final class EqualRule implements Rule, YamlCallback
{
    public static function getName(): string
    {
        return "equal";
    }

    public static function getAcceptableTypes(): array
    {
        return ["string", "integer", "float"];
    }

    public static function getYamlCallbackTag(): string
    {
        return "!rule/equal";
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

        if (!is_scalar($value)) {
            throw new LogicException(
                "the '$tag' can only be used with a scalar value",
            );
        }

        return [
            "rule" => self::getName(),
            "equal" => $value,
        ];
    }

    private $equal;
    private string $summary;
    private string $description;

    public function __construct(
        $equal,
        string $summary = "equal:%s",
        string $description = "must be %s"
    ) {
        $this->equal = $equal;
        $this->summary = $summary;
        $this->description = $description;
    }

    public function getSummary(): string
    {
        return sprintf($this->summary, $this->equal);
    }

    public function getDescription(): string
    {
        return sprintf($this->description, $this->equal);
    }

    public function validate($value, string $path = "$")
    {
        if ($value === $this->equal) {
            return $value;
        }

        throw new RuleValidationException(
            $path,
            $this->getDescription(),
        );
    }
}
