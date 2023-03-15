<?php declare(strict_types=1);

namespace sndsgd\schema;

use Countable;
use LogicException;
use sndsgd\schema\exceptions\ValidationException;

class ValidationErrorList implements Countable
{
    private array $errors = [];
    private int $maxErrors;

    public function __construct(int $maxErrors = PHP_INT_MAX)
    {
        $this->maxErrors = $maxErrors;
    }

    public function count(): int
    {
        return count($this->errors);
    }

    public function hasError(string $path): bool
    {
        return isset($this->errors[$path]);
    }

    public function addError(string $path, string $message)
    {
        if (isset($this->errors[$path])) {
            throw new LogicException("error already defined for '$path'");
        }
        $this->errors[$path] = $message;

        if (count($this) > $this->maxErrors) {
            throw $this->createException();
        }
    }

    public function addErrors(ValidationErrorList $errors)
    {
        foreach ($errors->toArray() as $error) {
            $this->addError($error["path"], $error["message"]);
        }
    }

    public function toArray(): array
    {
        $ret = [];
        foreach ($this->errors as $path => $message) {
            $ret[] = [
                "path" => $path,
                "message" => $message,
            ];
        }
        return $ret;
    }

    public function createException(): ValidationException
    {
        return new ValidationException($this);
    }
}
