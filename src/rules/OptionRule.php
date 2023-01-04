<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use LogicException;
use sndsgd\schema\exceptions\RuleValidationException;
use sndsgd\schema\Rule;

final class OptionRule implements Rule
{
    public static function getName(): string
    {
        return "option";
    }

    public static function getAcceptableTypes(): array
    {
        return ["integer", "float", "string"];
    }

    private static function implodeOptions(
        array $options,
        string $delimeter = ","
    ): string {
        $ret = "";
        foreach ($options as $option) {
            if ($ret !== "") {
                $ret .= $delimeter;
            }
            $ret .= var_export($option, true);
        }
        return $ret;
    }

    private array $options;
    private string $summary;
    private string $description;

    public function __construct(
        array $options,
        string $summary = "option:[%s]",
        string $description = "must be one of the following values: %s"
    ) {
        if ($options === []) {
            throw new LogicException(
                "'options' must contain at least one option",
            );
        }

        foreach ($options as $option) {
            if (!is_scalar($option)) {
                throw new LogicException(
                    "all options must be scalar values",
                );
            }
        }

        $this->options = $options;
        $this->summary = $summary;
        $this->description = $description;
    }

    public function getSummary(): string
    {
        return sprintf($this->summary, self::implodeOptions($this->options));
    }

    public function getDescription(): string
    {
        return sprintf($this->description, self::implodeOptions($this->options, ", "));
    }

    public function validate($value, string $path = "$")
    {
        if (in_array($value, $this->options, true)) {
            return $value;
        }

        throw new RuleValidationException(
            $path,
            $this->getDescription(),
        );
    }
}
