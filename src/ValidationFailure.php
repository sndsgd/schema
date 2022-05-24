<?php declare(strict_types=1);

namespace sndsgd\schema;

/**
 * Implemented by exceptions that validation errors can be retrieved from
 */
interface ValidationFailure
{
    /**
     * Retrieve a list of validation errors
     *
     * @return ValidationErrorList
     */
    public function getValidationErrors(): ValidationErrorList;
}
