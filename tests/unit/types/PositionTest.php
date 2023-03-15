<?php declare(strict_types=1);

namespace sndsgd\schema\types;

use PHPUnit\Framework\TestCase;
use sndsgd\schema\ValidationFailure;
use test\types\Position;

/**
 * @coversNothing
 */
class PositionTest extends TestCase
{
    protected const DOC = <<<YAML
---
name: test.types.Latitude
type: float
rules:
- rule: minValue
  minValue: -90
- rule: maxValue
  maxValue: 90
---
name: test.types.Longitude
type: float
rules:
- rule: minValue
  minValue: -180
- rule: maxValue
  maxValue: 180
---
name: test.types.Altitude
type: float
---
name: test.types.Coordinates
type: object
properties:
  latitude:
    type: test.types.Latitude
  longitude:
    type: test.types.Longitude
required:
- latitude
- longitude
---
name: test.types.Position
type: object
properties:
  coordinates:
    type: test.types.Coordinates
  altitude:
    type: test.types.Altitude
required:
- coordinates
- altitude
YAML;

    public function setup(): void
    {
        createTestTypes(self::DOC);
    }

    /**
     * @dataProvider provideErrors
     */
    public function testErrors($value, string $path, array $errors): void
    {
        // initialize these to keep static analysis from complaining
        $instance = null;
        $ex = null;

        try {
            $instance = new Position($value, false, $path);
        } catch (ValidationFailure $ex) {
            // do nothing; inspect the errors below
        }

        $this->assertNull($instance);
        $this->assertSame($errors, $ex->getValidationErrors()->toArray());
    }

    public function provideErrors(): array
    {
        return [
            [
                [],
                "$",
                [["path" => "$", "message" => "must be an object"]],
            ],
            [
                "hello",
                "$.thing",
                [["path" => "$.thing", "message" => "must be an object"]],
            ],
            [
                null,
                "$.test",
                [["path" => "$.test", "message" => "must be an object"]],
            ],
            [
                123,
                "$.nested.name",
                [["path" => "$.nested.name", "message" => "must be an object"]],
            ],
            [
                [
                    "coordinates" => [
                        "latitude" => 99,
                        "longitude" => 20,
                    ],
                    "altitude" => [],
                ],
                "$",
                [
                    [
                        "path" => "$.coordinates.latitude",
                        "message" => "must be less than or equal to 90",
                    ],
                    [
                        "path" => "$.altitude",
                        "message" => "must be a float",
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideSuccess
     */
    public function testSuccess(
        $value,
        $expectLatitude,
        $expectLongitude,
        $expectAltitude,
    ): void
    {
        try {
            $instance = new Position($value);
        } catch (ValidationFailure $ex) {
            $this->fail(
                "validation failed:\n" .
                yaml_emit($ex->getValidationErrors()->toArray()),
            );
        }

        $coordinates = $instance->getCoordinates();
        $this->assertSame($expectLatitude, $coordinates->getLatitude());
        $this->assertSame($expectLatitude, $coordinates->getLatitude());
        $this->assertSame($expectAltitude, $instance->getAltitude());
    }

    public function provideSuccess(): array
    {
        return [
            [
                [
                    "coordinates" => [
                        "latitude" => 1,
                        "longitude" => 2,
                    ],
                    "altitude" => 99.99,
                ],
                1.0,
                2.0,
                99.99,
            ],
            [
                [
                    "coordinates" => [
                        "latitude" => 90,
                        "longitude" => -180,
                    ],
                    "altitude" => 123,
                ],
                90.0,
                -180.0,
                123.0,
            ],
        ];
    }
}
