<?php declare(strict_types=1);

namespace sndsgd\schema;

use sndsgd\schema\types\BaseType;

/**
 * An interace for all generated types to implement. This will be useful
 * when attempting to programatically determine if a class is generated.
 */
interface GeneratedType
{
    /**
     * Retrieve the type instance that a class was created from.
     *
     * @return BaseType
     */
    public static function fetchType(): BaseType;
}
