<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use PHPUnit\Framework\TestCase;
use sndsgd\schema\exceptions\RuleValidationException;

/**
 * @coversDefaultClass \sndsgd\schema\rules\ArrayRule
 */
class ArrayRuleTest extends TestCase
{
    public function testGetName(): void
    {
        $this->assertSame("array", ArrayRule::getName());
    }

    public function testGetAcceptableTypes(): void
    {
        $this->assertSame([], ArrayRule::getAcceptableTypes());
    }

    public function testGetSummary(): void
    {
        $rule = new ArrayRule();
        $this->assertSame("type:array", $rule->getSummary());
    }

    public function testGetDescription(): void
    {
        $rule = new ArrayRule();
        $this->assertSame("must be an array", $rule->getDescription());
    }

    public function testValidateNonArray(): void
    {
        $rule = new ArrayRule();
        $this->expectException(RuleValidationException::class);
        $this->expectExceptionMessage($rule->getDescription());
        $rule->validate("not an array");
    }

    public function testAssociativeArray(): void
    {
        $rule = new ArrayRule();
        $this->expectException(RuleValidationException::class);
        $this->expectExceptionMessage($rule->getDescription());
        $rule->validate([0 => 123, "foo" => "bar"]);
    }

    /**
     * @dataProvider provideValidate
     */
    public function testValidate($value): void
    {
        $rule = new ArrayRule();
        $this->assertSame($value, $rule->validate($value));
    }

    public function provideValidate(): array
    {
        return [
            [[1, 2, 3]],
            [["foo", "bar", "baz"]],
            [[0 => "zero", 1 => "one", 2 => "two", 3 => "three"]],
        ];
    }
}
