<?php declare(strict_types=1);

namespace sndsgd\schema;

interface Rule
{
    /**
     * Retrieve a list of the types the rule can be used to validate
     * An empty list indicates that a rule can handle any type.
     *
     * TODO: get this to work with rendered type names.
     * this would be useful for rules that process objects.
     *
     * @return array<string> A list of type names
     */
    public static function getAcceptableTypes(): array;

    /**
     * Retrieve a short summary of what the rule expects
     *
     * @return string
     */
    public function getSummary(): string;

    /**
     * Retrieve a message to show whenever validation fails
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Verify whether the provided value meets the criteria of the rule
     *
     * @param mixed $value The value to test
     * @param string $path The path to the value being validated
     * @return mixed The validated and/or sanitized value
     */
    public function validate($value, string $path = "$");
}
