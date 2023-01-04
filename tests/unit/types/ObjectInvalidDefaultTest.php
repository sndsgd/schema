<?php declare(strict_types=1);

namespace sndsgd\schema\types;

use PHPUnit\Framework\TestCase;
use sndsgd\schema\exceptions\ValidationException;
use Throwable;

class ObjectInvalidDefaultTest extends TestCase
{
    /**
     * @dataProvider provideInvalidDefault
     */
    public function testInvalidDefault(
      string $yaml,
      string $expectErrorMessage,
    ): void {
        $ex = null;

        try {
          createTestTypes($yaml);
        } catch (Throwable $ex) {
            // print_r($ex);
        }

        $this->assertInstanceOf(ValidationException::class, $ex);
        $this->assertSame($ex->getMessage(), "validation failed");

        // verify that the error message actually matches what went wrong
        $errors = $ex->getValidationErrors()->toArray();
        $this->assertStringContainsString(
            $expectErrorMessage,
            $errors[0]["message"],
        );
    }

    public function provideInvalidDefault(): iterable
    {
      yield [
        <<<YAML
        ---
        name: test.objectInvalidDefaultTest.StringLengthRuleFailure
        type: object
        properties:
          foo:
            type: string
            rules:
            - !rule/minLength 4
        defaults:
          foo: abc
        YAML,
        " failed to set default value for 'foo'; must be at least 4 characters",
      ];

      yield [
        <<<YAML
        ---
        name: test.objectInvalidDefaultTest.NonEmptyArray
        type: object
        properties:
          foo:
            type: array
            value: integer
        defaults:
          foo: [1]
        YAML,
        "cannot set default value for 'foo'; only scalar and empty arrays are acceptable defaults",
      ];

      yield [
        <<<YAML
        ---
        name: test.objectInvalidDefaultTest.NonEmptyArray
        type: object
        properties:
          foo:
            type: map
            key: !type string
            value: !type string
        defaults:
          foo: []
        YAML,
        "cannot set default value for 'foo'; only scalar and empty arrays are acceptable defaults",
      ];
    }
}
