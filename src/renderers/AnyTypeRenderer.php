<?php declare(strict_types=1);

namespace sndsgd\schema\renderers;

use sndsgd\schema\types\AnyType;

class AnyTypeRenderer
{
    private AnyType $type;
    private string $typehint;

    public function __construct(AnyType $type)
    {
        $this->type = $type;
        $this->typehint = RenderHelper::getTypeHint($type);
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
        $ret .= "    private $this->typehint \$value;\n";
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
        $ret .= "    public function __construct(\$value, string \$path = \"\$\")\n";
        $ret .= "    {\n";

        foreach ($this->type->getRules()->toArray() as $rule) {
            $ret .= RenderHelper::renderRuleCreateAndValidate($rule, "value");
        }

        $ret .= "        \$this->value = \$value;\n";
        $ret .= "    }\n";

        return $ret;
    }

    private function renderJsonSerialize()
    {
        $ret = "";
        $ret .= "    /** @see https://www.php.net/manual/en/class.jsonserializable.php */\n";
        $ret .= "    public function jsonSerialize(): {$this->typehint}\n";
        $ret .= "    {\n";
        $ret .= "        return \$this->value;\n";
        $ret .= "    }\n";

        return $ret;
    }

    private function renderGetter(): string
    {
        $ret = "";
        $ret .= "    public function getValue(): {$this->typehint}\n";
        $ret .= "    {\n";
        $ret .= "        return \$this->value;\n";
        $ret .= "    }\n";

        return $ret;
    }
}
