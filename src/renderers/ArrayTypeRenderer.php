<?php declare(strict_types=1);

namespace sndsgd\schema\renderers;

use sndsgd\Classname;
use sndsgd\schema\types\ArrayType;
use sndsgd\schema\types\ObjectType;
use sndsgd\schema\types\OneOfObjectType;
use sndsgd\schema\types\ScalarType;

class ArrayTypeRenderer
{
    private ArrayType $type;

    public function __construct(ArrayType $type)
    {
        $this->type = $type;
    }

    public function render(): string
    {
        $implements = [
            "\\ArrayAccess",
            "\\Iterator",
            "\\JsonSerializable",
        ];

        $ret = RenderHelper::getClassHeader($this->type, $implements);
        $ret .= "    private int \$index = 0;\n";
        $ret .= "    private array \$values = [];\n";
        $ret .= "\n";
        $ret .= $this->renderConstructor();
        $ret .= "\n";
        $ret .= $this->renderArrayAccessMethods();
        $ret .= "\n";
        $ret .= $this->renderIteratorMethods();
        $ret .= "\n";
        $ret .= $this->renderJsonSerialize();
        $ret .= "\n";
        $ret .= $this->renderGetter();
        $ret .= "}\n";

        return $ret;
    }

    private function renderConstructor(): string
    {
        $ret = "";
        $ret .= "    public function __construct(\$values, string \$path = \"\$\")\n";
        $ret .= "    {\n";

        $typeRule = $this->type->getRules()->getTypeRule();
        $ret .= RenderHelper::renderRuleCreateAndValidate($typeRule, "values");

        // note that we're using a local variable and a separate try/catch
        // for each property. we do not use an instance variable so it is
        // impossible to have a property name collision.
        $ret .= "        \$errors = new \sndsgd\schema\ValidationErrorList();\n";
        $ret .= "\n";

        // validate the value
        $valueType = $this->type->getValue();
        $valueTypeClass = "\\" . Classname::toString($valueType->getName());

        // we extract the value from scalar types after the instance is
        // created successfully. for all other types we just store the
        // type instance.
        $beforeCreateType = "";
        $afterCreateType = "";
        if (
            $valueType instanceof ScalarType
            || $valueType instanceof OneOfObjectType
        ) {
            $beforeCreateType = "(";
            $afterCreateType = ")->getValue()";
        }

        $ret .= "        for (\$i = 0, \$len = count(\$values); \$i < \$len; \$i++) {\n";
        $ret .= "            try {\n";
        $ret .= "                \$this->values[] = {$beforeCreateType}new $valueTypeClass(\n";
        $ret .= "                    \$values[\$i],\n";
        if (
            $valueType instanceof ObjectType
            || $valueType instanceof OneOfObjectType
        ) {
            $ret .= "                    false,\n";
        }
        $ret .= "                    \"\$path.\$i\"\n";
        $ret .= "                ){$afterCreateType};\n";
        $ret .= "            } catch (\\sndsgd\\schema\\ValidationFailure \$ex) {\n";
        $ret .= "                \$errors->addErrors(\$ex->getValidationErrors());\n";
        $ret .= "            }\n";
        $ret .= "        }\n";

        // remaining rules
        $rules = array_slice($this->type->getRules()->toArray(), 1);
        if ($rules) {
            $ret .= "\n";
            foreach ($rules as $rule) {
                $ret .= RenderHelper::renderRuleCreateAndValidate($rule, "this->values");
            }
        }

        $ret .= "        if (count(\$errors)) {\n";
        $ret .= "            throw \$errors->createException();\n";
        $ret .= "        }\n";
        $ret .= "    }\n";

        return $ret;
    }

    private function renderArrayAccessMethods(): string
    {
        $comment = "/** @see https://www.php.net/manual/en/class.arrayaccess.php */";

        $ret = "";
        $ret .= "    $comment\n";
        $ret .= "    public function offsetExists(\$offset): bool\n";
        $ret .= "    {\n";
        $ret .= "        return isset(\$this->values[\$offset]);\n";
        $ret .= "    }\n";
        $ret .= "\n";
        $ret .= "    $comment\n";
        $ret .= "    public function offsetGet(\$offset): mixed\n";
        $ret .= "    {\n";
        $ret .= "        if (!isset(\$this->values[\$offset])) {\n";
        $ret .= "            throw new \LogicException('undefined offset');\n";
        $ret .= "        }\n";
        $ret .= "        return \$this->values[\$offset];\n";
        $ret .= "    }\n";
        $ret .= "\n";
        $ret .= "    $comment\n";
        $ret .= "    public function offsetSet(\$offset, \$value): void\n";
        $ret .= "    {\n";
        $ret .= "        if (!isset(\$this->values[\$offset])) {\n";
        $ret .= "            throw new \LogicException('failed to update read only array');\n";
        $ret .= "        }\n";
        $ret .= "    }\n";
        $ret .= "\n";
        $ret .= "    $comment\n";
        $ret .= "    public function offsetUnset(\$offset): void\n";
        $ret .= "    {\n";
        $ret .= "        if (!isset(\$this->values[\$offset])) {\n";
        $ret .= "            throw new \LogicException('failed to update read only array');\n";
        $ret .= "        }\n";
        $ret .= "    }\n";

        return $ret;
    }

    private function renderIteratorMethods(): string
    {
        $comment = "/** @see https://www.php.net/manual/en/class.iterator.php */";

        $ret = "";
        $ret .= "    $comment\n";
        $ret .= "    public function current(): mixed\n";
        $ret .= "    {\n";
        $ret .= "        return \$this->values[\$this->index];\n";
        $ret .= "    }\n";
        $ret .= "\n";
        $ret .= "    $comment\n";
        $ret .= "    public function key(): mixed\n";
        $ret .= "    {\n";
        $ret .= "        return \$this->index;\n";
        $ret .= "    }\n";
        $ret .= "\n";
        $ret .= "    $comment\n";
        $ret .= "    public function next(): void\n";
        $ret .= "    {\n";
        $ret .= "        \$this->index++;\n";
        $ret .= "    }\n";
        $ret .= "\n";
        $ret .= "    $comment\n";
        $ret .= "    public function rewind(): void\n";
        $ret .= "    {\n";
        $ret .= "        \$this->index = 0;\n";
        $ret .= "    }\n";
        $ret .= "\n";
        $ret .= "    $comment\n";
        $ret .= "    public function valid(): bool\n";
        $ret .= "    {\n";
        $ret .= "        return isset(\$this->values[\$this->index]);\n";
        $ret .= "    }\n";

        return $ret;
    }

    private function renderJsonSerialize()
    {
        $ret = "";
        $ret .= "    /** @see https://www.php.net/manual/en/class.jsonserializable.php */\n";
        $ret .= "    public function jsonSerialize(): array\n";
        $ret .= "    {\n";

        if ($this->type->getValue() instanceof ScalarType) {
            $ret .= "        return \$this->getValues();\n";
        } else {
            $ret .= "        \$ret = [];\n";
            $ret .= "        for (\$i = 0, \$len = count(\$this->values); \$i < \$len; \$i++) {\n";
            $ret .= "            \$ret[] = \$this->values[\$i]->jsonSerialize();\n";
            $ret .= "        }\n";
            $ret .= "        return \$ret;\n";
        }

        $ret .= "    }\n";

        return $ret;
    }

    private function renderGetter(): string
    {
        $ret = "";
        $ret .= "    public function getValues(): array\n";
        $ret .= "    {\n";
        $ret .= "        return \$this->values;\n";
        $ret .= "    }\n";

        return $ret;
    }
}
