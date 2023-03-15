<?php declare(strict_types=1);

namespace sndsgd\schema\types;

use objectArrayObject\Top;
use PHPUnit\Framework\TestCase;
use sndsgd\schema\ValidationFailure;

/**
 * @coversNothing
 */
class ObjectArrayObjectTest extends TestCase
{
    protected const DOC = <<<YAML
---
name: objectArrayObject.Bottom
type: object
properties:
  foo:
    type: string
  bar:
    type: integer
required:
- foo
---
name: objectArrayObject.Middle
type: array
value:
  type: objectArrayObject.Bottom
---
name: objectArrayObject.Top
type: object
properties:
  middle:
    type: objectArrayObject.Middle
required:
- middle
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
            $instance = new Top($value, false, $path);
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
                [["path" => "$", "message" => "must be an object"]],
            ],
            [
                ["middle" => 123],
                "$",
                [["path" => "$.middle", "message" => "must be an array"]],
            ],
            [
                ["middle" => [123]],
                "$",
                [["path" => "$.middle.0", "message" => "must be an object"]],
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
                (new Top($value))->jsonSerialize(),
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
                [
                    "middle" => [
                        ["foo" => "asd"],
                        ["foo" => "hello", "bar" => "123"],
                    ],
                ],
                [
                    "middle" => [
                        ["foo" => "asd"],
                        ["foo" => "hello", "bar" => 123],
                    ],
                ],
            ],
        ];
    }

    public function testGetters(): void
    {
        $value = new Top(
            [
                "middle" => [
                    ["foo" => "testing..."],
                    ["foo" => "hello", "bar" => "9999999"],
                ],
            ],
        );

        $middle = $value->getMiddle();
        $this->assertIsArray($middle);

        $this->assertSame("testing...", $middle[0]->getFoo());
        $this->assertFalse($middle[0]->hasBar());

        $this->assertSame("hello", $middle[1]->getFoo());
        $this->assertTrue($middle[1]->hasBar());
        $this->assertSame(9999999, $middle[1]->getBar());
    }
}
