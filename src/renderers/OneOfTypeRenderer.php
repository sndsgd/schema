<?php declare(strict_types=1);

namespace sndsgd\schema\renderers;

use sndsgd\Classname;
use sndsgd\schema\types\ObjectType;
use sndsgd\schema\types\OneOfType;
use sndsgd\schema\types\ScalarType;

class OneOfTypeRenderer
{
    private OneOfType $type;

    public function __construct(OneOfType $type)
    {
        $this->type = $type;
    }

    public function render(): string
    {
        $ret = RenderHelper::getClassHeader($this->type);
        $ret .= "    private \$value;\n";
        $ret .= "\n";
        $ret .= $this->renderConstructor();
        $ret .= "\n";
        $ret .= $this->renderJsonSerialize();
        $ret .= "\n";
        $ret .= $this->renderGetter();
        $ret .= "}\n";

        return $ret;
    }

    private function renderConstructor(): string
    {
        // this should really just be true because oneof is always complex
        $isComplex = false;

        $validTypeSignatures = [];

        // attempt to validate all the different types
        $tmp = "";

        // TODO we need to sort the types in a way to ensure that an integer
        // doesn't get coerced to a float, a string to a boolean, etc
        foreach ($this->type->getTypes() as $type) {
            $tmp .= $tmp === "" ? "" : "\n";

            $validTypeSignatures[] = $type->getSignature();
            $typeClass = "\\" . Classname::toString($type->getName());

            $tmp .= "        try {\n";
            $tmp .= "            \$this->value = new $typeClass(\n";
            $tmp .= "                \$value,\n";
            if ($type instanceof ObjectType) {
                $tmp .= "                false,\n";
            }
            $tmp .= "                \$path,\n";
            $tmp .= "            );\n";
            $tmp .= "            return;\n";
            $tmp .= "        } catch (\\sndsgd\\schema\\exceptions\\TypeValidationException \$ex) {\n";
            // if a nested type had a type error, we can throw here
            $tmp .= "            if (\$ex->getPath() !== \$path) {\n";
            $tmp .= "                throw \$ex;\n";
            $tmp .= "            }\n";
            $tmp .= "        } catch (\\sndsgd\\schema\\ValidationFailure \$ex) {\n";

            // if the type is scalar we can bail here because the provide value
            // had the valid type, but something else must have failed
            if ($type instanceof ScalarType) {
                $tmp .= "            throw \$ex;\n";
            } else {
                $isComplex = true;
                $typeName = var_export($type->getName(), true);
                $tmp .= "            \$errors[$typeName] = \$ex->getValidationErrors();\n";
            }

            $tmp .= "        }\n";
        }

        $ret = "";
        $ret .= "    public function __construct(\$value, string \$path = \"\$\")\n";
        $ret .= "    {\n";

        if ($isComplex) {
            // we need to use a plain array because an ErrorList does not
            // allow multiple errors for a given path
            $ret .= "        \$errors = [];\n";
            $ret .= "\n";
        }

        $ret .= $tmp;

        if ($isComplex) {
            // TODO do something with all the damn errors
        }

        $validTypeSignatures = implode(", ", $validTypeSignatures);

        $ret .= "\n";
        $ret .= "        throw new \\sndsgd\\schema\\exceptions\\RuleValidationException(\n";
        $ret .= "            \$path,\n";
        $ret .= "            \"must be one of the following types: $validTypeSignatures\"\n";
        $ret .= "        );\n";
        $ret .= "    }\n";

        return $ret;
    }

    private function renderJsonSerialize()
    {
        $ret = "";
        $ret .= "    /** @see https://www.php.net/manual/en/class.jsonserializable.php */\n";
        $ret .= "    public function jsonSerialize(): mixed\n";
        $ret .= "    {\n";
        $ret .= "        return \$this->value->jsonSerialize();\n";
        $ret .= "    }\n";

        return $ret;
    }

    private function renderGetter(): string
    {
        $ret = "";
        $ret .= "    public function getValue()\n";
        $ret .= "    {\n";
        $ret .= "        return \$this->value;\n";
        $ret .= "    }\n";

        return $ret;
    }
}
