<?php declare(strict_types=1);

namespace sndsgd\schema\types;

use PHPUnit\Framework\TestCase;
use sndsgd\schema\exceptions\ErrorListException;
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
            // do nothing; we inspect the exception below
        }

        $this->assertInstanceOf(ErrorListException::class, $ex);
        assert($ex instanceof ErrorListException); // ugh phpstan
        $this->assertSame($ex->getMessage(), "type definition errors encountered");

        // verify that the error message actually matches what went wrong
        $errors = json_encode($ex->getErrorList()->getErrors());
        $this->assertStringContainsString(
            $expectErrorMessage,
            $errors,
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
            key: string
            value: string
        defaults:
          foo: []
        YAML,
        "cannot set default value for 'foo'; only scalar and empty arrays are acceptable defaults",
      ];
    }
}
