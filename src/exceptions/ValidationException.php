<?php declare(strict_types=1);

namespace sndsgd\schema\exceptions;

use Exception;
use sndsgd\schema\ValidationErrorList;
use sndsgd\schema\ValidationFailure;
use Throwable;

class ValidationException extends Exception implements ValidationFailure
{
    private ValidationErrorList $errors;

    public function __construct(
        ValidationErrorList $errors,
        string $message = "validation failed",
        int $code = 0,
        ?Throwable $previous = null,
    )
    {
        $this->errors = $errors;
        parent::__construct($message, $code, $previous);
    }

    public function getValidationErrors(): ValidationErrorList
    {
        return $this->errors;
    }
}
