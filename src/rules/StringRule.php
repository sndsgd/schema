<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use sndsgd\schema\exceptions\TypeValidationException;
use sndsgd\schema\NamedRule;
use sndsgd\schema\Rule;

final class StringRule implements Rule, NamedRule
{
    public static function getName(): string
    {
        return "string";
    }

    public static function getAcceptableTypes(): array
    {
        return [];
    }

    private string $summary;
    private string $description;

    public function __construct(
        string $summary = "type:string",
        string $description = "must be a string",
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

    public function validate($value, string $path = "$"): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_object($value) && method_exists($value, "__toString")) {
            return (string) $value;
        }

        throw new TypeValidationException(
            $path,
            $this->getDescription(),
        );
    }
}
