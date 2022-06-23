<?php declare(strict_types=1);

namespace sndsgd\schema;

use Countable;
use sndsgd\schema\exceptions\DuplicatePropertyException;

class PropertyList implements Countable
{
    private array $properties = [];

    public function __construct(Property ...$properties)
    {
        foreach ($properties as $property) {
            $this->addProperty($property);
        }
    }

    public function count(): int
    {
        return count($this->properties);
    }

    private function addProperty(Property $property): void
    {
        $name = $property->getName();
        if (isset($this->properties[$name])) {
            throw new DuplicatePropertyException(
                "failed to add '$name' to property list multiple times",
            );
        }

        $this->properties[$name] = $property;
    }

    public function get(string $name): Property
    {
        return $this->properties[$name];
    }

    public function has(string $name): bool
    {
        return isset($this->properties[$name]);
    }

    public function toArray(): array
    {
        return $this->properties;
    }
}
