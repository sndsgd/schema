<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use sndsgd\schema\exceptions\TypeValidationException;
use sndsgd\schema\NamedRule;
use sndsgd\schema\Rule;

final class FloatRule implements Rule, NamedRule
{
    public static function getName(): string
    {
        return "float";
    }

    public static function getAcceptableTypes(): array
    {
        return [];
    }

    private string $summary;
    private string $description;

    public function __construct(
        string $summary = "type:float",
        string $description = "must be a float",
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

    public function validate($value, string $path = "$"): float
    {
        if (is_float($value)) {
            return $value;
        }

        if (is_int($value)) {
            return floatval($value);
        }

        if (
            is_string($value)
            && filter_var($value, FILTER_VALIDATE_FLOAT) !== false
        ) {
            return floatval($value);
        }

        throw new TypeValidationException(
            $path,
            $this->getDescription(),
        );
    }
}
