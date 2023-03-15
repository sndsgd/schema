<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use sndsgd\schema\exceptions\TypeValidationException;
use sndsgd\schema\NamedRule;
use sndsgd\schema\Rule;

final class IntegerRule implements Rule, NamedRule
{
    public static function getName(): string
    {
        return "integer";
    }

    public static function getAcceptableTypes(): array
    {
        return [];
    }

    private string $summary;
    private string $description;

    public function __construct(
        string $summary = "type:integer",
        string $description = "must be an integer",
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

    public function validate($value, string $path = "$"): int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) || is_float($value)) {
            $value = filter_var($value, FILTER_VALIDATE_INT);
            if ($value !== false) {
                return $value;
            }
        }

        // TODO need to sort out how to handle values greater than
        // PHP_INT_MAX and less than PHP_INT_MIN

        throw new TypeValidationException(
            $path,
            $this->getDescription(),
        );
    }
}
