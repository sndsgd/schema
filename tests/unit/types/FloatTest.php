<?php declare(strict_types=1);

namespace sndsgd\schema\types;

use floatTest\Test;
use PHPUnit\Framework\TestCase;
use sndsgd\schema\ValidationFailure;

/**
 * @coversDefaultClass \sndsgd\schema\types\FloatTest
 */
class FloatTest extends TestCase
{
    protected const DOC = <<<YAML
name: floatTest.Test
type: float
rules:
- rule: minValue
  minValue: -90
- rule: maxValue
  maxValue: 90
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
                [],
                "$",
                [["path" => "$", "message" => "must be a float"]],
            ],
            [
                "hello",
                "$.latitude",
                [["path" => "$.latitude", "message" => "must be a float"]],
            ],
            [
                null,
                "$.test",
                [["path" => "$.test", "message" => "must be a float"]],
            ],
            [
                90.00001,
                "$.max",
                [["path" => "$.max", "message" => "must be less than or equal to 90"]],
            ],
            [
                -90.00001,
                "$.min",
                [["path" => "$.min", "message" => "must be greater than or equal to -90"]],
            ],
        ];
    }

    /**
     * @dataProvider provideSuccess
     */
    public function testSuccess($value, float $expect): void
    {
        try {
            $this->assertSame(
                $expect,
                (new Test($value, "$"))->getValue(),
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
            [0, 0.0],
            [0.0, 0.0],
            [1, 1.0],
            [-90, -90.0],
            [90, 90.0],
        ];
    }
}
