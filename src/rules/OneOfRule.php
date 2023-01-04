<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use sndsgd\schema\exceptions\RuleValidationException;
use sndsgd\schema\exceptions\TypeValidationFailure;
use sndsgd\schema\Rule;
use sndsgd\schema\ValidationErrorList;
use sndsgd\schema\ValidationFailure;

class OneOfRule implements Rule
{
    public static function getName(): string
    {
        return "oneof";
    }

    public static function getAcceptableTypes(): array
    {
        return [];
    }

    private array $rules;
    private string $summary;
    private string $description;

    public function __construct(
        array $rules,
        string $summary = "",
        string $description = "",
    ) {
        $this->setRules(...$rules);
        $this->summary = $summary;
        $this->description = $description;
    }

    private function setRules(Rule ...$rules): void
    {
        $this->rules = $rules;
    }

    public function getSummary(): string
    {
        if ($this->summary !== "") {
            return $this->summary;
        }

        return "oneof:" . implode("|", $this->types);
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function validate($value, string $path = "$")
    {
        $nonTypeErrors = new ValidationErrorList();

        foreach ($this->types as $class) {
            try {
                return new $class($value, $path);
            } catch (TypeValidationFailure $ex) {
                // if the type failed to validate just continue to the next value
                continue;
            } catch (ValidationFailure $ex) {
                // if we get here, this means the type was correct, but an
                // additional validation rule failed. we may be able to use
                // the error message if none of the other types validate.
                $nonTypeErrors->addErrors($ex->getValidationErrors());
            }
        }

        print_r($nonTypeErrors);

        throw new RuleValidationException(
            $path,
            $this->getDescription(),
        );
    }
}
