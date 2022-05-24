<?php declare(strict_types=1);

namespace sndsgd\schema\types;

/**
 * @coversDefaultClass \sndsgd\schema\types\ObjectType
 */
class NestedObjectTest extends \PHPUnit\Framework\TestCase
{
    protected const DOC = <<<YAML
---
name: nestedObjectTest.Three
type: object
properties:
  foo:
    type: string
  bar:
    type: integer
required:
- foo
- bar
---
name: nestedObjectTest.Two
type: object
properties:
  three:
    type: nestedObjectTest.Three
required:
- three
---
name: nestedObjectTest.One
type: object
properties:
  two:
    type: nestedObjectTest.Two
required:
- two
---
name: nestedObjectTest.Test
type: object
properties:
  one:
    type: nestedObjectTest.One
required:
- one
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
            $instance = new \nestedObjectTest\Test($value, false, $path);
        } catch (\sndsgd\schema\ValidationFailure $ex) {
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
                [["path" => "$", "message" => "must be an object"]],
            ],
            [
                ["one" => 123],
                "$",
                [["path" => "$.one", "message" => "must be an object"]],
            ],
            [
                ["one" => ["two" => 123]],
                "$",
                [["path" => "$.one.two", "message" => "must be an object"]],
            ],
            [
                ["one" => ["two" => ["three" => 123]]],
                "$",
                [["path" => "$.one.two.three", "message" => "must be an object"]],
            ],
            [
                ["one" => ["two" => ["three" => ["foo" => [], "bar" => []]]]],
                "$",
                [
                    ["path" => "$.one.two.three.foo", "message" => "must be a string"],
                    ["path" => "$.one.two.three.bar", "message" => "must be an integer"],
                ],
            ],
            [
                ["one" => ["two" => ["three" => ["foo" => [], "bar" => []]]]],
                "$.test",
                [
                    ["path" => "$.test.one.two.three.foo", "message" => "must be a string"],
                    ["path" => "$.test.one.two.three.bar", "message" => "must be an integer"],
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
                (new \nestedObjectTest\Test($value))->jsonSerialize(),
            );
        } catch (\sndsgd\schema\ValidationFailure $ex) {
            $this->fail(
                "validation failed:\n" .
                yaml_emit($ex->getValidationErrors()->toArray()),
            );
        }
    }

    public function provideSuccess(): array
    {
        $three = ["foo" => "abc", "bar" => 123];
        $two = ["three" => $three];
        $one = ["two" => $two];

        return [
            [
                ["one" => $one],
                ["one" => $one],
            ],
        ];
    }
}
