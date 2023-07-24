<?php declare(strict_types=1);

namespace sndsgd\schema;

use sndsgd\schema\types\BaseType;

interface GeneratedType
{
    /**
     * Retrieve the type instance that a class was created from.
     *
     * @return BaseType
     */
    public static function fetchType(): BaseType;
}
