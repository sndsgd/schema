<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use PHPUnit\Framework\TestCase;
use sndsgd\schema\exceptions\RuleValidationException;

/**
 * @coversDefaultClass \sndsgd\schema\rules\FloatRule
 */
class FloatRuleTest extends TestCase
{
    public function testGetName(): void
    {
        $this->assertSame("float", FloatRule::getName());
    }

    public function testGetAcceptableTypes(): void
    {
        $this->assertSame([], FloatRule::getAcceptableTypes());
    }

    public function testGetSummary(): void
    {
        $rule = new FloatRule();
        $this->assertSame("type:float", $rule->getSummary());
    }

    public function testGetDescription(): void
    {
        $rule = new FloatRule();
        $this->assertSame("must be a float", $rule->getDescription());
    }

    /**
     * @dataProvider provideValidateFailure
     */
    public function testValidateFailure($value): void
    {
        $rule = new FloatRule();
        $this->expectException(RuleValidationException::class);
        $this->expectExceptionMessage($rule->getDescription());
        $rule->validate($value);
    }

    public function provideValidateFailure(): iterable
    {
        yield ["0.0.0"];
        yield [null];
        yield [[]];
        yield [(object) []];
    }

    /**
     * @dataProvider provideValidate
     */
    public function testValidate($value, float $expect): void
    {
        $rule = new FloatRule();
        $this->assertSame($expect, $rule->validate($value));
    }

    public function provideValidate(): iterable
    {
        yield "float zero" => [0.0, 0.0];
        yield "float one" => [1.0, 1.0];
        yield "float negative one" => [-1.0, -1.0];
        yield "long decimal" => [0.9999999, 0.9999999];
        yield "negative long decimal" => [-0.9999999, -0.9999999];

        yield "integer zero" => [0, 0.0];
        yield "integer forty-two" => [42, 42.0];
        yield "integer negative forty-two" => [-42, -42.0];
        yield "integer max" => [PHP_INT_MAX, floatval(PHP_INT_MAX)];
        yield "integer min" => [PHP_INT_MIN, floatval(PHP_INT_MIN)];

        yield "positive string" => ["123.456", 123.456];
        yield "negative string" => ["-123.456", -123.456];
        yield "large string" => [strval(PHP_INT_MAX), floatval(PHP_INT_MAX)];
        yield "marge negative string" => [strval(PHP_INT_MIN), floatval(PHP_INT_MIN)];
    }
}
