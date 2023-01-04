<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use sndsgd\schema\exceptions\TypeValidationException;
use sndsgd\schema\Rule;

class ArrayRule implements Rule
{
    public static function getName(): string
    {
        return "array";
    }

    public static function getAcceptableTypes(): array
    {
        return [];
    }

    private string $summary;
    private string $description;

    public function __construct(
        string $summary = "type:array",
        string $description = "must be an array",
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

    public function validate($value, string $path = "value"): array
    {
        if (is_array($value) && array_values($value) === $value) {
            return $value;
        }

        throw new TypeValidationException(
            $path,
            $this->getDescription(),
        );
    }
}
