<?php declare(strict_types=1);

namespace sndsgd\schema\types;

use PHPUnit\Framework\TestCase;
use sndsgd\schema\RuleList;
use sndsgd\schema\rules\ArrayRule;
use sndsgd\schema\rules\IntegerRule;
use sndsgd\schema\rules\StringRule;
use sndsgd\schema\types\ArrayType;
use sndsgd\schema\types\ScalarType;
use Throwable;

/**
 * @coversDefaultClass \sndsgd\schema\types\OneOfType
 */
class OneOfTypeTest extends TestCase
{
    /**
     * @dataProvider provideLessThanTwoTypesException
     */
    public function testLessThanTwoTypesException(array $types)
    {
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage("a 'oneof' must be defined with at least two types");

        new OneOfType(
            "name",
            "description",
            ...$types,
        );
    }

    public function provideLessThanTwoTypesException(): array
    {
        return [
            "zero types" => [[]],
            "one type" => [
                [
                    new ScalarType(
                        "name",
                        "description",
                        new RuleList(new StringRule()),
                        ScalarType::BASE_STRING_CLASSNAME,
                    ),
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideDuplicateTypeException
     */
    public function testDuplicateTypeException(
        array $types,
        string $expectExceptionMessage,
    ): void {
        $this->expectException(Throwable::class);
        $this->expectExceptionMessage($expectExceptionMessage);

        new OneOfType(
            "name",
            "description",
            ...$types,
        );
    }

    public function provideDuplicateTypeException(): array
    {
        return [
            "two strings" => [
                [
                    new ScalarType(
                        "first",
                        "description",
                        new RuleList(new StringRule()),
                        ScalarType::BASE_STRING_CLASSNAME,
                    ),
                    new ScalarType(
                        "second",
                        "description",
                        new RuleList(new StringRule()),
                        ScalarType::BASE_STRING_CLASSNAME,
                    ),
                ],
                "failed to add 'second'; "
                . "the type 'string' is already defined by 'first'",
            ],
            "two integers" => [
                [
                    new ScalarType(
                        "foo",
                        "description",
                        new RuleList(new IntegerRule()),
                        ScalarType::BASE_INTEGER_CLASSNAME,
                    ),
                    new ScalarType(
                        "bar",
                        "description",
                        new RuleList(new IntegerRule()),
                        ScalarType::BASE_INTEGER_CLASSNAME,
                    ),
                ],
                "failed to add 'bar'; "
                . "the type 'integer' is already defined by 'foo'",
            ],
            "two arrays of integers" => [
                [
                    new ArrayType(
                        "arr.First",
                        "description",
                        new RuleList(new ArrayRule()),
                        new ScalarType(
                            "foo_Value",
                            "description",
                            new RuleList(new IntegerRule()),
                            ScalarType::BASE_INTEGER_CLASSNAME,
                        ),
                    ),
                    new ArrayType(
                        "arr.Second",
                        "description",
                        new RuleList(new ArrayRule()),
                        new ScalarType(
                            "foo_Value",
                            "description",
                            new RuleList(new IntegerRule()),
                            ScalarType::BASE_INTEGER_CLASSNAME,
                        ),
                    ),
                ],
                "failed to add 'arr.Second'; "
                . "the type 'array<integer>' is already defined by 'arr.First'",
            ],
        ];
    }
}
