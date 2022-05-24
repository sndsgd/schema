<?php declare(strict_types=1);

namespace sndsgd\schema\renderers;

use sndsgd\Classname;
use sndsgd\schema\types\MapType;
use sndsgd\schema\types\ObjectType;
use sndsgd\schema\types\OneOfObjectType;
use sndsgd\schema\types\ScalarType;

class MapTypeRenderer
{
    private MapType $type;

    public function __construct(MapType $type)
    {
        $this->type = $type;
    }

    public function render(): string
    {
        $classname = RenderHelper::createClassnameFromString($this->type->getName());
        $namespace = $classname->getNamespace();
        $classname = $classname->getClass();

        $ret = "";
        $ret .= "<?php declare(strict_types=1);\n";
        $ret .= "\n";
        if ($namespace) {
            $ret .= "namespace $namespace;\n";
            $ret .= "\n";
        }
        $ret .= RenderHelper::getClassComment($this->type);
        $ret .= "final class $classname implements \ArrayAccess, \Iterator, \JsonSerializable\n";
        $ret .= "{\n";
        $ret .= $this->renderPropertyDefinitions();
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

    private function renderPropertyDefinitions(): string
    {
        $ret = "";
        $ret .= "    private int \$index = 0;\n";
        $ret .= "    private array \$keys = [];\n";
        $ret .= "    private array \$values = [];\n";

        return $ret;
    }

    private function renderConstructor(): string
    {
        $ret = "";
        $ret .= "    public function __construct(\$values, string \$path = \"\$\")\n";
        $ret .= "    {\n";

        // render a statement that validates `$values` is of the correct type
        $typeRule = $this->type->getRules()->getTypeRule();
        $ret .= RenderHelper::renderRuleCreateAndValidate($typeRule, "values");

        // note that we're using a local variable and a separate try/catch
        // for each property. we do not use an instance variable so it is
        // impossible to have a property name collision.
        $ret .= "        \$errors = new \sndsgd\schema\ValidationErrorList();\n";

        // iterate over the values, testing each key and value as we go
        $ret .= "\n";
        $ret .= "        foreach (\$values as \$key => \$value) {\n";

        // validate the key (can only be string, integer, or float)
        $keyType = $this->type->getKeyType();
        $keyTypeClass = "\\" . Classname::toString($keyType->getName());
        $ret .= "            try {\n";
        $ret .= "                \$this->keys[] = \$key = (new $keyTypeClass(\n";
        $ret .= "                    \$key,\n";
        $ret .= "                    \"\$path.\$key\"\n";
        $ret .= "                ))->getValue();\n";
        $ret .= "            } catch (\\sndsgd\\schema\\ValidationFailure \$ex) {\n";
        $ret .= "                \$errors->addErrors(\$ex->getValidationErrors());\n";
        $ret .= "                continue;\n";
        $ret .= "            }\n";

        // validate the value
        $valueType = $this->type->getValueType();
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

        $ret .= "\n";
        $ret .= "            try {\n";
        $ret .= "                \$this->values[\$key] = {$beforeCreateType}new $valueTypeClass(\n";
        $ret .= "                    \$values->\$key,\n";
        if (
            $valueType instanceof ObjectType
            || $valueType instanceof OneOfObjectType
        ) {
            $ret .= "                    false,\n";
        }
        $ret .= "                    \"\$path.\$key\"\n";
        $ret .= "                ){$afterCreateType};\n";
        $ret .= "            } catch (\\sndsgd\\schema\\ValidationFailure \$ex) {\n";
        $ret .= "                \$errors->addErrors(\$ex->getValidationErrors());\n";
        $ret .= "            }\n";
        $ret .= "        }\n";

        // throw an exception if there are any errors
        $ret .= "\n";
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
        $ret .= "    public function offsetExists(mixed \$offset): bool\n";
        $ret .= "    {\n";
        $ret .= "        return isset(\$this->values[\$offset]);\n";
        $ret .= "    }\n";
        $ret .= "\n";
        $ret .= "    $comment\n";
        $ret .= "    public function offsetGet(mixed \$offset): mixed\n";
        $ret .= "    {\n";
        $ret .= "        return \$this->values[\$offset];\n";
        $ret .= "    }\n";
        $ret .= "\n";
        $ret .= "    $comment\n";
        $ret .= "    public function offsetSet(mixed \$offset, mixed \$value): void\n";
        $ret .= "    {\n";
        $ret .= "        throw new \LogicException('setting values via ArrayAccess is disabled');\n";
        $ret .= "    }\n";
        $ret .= "\n";
        $ret .= "    $comment\n";
        $ret .= "    public function offsetUnset(mixed \$offset): void\n";
        $ret .= "    {\n";
        $ret .= "        throw new \LogicException('unsetting values via ArrayAccess is disabled');\n";
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
        $ret .= "        return \$this->values[\$this->key()];\n";
        $ret .= "    }\n";
        $ret .= "\n";
        $ret .= "    $comment\n";
        $ret .= "    public function key(): mixed\n";
        $ret .= "    {\n";
        $ret .= "        return \$this->keys[\$this->index];\n";
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
        $ret .= "        return isset(\$this->keys[\$this->index]);\n";
        $ret .= "    }\n";

        return $ret;
    }

    private function renderJsonSerialize()
    {
        $ret = "";
        $ret .= "    /** @see https://www.php.net/manual/en/class.jsonserializable.php */\n";
        $ret .= "    public function jsonSerialize(): array\n";
        $ret .= "    {\n";

        $valueType = $this->type->getValueType();
        if ($valueType instanceof ScalarType) {
            $ret .= "        return \$this->getValues();\n";
        } else {
            $ret .= "        \$ret = [];\n";
            $ret .= "        foreach (\$this->values as \$key => \$value) {\n";
            $ret .= "            \$ret[\$key] = \$value->jsonSerialize();\n";
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
