<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

class ObjectRule implements \sndsgd\schema\Rule
{
    public static function getName(): string
    {
        return "object";
    }

    public static function getAcceptableTypes(): array
    {
        return [];
    }

    private string $summary;
    private string $description;

    public function __construct(
        string $summary = "type:object",
        string $description = "must be an object"
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
        if (is_object($value)) {
            return $value;
        }

        // allow associative arrays
        // note that we do _not_ allow empty arrays here!
        if (
            is_array($value)
            && $value !== []
            && array_keys($value) !== range(0, count($value) - 1)
        ) {
            return (object) $value;
        }

        throw new \sndsgd\schema\exceptions\TypeValidationException(
            $path,
            $this->getDescription(),
        );
    }
}
