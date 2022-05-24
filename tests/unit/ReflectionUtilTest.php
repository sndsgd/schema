<?php declare(strict_types=1);

namespace sndsgd\schema;

use PHPUnit\Framework\TestCase;
use ReflectionFunction;

class ReflectionUtilTest extends TestCase
{
    /**
     * @dataProvider provideNormalizeType
     */
    public function testNoramlizeType(
        string $input,
        string $expect,
    ): void {
        $this->assertSame($expect, ReflectionUtil::normalizeType($input));
    }

    public function provideNormalizeType(): iterable
    {
        yield ["integer", "integer"];
        yield ["int", "integer"];
        yield ["boolean", "boolean"];
        yield ["bool", "boolean"];
    }

    /**
     * @dataProvider provideGetParameterTypes
     */
    public function testGetParameterTypes(
        callable $function,
        array $expect,
    ): void {
        $rf = new ReflectionFunction($function);
        $this->assertCount(1, $rf->getParameters());
        $this->assertSame(
            $expect,
            ReflectionUtil::getParameterTypes($rf->getParameters()[0]),
        );
    }

    public function provideGetParameterTypes(): iterable
    {
        yield [
            static function (string $value) {},
            ["string"],
        ];

        yield [
            static function (bool $value) {},
            ["boolean"],
        ];

        yield [
            static function (int $value) {},
            ["integer"],
        ];

        yield [
            static function (float $value) {},
            ["float"],
        ];

        yield [
            static function (array $value) {},
            ["array"],
        ];

        yield [
            static function (object $value) {},
            ["object"],
        ];

        yield [
            static function (?string $value) {},
            ["null", "string"],
        ];

        yield [
            static function (?bool $value) {},
            ["boolean", "null"],
        ];

        yield [
            static function (?int $value) {},
            ["integer", "null"],
        ];

        yield [
            static function (?float $value) {},
            ["float", "null"],
        ];

        yield [
            static function (?array $value) {},
            ["array", "null"],
        ];

        yield [
            static function (?object $value) {},
            ["null", "object"],
        ];

        yield [
            static function(string|int $value) {},
            ["integer", "string"],
        ];

        yield [
            static function(string|int |null $value) {},
            ["integer", "null", "string"],
        ];
    }
}
