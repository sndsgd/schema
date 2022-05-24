<?php declare(strict_types=1);

namespace sndsgd\schema\exceptions;

use Exception;
use sndsgd\schema\ValidationErrorList;
use sndsgd\schema\ValidationFailure;

class RuleValidationException extends Exception implements ValidationFailure
{
    private string $path;

    public function __construct(string $path, string $message)
    {
        $this->path = $path;
        parent::__construct($message);
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getValidationErrors(): ValidationErrorList
    {
        $list = new ValidationErrorList();
        $list->addError($this->path, $this->message);
        return $list;
    }
}
