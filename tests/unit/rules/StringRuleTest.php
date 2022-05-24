<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use PHPUnit\Framework\TestCase;
use sndsgd\schema\exceptions\RuleValidationException;

/**
 * @coversDefaultClass \sndsgd\schema\rules\StringRule
 */
class StringRuleTest extends TestCase
{
    public function testGetName(): void
    {
        $this->assertSame("string", StringRule::getName());
    }

    public function testGetAcceptableTypes(): void
    {
        $this->assertSame([], StringRule::getAcceptableTypes());
    }

    public function testGetSummary(): void
    {
        $rule = new StringRule();
        $this->assertSame("type:string", $rule->getSummary());
    }

    public function testGetDescription(): void
    {
        $rule = new StringRule();
        $this->assertSame("must be a string", $rule->getDescription());
    }

    public function testValidateNonString(): void
    {
        $rule = new StringRule();
        $this->expectException(RuleValidationException::class);
        $this->expectExceptionMessage($rule->getDescription());
        $rule->validate([]);
    }

    /**
     * @dataProvider provideValidate
     */
    public function testValidate($value): void
    {
        $rule = new StringRule();
        $this->assertSame($value, $rule->validate($value));
    }

    public function provideValidate(): array
    {
        return [
            ["yes, this is a string"],
            [""],
            ["1234"],
        ];
    }
}
