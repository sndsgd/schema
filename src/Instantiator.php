<?php declare(strict_types=1);

namespace sndsgd\schema;

use LogicException;
use ReflectionClass;
use ReflectionFunctionAbstract;
use ReflectionParameter;
use sndsgd\Str;

class Instantiator
{
    private $class;

    public function __construct(
        string $class,
    ) {
        $this->class = new ReflectionClass($class);
    }

    public function instantiate(
        array $args,
    ): mixed {
        $method = $this->class->getMethod("__construct");
        $constructorArgs = self::resolveArguments($method, $args);
        return $this->class->newInstanceArgs($constructorArgs);
    }

    public static function resolveArguments(
        ReflectionFunctionAbstract $function,
        array $values,
    ): array {
        $args = [];

        foreach ($function->getParameters() as $parameter) {
            $name = self::resolveValueName($parameter->getName(), $values);
            if ($name !== "") {
                self::ensureType($parameter, $name, $values[$name]);
                $args[] = $values[$name];
                unset($values[$name]);
                continue;
            }

            if (!$parameter->isOptional()) {
                throw new LogicException("missing required property '$name'");
            }

            $args[] = $parameter->getDefaultValue();
        }

        // if any values remain we'll consider the input invalid
        if (!empty($values)) {
            $noun = count($values) === 1
                ? "value"
                : "values";
            $names = implode(",", array_keys($values));
            throw new LogicException("unknown $noun encountered ($names)");
        }

        return $args;
    }

    private static function ensureType(
        ReflectionParameter $parameter,
        string $name,
        $value,
    ): void {
        if (!$parameter->hasType()) {
            return;
        }

        $validTypes = ReflectionUtil::getParameterTypes($parameter);
        $actualType = ReflectionUtil::normalizeType(gettype($value));
        if (in_array($actualType, $validTypes, true)) {
            return;
        }

        throw new LogicException(
            sprintf(
                "property '%s' must be of type '%s', encountered '%s'",
                $name,
                implode("|", $validTypes),
                $actualType,
            ),
        );
    }

    public static function resolveValueName(
        string $name,
        array &$values,
    ): string {
        if (array_key_exists($name, $values)) {
            return $name;
        }

        $name = Str::toSnakeCase($name);
        if (array_key_exists($name, $values)) {
            return $name;
        }

        return "";
    }
}
