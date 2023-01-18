<?php declare(strict_types=1);

namespace sndsgd\schema\renderers;

use ReflectionClass;
use sndsgd\Classname;
use sndsgd\schema\Rule;
use sndsgd\schema\Type;
use sndsgd\schema\types\AnyType;
use sndsgd\schema\types\OneOfObjectType;
use sndsgd\schema\types\ScalarType;

class RenderHelper
{
    public static function getClassHeader(
        Type $type,
        array $implements = ["\\JsonSerializable"],
    ): string {
        $classname = self::createClassnameFromString($type->getName());
        $namespace = $classname->getNamespace();
        $classname = $classname->getClass();

        $implements = implode(", ", $implements);

        $ret = "";
        $ret .= "<?php declare(strict_types=1);\n";
        $ret .= "\n";
        if ($namespace) {
            $ret .= "namespace $namespace;\n";
            $ret .= "\n";
        }
        $ret .= RenderHelper::getClassComment($type);
        $ret .= "final class $classname implements $implements\n";
        $ret .= "{\n";
        $ret .= RenderHelper::renderTypeFetcher($type);
        $ret .= "\n";

        return $ret;
    }

    public static function getClassComment(Type $type): string
    {
        return <<<COMMENT
        /**
         * GENERATED CODE! DO NOT EDIT!
         * This file was generated by the sndsgd/schema library
         *
         * Name: {$type->getName()}
         * Description: {$type->getDescription()}
         */

        COMMENT;
    }

    public static function renderTypeFetcher($type): string
    {
        $class = $type::class;
        $serialized = var_export(serialize($type), true);

        $ret = "";
        $ret .= "    public static function fetchType(): \\$class\n";
        $ret .= "    {\n";
        $ret .= "        return unserialize($serialized);\n";
        $ret .= "    }\n";

        return $ret;
    }

    public static function renderRuleCreateAndValidate(
        Rule $rule,
        string $variableName,
        bool $translateDescription = true,
        bool $includePathArgument = true,
    ): string {
        $reflection = new ReflectionClass($rule);
        $class = $reflection->getName();

        $ret = "";
        if ($variableName === "this") {
            $ret .= "        (new \\$class(\n";
        } else {
            $ret .= "        \$$variableName = (new \\$class(\n";
        }

        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            $propertyName = $property->getName();
            $value = $property->getValue($rule);
            $value = var_export($value, true);

            if ($translateDescription && $propertyName === "description") {
                $value = "_($value)";
            }

            $ret .= "            $propertyName: $value,\n";
        }

        $args = "\$$variableName";
        if ($includePathArgument) {
            $args .= ", \$path";
        }

        return $ret . "        ))->validate($args);\n\n";
    }

    public static function getTypePsr4Path(string $baseDir, Type $type): string
    {
        $relpath = str_replace(".", "/", $type->getName()) . ".php";
        return "$baseDir/$relpath";
    }

    public static function getDocReturnTypeForOneOfObject(OneOfObjectType $type): string
    {
        $ret = "";
        foreach ($type->getTypeMap() as $objectType) {
            if ($ret !== "") {
                $ret .= "|";
            }
            $ret .= self::getTypeHint($objectType);
        }
        return $ret;
    }

    public static function getTypeHint(Type $type): string
    {
        if ($type instanceof ScalarType) {
            $typehint = $type->getRules()->getTypeName();
            switch ($typehint) {
                case "integer":
                    return "int";
                case "boolean":
                    return "bool";
                default:
                    return $typehint;
            }
        }

        if ($type instanceof AnyType) {
            return "mixed";
        }

        return "\\" . Classname::toString($type->getName());
    }

    public static function createClassnameFromString(string $class): Classname
    {
        $parts = Classname::split($class);
        return new Classname(implode("\\", $parts));
    }
}
