<?php declare(strict_types=1);

namespace sndsgd\schema\types;

use nestedArrayTest\Test;
use PHPUnit\Framework\TestCase;
use sndsgd\schema\ValidationFailure;

/**
 * @coversDefaultClass \sndsgd\schema\types\ArrayType
 */
class NestedArrayTest extends TestCase
{
    protected const DOC = <<<YAML
---
name: nestedArrayTest.Value
type: integer
rules:
- !rule/min 0
- !rule/max 10
---
name: nestedArrayTest.Two
type: array
value:
  type: nestedArrayTest.Value
---
name: nestedArrayTest.One
type: array
value:
  type: nestedArrayTest.Two
---
name: nestedArrayTest.Test
type: array
value:
  type: nestedArrayTest.One
YAML;

    public function setup(): void
    {
        createTestTypes(self::DOC);
    }

    /**
     * @dataProvider provideErrors
     */
    public function testErrors($value, string $path, array $expectErrors): void
    {
        // initialize these to keep static analysis from complaining
        $instance = null;
        $ex = null;

        try {
            $instance = new Test($value, $path);
        } catch (ValidationFailure $ex) {
            // do nothing; inspect the errors below
        }

        $this->assertNull($instance);
        $this->assertSame($expectErrors, $ex->getValidationErrors()->toArray());
    }

    public function provideErrors(): array
    {
        return [
            [
                123,
                "$",
                [["path" => "$", "message" => "must be an array"]],
            ],
            [
                123.456,
                "$.foo",
                [["path" => "$.foo", "message" => "must be an array"]],
            ],
            [
                "hello",
                "$.foo.bar",
                [["path" => "$.foo.bar", "message" => "must be an array"]],
            ],
            [
                null,
                "$.test",
                [["path" => "$.test", "message" => "must be an array"]],
            ],
            [
                [1],
                "$",
                [["path" => "$.0", "message" => "must be an array"]],
            ],
            [
                [[1]],
                "$",
                [["path" => "$.0.0", "message" => "must be an array"]],
            ],
            [
                [[[-1, 1, 10, 11]]],
                "$",
                [
                    ["path" => "$.0.0.0", "message" => "must be greater than or equal to 0"],
                    ["path" => "$.0.0.3", "message" => "must be less than or equal to 10"],
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideSuccess
     */
    public function testSuccess($value, array $expect): void
    {
        try {
            $this->assertSame(
                $expect,
                (new Test($value))->jsonSerialize(),
            );
        } catch (ValidationFailure $ex) {
            $this->fail(
                "validation failed:\n" .
                yaml_emit($ex->getValidationErrors()->toArray()),
            );
        }
    }

    public function provideSuccess(): array
    {
        return [
            [
                [[[1,2,3]]],
                [[[1,2,3]]],
            ],
            [
                [[["1", 2, 3]]],
                [[[1, 2, 3]]],
            ],
            [
                [[["1", "2", "3"]]],
                [[[1, 2, 3]]],
            ],
        ];
    }

    public function testGetters(): void
    {
        $value = new Test([[["1", "2", "3"]]]);
        $this->assertSame([[1, 2, 3]], $value[0]->jsonSerialize());
        $this->assertSame([1, 2, 3], $value[0][0]->jsonSerialize());
        $this->assertSame(1, $value[0][0][0]);
        $this->assertSame(2, $value[0][0][1]);
        $this->assertSame(3, $value[0][0][2]);
    }
}
