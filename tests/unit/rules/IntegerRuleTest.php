<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use PHPUnit\Framework\TestCase;
use sndsgd\schema\exceptions\RuleValidationException;

/**
 * @coversDefaultClass \sndsgd\schema\rules\IntegerRule
 */
class IntegerRuleTest extends TestCase
{
    public function testGetName(): void
    {
        $this->assertSame("integer", IntegerRule::getName());
    }

    public function testGetAcceptableTypes(): void
    {
        $this->assertSame([], IntegerRule::getAcceptableTypes());
    }

    public function testGetSummary(): void
    {
        $rule = new IntegerRule();
        $this->assertSame("type:integer", $rule->getSummary());
    }

    public function testGetDescription(): void
    {
        $rule = new IntegerRule();
        $this->assertSame("must be an integer", $rule->getDescription());
    }

    public function testValidateNonString(): void
    {
        $rule = new IntegerRule();
        $this->expectException(RuleValidationException::class);
        $this->expectExceptionMessage($rule->getDescription());
        $rule->validate([]);
    }

    /**
     * @dataProvider provideValidate
     */
    public function testValidate($value): void
    {
        $rule = new IntegerRule();
        $this->assertSame($value, $rule->validate($value));
    }

    public function provideValidate(): array
    {
        return [
            [0],
            [-1],
            [1],
            [-42],
            [42],
            [PHP_INT_MIN],
            [PHP_INT_MAX],
        ];
    }
}
