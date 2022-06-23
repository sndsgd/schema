<?php declare(strict_types=1);

namespace sndsgd\schema\types;

use sndsgd\schema\exceptions\ValidationException;

/**
 * @coversNothing
 */
class ObjectInvalidDefaultTest extends \PHPUnit\Framework\TestCase
{
    public function testInvalidDefault(): void
    {
        $ex = null;

        try {
          createTestTypes(
            <<<YAML
            ---
            name: test.ObjectInvalidDefaultTest
            type: object
            properties:
              foo:
                type: string
                rules:
                - !rule/minLength 4
            defaults:
              foo: abc
            YAML
          );
        } catch (\Throwable $ex) {
            //
        }

        $this->assertInstanceOf(ValidationException::class, $ex);
        $this->assertSame($ex->getMessage(), "validation failed");

        // verify that the error message actually matches what went wrong
        $errors = $ex->getValidationErrors()->toArray();
        $this->assertStringContainsString(
            "value for 'foo'; must be at least 4 characters",
            $errors[0]["message"]
        );
    }
}
