<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use sndsgd\schema\exceptions\TypeValidationException;
use sndsgd\schema\Rule;
use sndsgd\Str;

final class BooleanRule implements Rule
{
    public static function getName(): string
    {
        return "boolean";
    }

    public static function getAcceptableTypes(): array
    {
        return [];
    }

    private string $summary;
    private string $description;

    public function __construct(
        string $summary = "type:boolean",
        string $description = "must be a boolean"
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

    public function validate($value, string $path = "$"): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            if ($value === 0) {
                return false;
            } elseif ($value === 1) {
                return true;
            }
        } elseif (is_string($value)) {
            $value = Str::toBoolean($value);
            if ($value !== null) {
                return $value;
            }
        }

        throw new TypeValidationException(
            $path,
            $this->getDescription(),
        );
    }
}
