<?php declare(strict_types=1);

namespace sndsgd\schema;

use Countable;
use LogicException;
use sndsgd\schema\exceptions\ErrorListException;

// an object used to accumulate errors that can be analyzed
// together. prevents users from attempting to regenerate
// the files one time per error.
class ErrorList implements Countable
{
    private array $errors = [];

    public function count(): int
    {
        return count($this->errors);
    }

    public function addError(string $path, string $message)
    {
        if (!isset($this->errors[$path])) {
            $this->errors[$path] = [];
        }

        $this->errors[$path][] = $message;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function createException(): ErrorListException
    {
        return new ErrorListException($this);
    }
}
