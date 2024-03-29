<?php declare(strict_types=1);

namespace sndsgd\schema\exceptions;

use Exception;

/**
 * Used to indicate a property is defined in a property list multiple times
 */
class DuplicatePropertyException extends Exception
{
}
