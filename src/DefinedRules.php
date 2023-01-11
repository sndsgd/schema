<?php declare(strict_types=1);

namespace sndsgd\schema;

use Countable;
use ReflectionMethod;
use ReflectionNamedType;
use sndsgd\schema\exceptions\DuplicateRuleException;
use sndsgd\schema\exceptions\UndefinedRuleException;
use sndsgd\schema\Rule;
use sndsgd\schema\rules\AnyTypeRule;
use sndsgd\schema\rules\ArrayRule;
use sndsgd\schema\rules\BooleanRule;
use sndsgd\schema\rules\DateTimeRule;
use sndsgd\schema\rules\EmailRule;
use sndsgd\schema\rules\EqualRule;
use sndsgd\schema\rules\FloatRule;
use sndsgd\schema\rules\HostnameRule;
use sndsgd\schema\rules\IntegerRule;
use sndsgd\schema\rules\MaxLengthRule;
use sndsgd\schema\rules\MaxValueRule;
use sndsgd\schema\rules\MinLengthRule;
use sndsgd\schema\rules\MinValueRule;
use sndsgd\schema\rules\ObjectRule;
use sndsgd\schema\rules\OneOfRule;
use sndsgd\schema\rules\OptionRule;
use sndsgd\schema\rules\ReadableFileRule;
use sndsgd\schema\rules\RegexRule;
use sndsgd\schema\rules\StringRule;
use sndsgd\schema\rules\UniqueRule;
use sndsgd\schema\rules\WritableFileRule;
use sndsgd\yaml\Callback as YamlCallback;
use UnexpectedValueException;

class DefinedRules implements Countable
{
    private const RULE_NAME_REGEX = "/^[a-z]{1,}[a-z0-1_]+$/i";

    /**
     * A map of rules defined in sndsgd/schema
     *
     * @var array<string>
     */
    public const SNDSGD_SCHEMA_RULES = [
        // types
        ArrayRule::class,
        BooleanRule::class,
        FloatRule::class,
        IntegerRule::class,
        ObjectRule::class,
        StringRule::class,
        OneOfRule::class,
        AnyTypeRule::class,
        // misc
        DateTimeRule::class,
        EmailRule::class,
        EqualRule::class,
        HostnameRule::class,
        MaxLengthRule::class,
        MaxValueRule::class,
        MinLengthRule::class,
        MinValueRule::class,
        OptionRule::class,
        ReadableFileRule::class,
        RegexRule::class,
        UniqueRule::class,
        WritableFileRule::class,
    ];

    public static function create(): DefinedRules
    {
        $definedRules = new DefinedRules();
        foreach (self::SNDSGD_SCHEMA_RULES as $class) {
            $definedRules->addRule($class);
        }
        return $definedRules;
    }

    /**
     * A map of rule classes, keyed by the rule name
     *
     * @var array<string,string>
     */
    private $rules = [];

    private function __construct()
    {
        // Require the use of ::create() to create instances of this object
    }

    public function count(): int
    {
        return count($this->rules);
    }

    public function addRule(string $class): void
    {
        if (!class_exists($class)) {
            throw new UnexpectedValueException(
                "failed to add rule; class '$class' does not exist",
            );
        }

        if (!in_array(Rule::class, class_implements($class), true)) {
            throw new UnexpectedValueException(
                sprintf(
                    "failed to add rule; class '%s' does not implement '%s'",
                    $class,
                    Rule::class,
                ),
            );
        }

        $name = $class::getName();

        // only allow a name to be used once
        if (isset($this->rules[$name])) {
            throw new DuplicateRuleException(
                sprintf(
                    "the rule '%s' is already defined by '%s'",
                    $name,
                    $this->rules[$name],
                ),
            );
        }

        if (!preg_match(self::RULE_NAME_REGEX, $name)) {
            throw new UnexpectedValueException(
                "invalid rule name '$name'",
            );
        }

        $this->rules[$name] = $class;
    }

    public function instantiateRule(
        string $name,
        array $args,
    ): Rule {
        $class = $this->rules[$name] ?? "";
        if ($class === "") {
            throw new UndefinedRuleException(
                "rule for '$name' not defined",
            );
        }

        return (new Instantiator($class))->instantiate($args);
    }

    public function getYamlCallbackClasses(): array
    {
        $ret = [];
        foreach ($this->rules as $class) {
            if (in_array(YamlCallback::class, class_implements($class), true)) {
                $ret[] = $class;
            }
        }
        return $ret;
    }

    public function toSchema(): array
    {
        $ret = [];

        foreach ($this->rules as $class) {
            $constructor = new ReflectionMethod($class, "__construct");
            $properties = [
                "rule" => [
                    "type" => "string",
                    "rules" => [
                        "rule" => "equals",
                        "equal" => $class::getName(),
                    ],
                ],
            ];
            $requiredProperties = ["rule"];
            $defaults = [];

            foreach ($constructor->getParameters() as $parameter) {
                $name = $parameter->getName();
                $type = $parameter->getType();
                if ($type instanceof ReflectionNamedType) {
                    $type = $type->getName();
                    $type = ReflectionUtil::normalizeType(strval($type));
                } else {
                    $type = "any";
                }

                $property = ["type" => $type];
                if ($parameter->isOptional()) {
                    $defaults[$name] = $parameter->getDefaultValue();
                } else {
                    $requiredProperties[] = $name;
                }

                $properties[$name] = $property;
            }

            $ret[] = array_filter([
                "name" => $class,
                "type" => "object",
                "properties" => $properties,
                "required" => $requiredProperties,
                "defaults" => $defaults,
            ], static function($value) {
                return $value !== [];
            });
        }

        return $ret;
    }
}
