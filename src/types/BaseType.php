<?php declare(strict_types=1);

namespace sndsgd\schema\types;

use InvalidArgumentException;
use sndsgd\schema\RuleList;
use sndsgd\schema\Type;

abstract class BaseType implements Type
{
    private const NAME_REGEX = "/[a-z]{1,}[a-z0-9.]+/i";

    private string $name;
    private string $description;
    private RuleList $rules;

    public function __construct(
        string $name,
        string $description,
        RuleList $rules,
    ) {
        if (!preg_match(self::NAME_REGEX, $name)) {
            throw new InvalidArgumentException(
                "type names must start with a letter and only contain letters, "
                . "numbers, and dots",
            );
        }

        $this->name = $name;
        $this->description = $description;
        $this->rules = $rules;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getRules(): RuleList
    {
        return $this->rules;
    }
}
