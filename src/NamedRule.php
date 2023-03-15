<?php declare(strict_types=1);

namespace sndsgd\schema;

interface NamedRule
{
    /**
     * Retrieve a short name that can beused to reference
     * this rule in schema files
     *
     * @return string
     */
    public static function getName(): string;
}
