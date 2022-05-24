<?php declare(strict_types=1);

namespace sndsgd\schema;

class Property
{
    public function __construct(
        private string $name,
        private Type $type
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): Type
    {
        return $this->type;
    }
}
