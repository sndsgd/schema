<?php declare(strict_types=1);

namespace sndsgd\schema\renderers;

use sndsgd\Classname;
use sndsgd\schema\types\ArrayType;
use sndsgd\schema\types\ObjectType;
use sndsgd\schema\types\OneOfObjectType;
use sndsgd\schema\types\ScalarType;
use sndsgd\Str;

class ObjectTypeRenderer
{
    private ObjectType $type;

    public function __construct(ObjectType $type)
    {
        $this->type = $type;
    }

    public function render(): string
    {
        $ret = RenderHelper::getClassHeader($this->type);
        $ret .= $this->renderPropertyDefinitions();
        $ret .= "\n";
        $ret .= $this->renderConstructor();
        $ret .= "\n";
        $ret .= $this->renderGetters();
        $ret .= "\n";
        $ret .= $this->renderJsonSerialize();
        $ret .= "}\n";

        return $ret;
    }

    private function renderPropertyDefinitions(): string
    {
        $defaults = $this->type->getDefaults();

        $ret = "";
        foreach ($this->type->getProperties()->toArray() as $property) {
            $name = $property->getName();
            $typehint = RenderHelper::getTypeHint($property->getType());

            // scalar defaults can be defined here but more complex
            // types require initialization in the constructor
            if (
                isset($defaults[$name])
                && ($property->getType() instanceof ScalarType)
            ) {
                $ret .= sprintf(
                    "    private $typehint \$$name = %s;\n",
                    var_export($defaults[$name], true),
                );
            } else {
                $ret .= "    private $typehint \$$name;\n";
            }
        }
        return $ret;
    }

    private function renderConstructor(): string
    {
        $ret = "";
        $ret .= "    public function __construct(\n";
        $ret .= "        \$values,\n";
        $ret .= "        bool \$ignoreRequired = false,\n";
        $ret .= "        string \$path = \"\$\"\n";
        $ret .= "    ) {\n";

        // render a statement that validates `$values` is of the correct type
        $typeRule = $this->type->getRules()->getTypeRule();
        $ret .= RenderHelper::renderRuleCreateAndValidate($typeRule, "values");

        // note that we're using a local variable and a separate try/catch
        // for each property. we do not use an instance variable so it is
        // impossible to have a property name collision.
        $ret .= "        \$errors = new \sndsgd\schema\ValidationErrorList();\n";

        foreach ($this->type->getProperties()->toArray() as $property) {
            $name = $property->getName();
            $isRequired = $this->type->isPropertyRequired($name);
            $typeClass = "\\" . Classname::toString($property->getType()->getName());
            $path = "\$path.$name";

            // we extract the value from scalar types after the instance is
            // created successfully. for all other types we just store the
            // type instance.
            $beforeCreateType = "";
            $afterCreateType = "";
            if ($property->getType() instanceof ScalarType) {
                $beforeCreateType = "(";
                $afterCreateType = ")->getValue()";
            }

            // this is the actual validation logic.
            $tmp = "";
            $tmp .= "            try {\n";
            $tmp .= "                \$this->$name = {$beforeCreateType}new $typeClass(\n";
            $tmp .= "                    \$values->$name,\n";
            if (
                $property->getType() instanceof ObjectType
                || $property->getType() instanceof OneOfObjectType
            ) {
                $tmp .= "                    \$ignoreRequired,\n";
            }
            $tmp .= "                    \"$path\"\n";
            $tmp .= "                ){$afterCreateType};\n";
            $tmp .= "            } catch (\\sndsgd\\schema\\ValidationFailure \$ex) {\n";
            $tmp .= "                \$errors->addErrors(\$ex->getValidationErrors());\n";
            $tmp .= "            } finally {\n";
            $tmp .= "                unset(\$values->$name);\n";
            $tmp .= "            }\n";

            if ($isRequired) {
                $ret .= "\n";
                $ret .= "        if (property_exists(\$values, '$name')) {\n";
                $ret .= $tmp;
                $ret .= "        } elseif (!\$ignoreRequired) {\n";
                $ret .= "            \$errors->addError(\n";
                $ret .= "                \"$path\",\n";
                $ret .= "                _('required')\n";
                $ret .= "            );\n";
                $ret .= "        }\n";
            } else {
                $ret .= "\n";
                $ret .= "        if (property_exists(\$values, '$name')) {\n";
                $ret .= $tmp;

                // only scalars and arrays with empty array defaults can have
                // default values
                if (
                    isset($this->type->getDefaults()[$name])
                    && $this->type->getDefaults()[$name] === []
                    && $property->getType() instanceof ArrayType
                ) {
                    $ret .= "        } else {\n";
                    $ret .= "            \$this->$name = new $typeClass([]);\n";
                }
                $ret .= "        }\n";
            }
        }

        $ret .= "\n";
        $ret .= "        // all remaining properties are unknown\n";
        $ret .= "        foreach (\$values as \$name => \$value) {\n";
        $ret .= "            \$errors->addError(\n";
        $ret .= "                \"\$path.\$name\",\n";
        $ret .= "                _('unknown property')\n";
        $ret .= "            );\n";
        $ret .= "        }\n";

        // remaining rules
        $rules = array_slice($this->type->getRules()->toArray(), 1);
        if ($rules) {
            $ret .= "\n";
            foreach ($rules as $rule) {
                $ret .= RenderHelper::renderRuleCreateAndValidate($rule, "value");
            }
        }

        $ret .= "\n";
        $ret .= "        if (count(\$errors)) {\n";
        $ret .= "            throw \$errors->createException();\n";
        $ret .= "        }\n";
        $ret .= "    }\n";

        return $ret;
    }

    private function renderGetters(): string
    {
        $defaults = $this->type->getDefaults();

        $ret = "";
        foreach ($this->type->getProperties()->toArray() as $property) {
            $name = $property->getName();
            $type = $property->getType();
            $getMethod = Str::toCamelCase("get_" . $name);
            $hasMethod = Str::toCamelCase("has_" . $name);
            $retval = "\$this->$name";

            $docReturnType = "";
            $returnType = "";

            if ($type instanceof ArrayType) {
                $returnType = "array";
                $retval .= "->getValues()";
            } elseif ($type instanceof OneOfObjectType) {
                $docReturnType = RenderHelper::getDocReturnTypeForOneOfObject($type);
                $retval .= "->getValue()";
            } else {
                $returnType = RenderHelper::getTypeHint($type);
            }

            if ($ret !== "") {
                $ret .= "\n";
            }

            if (
                !isset($defaults[$name])
                && !$this->type->isPropertyRequired($property->getName())
            ) {
                $ret .= "    public function $hasMethod(): bool\n";
                $ret .= "    {\n";
                $ret .= "        return isset(\$this->$name);\n";
                $ret .= "    }\n";
                $ret .= "\n";
            }

            if ($docReturnType !== "") {
                $ret .= "    /**\n";
                $ret .= "     * @return $docReturnType\n";
                $ret .= "     */\n";
            }

            if ($returnType === "") {
                $ret .= "    public function $getMethod()\n";
            } else {
                $ret .= "    public function $getMethod(): $returnType\n";
            }

            $ret .= "    {\n";
            if (
                !isset($defaults[$name])
                && !$this->type->isPropertyRequired($property->getName())
            ) {
                $ret .= "        if (!\$this->$hasMethod()) {\n";
                $ret .= "            throw new \LogicException(\n";
                $ret .= "                \"no value for '$name'; \" .\n";
                $ret .= "                \"call `->$hasMethod()` before `->$getMethod()`\"\n";
                $ret .= "            );\n";
                $ret .= "        }\n";
                $ret .= "\n";
            }

            $ret .= "        return $retval;\n";
            $ret .= "    }\n";
        }

        return $ret;
    }

    private function renderJsonSerialize()
    {
        $ret = "";
        $ret .= "    public function jsonSerialize(): array\n";
        $ret .= "    {\n";
        $ret .= "        \$ret = [];\n";
        foreach ($this->type->getProperties()->toArray() as $property) {
            $name = $property->getName();
            $propertyAccessor = $valueAccessor = "\$this->$name";
            if (!($property->getType() instanceof ScalarType)) {
                $valueAccessor .= "->jsonSerialize()";
            }

            $name = var_export($name, true);

            if ($this->type->isPropertyRequired($property->getName())) {
                $ret .= "        \$ret[$name] = $valueAccessor;\n";
            } else {
                $ret .= "        if (isset($propertyAccessor)) {\n";
                $ret .= "            \$ret[$name] = $valueAccessor;\n";
                $ret .= "        }\n";
            }
        }

        $ret .= "        return \$ret;\n";
        $ret .= "    }\n";

        return $ret;
    }
}
