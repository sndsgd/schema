<?php declare(strict_types=1);

namespace sndsgd\schema\exceptions;

use Exception;

/**
 * Used to indicate a rule is added to a rule list multiple times
 */
class DuplicateRuleException extends Exception
{
}
