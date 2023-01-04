<?php declare(strict_types=1);

namespace sndsgd\schema\types;

use PHPUnit\Framework\TestCase;
use sndsgd\schema\ValidationFailure;
use stdClass;
use test\TestString;

/**
 * @coversDefaultClass \sndsgd\schema\types\StringType
 */
class StringTest extends TestCase
{
    protected const DOC = <<<YAML
name: test.TestString
type: string
rules:
- !rule/minLength 1
- !rule/maxLength 2
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
            $instance = new TestString($value, $path);
            return;
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
                [["path" => "$", "message" => "must be a string"]],
            ],
            [
                123.456,
                "$.foo",
                [["path" => "$.foo", "message" => "must be a string"]],
            ],
            [
                [],
                "$.test",
                [["path" => "$.test", "message" => "must be a string"]],
            ],
            [
                new stdClass(),
                "$.foo.bar",
                [["path" => "$.foo.bar", "message" => "must be a string"]],
            ],
            [
                "123",
                "$",
                [["path" => "$", "message" => "must be no longer than 2 characters"]],
            ],
            [
                "",
                "$",
                [["path" => "$", "message" => "must be at least 1 characters"]],
            ],
        ];
    }

    /**
     * @dataProvider provideSuccess
     */
    public function testSuccess($value, string $expect): void
    {
        try {
            $this->assertSame(
                $expect,
                (new TestString($value, "$"))->getValue(),
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
            ["a", "a"],
            ["1", "1"],
            ["ab", "ab"],
            ["12", "12"],
        ];
    }
}
