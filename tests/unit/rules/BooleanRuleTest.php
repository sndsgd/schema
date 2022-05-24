<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use PHPUnit\Framework\TestCase;
use sndsgd\schema\exceptions\RuleValidationException;

/**
 * @coversDefaultClass \sndsgd\schema\rules\BooleanRule
 */
class BooleanRuleTest extends TestCase
{
    public function testGetName(): void
    {
        $this->assertSame("boolean", BooleanRule::getName());
    }

    public function testGetAcceptableTypes(): void
    {
        $this->assertSame([], BooleanRule::getAcceptableTypes());
    }

    public function testGetSummary(): void
    {
        $rule = new BooleanRule();
        $this->assertSame("type:boolean", $rule->getSummary());
    }

    public function testGetDescription(): void
    {
        $rule = new BooleanRule();
        $this->assertSame("must be a boolean", $rule->getDescription());
    }

    public function testValidateNonArray(): void
    {
        $rule = new BooleanRule();
        $this->expectException(RuleValidationException::class);
        $this->expectExceptionMessage($rule->getDescription());
        $rule->validate("not a boolean");
    }

    /**
     * @dataProvider provideValidate
     */
    public function testValidate($value, bool $expect): void
    {
        $rule = new BooleanRule();
        $this->assertSame($expect, $rule->validate($value));
    }

    public function provideValidate(): array
    {
        return [
            [true, true],
            [false, false],
            [1, true],
            [0, false],
            ["true", true],
            ["false", false],
        ];
    }
}
