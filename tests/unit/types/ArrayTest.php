<?php declare(strict_types=1);

namespace sndsgd\schema\types;

/**
 * @coversDefaultClass \sndsgd\schema\types\ArrayType
 */
class ArrayTypeTest extends \PHPUnit\Framework\TestCase
{
    protected const DOC = <<<YAML
---
name: type.TestArray
type: array
value:
  type: string
  rules:
  - !rule/maxLength 10
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
            $instance = new \type\TestArray($value, $path);
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
                [["path" => "$.0", "message" => "must be a string"]],
            ],
            [
                [[1]],
                "$",
                [["path" => "$.0", "message" => "must be a string"]],
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
                (new \type\TestArray($value))->jsonSerialize(),
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
        return [
            [
                ["foo", "bar", "baz"],
                ["foo", "bar", "baz"],
            ],
        ];
    }

    public function testArrayAccess(): void
    {
        $value = new \type\TestArray(["zero", "one", "two"]);
        $this->assertSame("zero", $value[0]);
        $this->assertSame("one", $value[1]);
        $this->assertSame("two", $value[2]);
    }

    public function testForeach(): void
    {
        $expect = ["zero", "one", "two"];
        $result = [];
        foreach (new \type\TestArray($expect) as $key => $value) {
            $result[$key] = $value;
        }
        $this->assertSame($expect, $result);
    }
}
