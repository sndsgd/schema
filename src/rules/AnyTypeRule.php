<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use sndsgd\schema\NamedRule;
use sndsgd\schema\Rule;

final class AnyTypeRule implements Rule, NamedRule
{
    public static function getName(): string
    {
        return "any";
    }

    public static function getAcceptableTypes(): array
    {
        return [];
    }

    private string $summary;
    private string $description;

    public function __construct(
        string $summary = "type:mixed",
        string $description = "",
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
        return $value;
    }
}
