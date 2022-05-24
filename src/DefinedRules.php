<?php declare(strict_types=1);

namespace sndsgd\schema;

use Countable;
use ReflectionMethod;
use sndsgd\yaml\Callback as YamlCallback;

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
        \sndsgd\schema\rules\ArrayRule::class,
        \sndsgd\schema\rules\BooleanRule::class,
        \sndsgd\schema\rules\FloatRule::class,
        \sndsgd\schema\rules\IntegerRule::class,
        \sndsgd\schema\rules\ObjectRule::class,
        \sndsgd\schema\rules\StringRule::class,
        \sndsgd\schema\rules\OneOfRule::class,
        \sndsgd\schema\rules\AnyTypeRule::class,
        // misc
        \sndsgd\schema\rules\DateTimeRule::class,
        \sndsgd\schema\rules\EmailRule::class,
        \sndsgd\schema\rules\EqualRule::class,
        \sndsgd\schema\rules\HostnameRule::class,
        \sndsgd\schema\rules\MaxLengthRule::class,
        \sndsgd\schema\rules\MaxValueRule::class,
        \sndsgd\schema\rules\MinLengthRule::class,
        \sndsgd\schema\rules\MinValueRule::class,
        \sndsgd\schema\rules\OptionRule::class,
        \sndsgd\schema\rules\ReadableFileRule::class,
        \sndsgd\schema\rules\RegexRule::class,
        \sndsgd\schema\rules\UniqueRule::class,
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

    /**
     * Require the use of ::create() to create instances of this object
     */
    private function __construct()
    {
    }

    public function count(): int
    {
        return count($this->rules);
    }

    public function addRule(string $class): void
    {
        if (!class_exists($class)) {
            throw new \UnexpectedValueException(
                "failed to add rule; class '$class' does not exist",
            );
        }

        $interface = Rule::class;
        if (!in_array($interface, class_implements($class), true)) {
            throw new \UnexpectedValueException(
                "failed to add rule; class '$class' does not implement '$interface'",
            );
        }

        $name = $class::getName();

        // only allow a name to be used once
        if (isset($this->rules[$name])) {
            throw new \sndsgd\schema\exceptions\DuplicateRuleException(
                "the rule '$name' is already defined by '{$this->rules[$name]}'",
            );
        }

        if (!preg_match(self::RULE_NAME_REGEX, $name)) {
            throw new \UnexpectedValueException(
                "invalid rule name '$name'",
            );
        }

        $this->rules[$name] = $class;
    }

    public function instantiateRule(
        string $name,
        array $args
    ): \sndsgd\schema\Rule
    {
        $class = $this->rules[$name] ?? "";
        if ($class === "") {
            throw new \sndsgd\schema\exceptions\UndefinedRuleException(
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

            foreach ($constructor->getParameters() as $parameter) {
                $name = $parameter->getName();
                $type = $parameter->getType();
                if ($type) {
                    $type = ReflectionUtil::normalizeType(strval($type->getName()));
                } else {
                    $type = "any";
                }

                $property = ["type" => $type];
                if ($parameter->isOptional()) {
                    $property["default"] = $parameter->getDefaultValue();
                } else {
                    $requiredProperties[] = $name;
                }

                $properties[$name] = $property;
            }

            $ret[] = [
                "name" => $class,
                "type" => "object",
                "properties" => $properties,
                "required" => $requiredProperties,
            ];
        }

        return $ret;
    }
}
