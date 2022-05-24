<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use PHPUnit\Framework\TestCase;
use sndsgd\schema\exceptions\RuleValidationException;

/**
 * @coversDefaultClass \sndsgd\schema\rules\ObjectRule
 */
class ObjectRuleTest extends TestCase
{
    public function testGetName(): void
    {
        $this->assertSame("object", ObjectRule::getName());
    }

    public function testGetAcceptableTypes(): void
    {
        $this->assertSame([], ObjectRule::getAcceptableTypes());
    }

    public function testGetSummary(): void
    {
        $rule = new ObjectRule();
        $this->assertSame("type:object", $rule->getSummary());
    }

    public function testGetDescription(): void
    {
        $rule = new ObjectRule();
        $this->assertSame("must be an object", $rule->getDescription());
    }

    /**
     * @dataProvider provideNonObject
     */
    public function testNonObject($value): void
    {
        $rule = new ObjectRule();
        $this->expectException(RuleValidationException::class);
        $this->expectExceptionMessage($rule->getDescription());
        $rule->validate($value);
    }

    public function provideNonObject(): iterable
    {
        yield "string" => ["hello"];
        yield "integer" => [42];
        yield "float" => [4.2];
        yield "empty array" => [[]];
        yield "non associative array" => [["foo", "bar", "baz"]];
    }

    /**
     * @dataProvider provideValidate
     */
    public function testValidate($value): void
    {
        $rule = new ObjectRule();
        $this->assertEquals((object) $value, $rule->validate($value));
    }

    public function provideValidate(): array
    {
        return [
            [(object) ["testing" => 123]],
            [["foo" => 123, "bar" => [], "baz" => (object) []]],
        ];
    }
}
