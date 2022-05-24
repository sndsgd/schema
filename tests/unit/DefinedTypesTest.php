<?php declare(strict_types=1);

namespace sndsgd\schema;

use sndsgd\schema\exceptions\DuplicateTypeException;

/**
 * @coversDefaultClass \sndsgd\schema\DefinedTypes
 */
class DefinedTypesTest extends \PHPUnit\Framework\TestCase
{
    public function testGetBaseTypes(): void
    {
        $baseTypes = DefinedTypes::getBaseTypes();
        $this->assertSame(
            [
                "sndsgd.types.StringType",
                "sndsgd.types.BooleanType",
                "sndsgd.types.IntegerType",
                "sndsgd.types.FloatType",
                "sndsgd.types.ArrayType",
                "sndsgd.types.ObjectType",
                "sndsgd.types.MapType",
                "sndsgd.types.OneOfType",
                "sndsgd.types.OneOfObjectType",
                "sndsgd.types.AnyType",
            ],
            array_keys($baseTypes),
        );
    }

    public function testAddTypeDuplicateTypeException(): void
    {
        $types = DefinedTypes::create();

        $name = "sndsgd.types.StringType";
        $type = $types->getType($name);

        $this->expectException(DuplicateTypeException::class);
        $this->expectExceptionMessage("type '$name' is already defined");
        $types->addType($type);
    }
}
