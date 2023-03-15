<?php declare(strict_types=1);

namespace sndsgd\schema\exceptions;

use Exception;
use sndsgd\schema\ErrorList;
use Throwable;

class ErrorListException extends Exception
{
    private ErrorList $errors;

    public function __construct(
        ErrorList $errors,
        string $message = "type definition errors encountered",
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        $this->errors = $errors;
        parent::__construct($message, $code, $previous);
    }

    public function getErrorList(): ErrorList
    {
        return $this->errors;
    }
}
