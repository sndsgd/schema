<?php declare(strict_types=1);

namespace sndsgd\schema\renderers;

use sndsgd\Classname;
use sndsgd\schema\types\OneOfObjectType;

class OneOfObjectTypeRenderer
{
    private OneOfObjectType $type;

    public function __construct(OneOfObjectType $type)
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
        $ret .= "final class $classname implements \JsonSerializable\n";
        $ret .= "{\n";
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
        $ret = "";
        $ret .= "    public function __construct(\n";
        $ret .= "        \$value,\n";
        $ret .= "        bool \$ignoreRequired = false,\n";
        $ret .= "        string \$path = \"\$\"\n";
        $ret .= "    ) {\n";

        // render a statement that validates `$values` is of the correct type
        $typeRule = $this->type->getRules()->getTypeRule();
        $ret .= RenderHelper::renderRuleCreateAndValidate($typeRule, "value");

        // we need to key property that is used to determine the type to exist
        $oneOfKey = $this->type->getKey();
        $ret .= "        if (!property_exists(\$value, '$oneOfKey')) {\n";
        $ret .= "            throw new \\sndsgd\\schema\\exceptions\\RuleValidationException(\n";
        $ret .= "                \"\$path.$oneOfKey\",\n";
        $ret .= "                _('required')\n";
        $ret .= "            );\n";
        $ret .= "        }\n";

        $ret .= "\n";
        $ret .= "        switch (\$value->$oneOfKey) {\n";

        $typeNames = [];
        foreach ($this->type->getTypeMap() as $typeKeyValue => $oneType) {
            $typeNames[] = $oneType->getName();
            $oneTypeClass = "\\" . Classname::toString($oneType->getName());

            $ret .= "            case '$typeKeyValue':\n";
            $ret .= "                \$this->value = new $oneTypeClass(\n";
            $ret .= "                    \$value,\n";
            $ret .= "                    \$ignoreRequired,\n";
            $ret .= "                    \$path,\n";
            $ret .= "                );\n";
            $ret .= "                break;\n";
        }

        $implodedTypeNames = implode(",", $typeNames);
        $ret .= "            default:\n";
        $ret .= "                throw new \\sndsgd\\schema\\exceptions\\RuleValidationException(\n";
        $ret .= "                    \$path,\n";
        $ret .= "                    \"must be oneof ($implodedTypeNames)\"\n";
        $ret .= "                );\n";
        $ret .= "         }\n";
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
