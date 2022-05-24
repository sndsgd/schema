<?php declare(strict_types=1);

namespace sndsgd\schema;

use sndsgd\yaml\ParserContext;

class YamlParserContext extends ParserContext
{
    private DefinedTypes $types;

    public function __construct(DefinedTypes $types)
    {
        $this->types = $types;
    }

    public function getDefinedTypes(): DefinedTypes
    {
        return $this->types;
    }
}
