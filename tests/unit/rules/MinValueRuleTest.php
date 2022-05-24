<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use sndsgd\schema\exceptions\RuleValidationException;
use sndsgd\yaml\ParserContext;
use UnexpectedValueException;

class MinValueRuleTest extends TestCase
{
    public function testGetName(): void
    {
        $this->assertSame("minValue", MinValueRule::getName());
    }

    public function testGetAcceptableTypes(): void
    {
        $this->assertSame(["integer", "float"], MinValueRule::getAcceptableTypes());
    }

        public function testExecuteYamlCallback(): void
    {
        $result = MinValueRule::executeYamlCallback("!rule/min", "123", 0, new ParserContext());
        $this->assertSame(["rule" => "minValue", "minValue" => "123"], $result);
    }

    public function testExecuteYamlCallbackInvalidNameException(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("yaml tag name must be '!rule/min'");
        MinValueRule::executeYamlCallback("nope", "", 0, new ParserContext());
    }

    public function testExecuteYamlCallbackValueException(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("the '!rule/min' can only be used with a numeric value");
        MinValueRule::executeYamlCallback("!rule/min", [], 0, new ParserContext());
    }

    /**
     * @dataProvider provideConstructorminValueInvalid
     */
    public function testConstructorminValueInvalid($value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("'minValue' must be numeric");
        new MinValueRule($value);
    }

    public function provideConstructorminValueInvalid(): iterable
    {
        yield [null];
        yield [[]];
        yield [(object) []];
        yield [""];
        yield ["nope"];
    }

    public function testGetSummary(): void
    {
        $rule = new MinValueRule(456);
        $this->assertSame("min:456", $rule->getSummary());
    }

    public function testGetDescription(): void
    {
        $rule = new MinValueRule(123);
        $this->assertSame("must be greater than or equal to 123", $rule->getDescription());
    }

    /**
     * @dataProvider provideValidateFailure
     */
    public function testValidateFailure($minValue, $value): void
    {
        $rule = new MinValueRule($minValue);
        $this->expectException(RuleValidationException::class);
        $this->expectExceptionMessage($rule->getDescription());
        $rule->validate($value);
    }

    public function provideValidateFailure(): iterable
    {
        yield [".01", 0.0099999];
        yield ["1.0", 0.9999999];
        yield ["1.0", 0.9999999];
        yield [".999999999999", 0.999999999998];

        yield [100, 99.99999999];
    }

    /**
     * @dataProvider provideValidate
     */
    public function testValidate($minValue, $value): void
    {
        $rule = new MinValueRule($minValue);
        $this->assertSame($value, $rule->validate($value));
    }

    public function provideValidate(): iterable
    {
        yield [1, 1];
        yield [1.0, 1];
        yield ["1", 1];
        yield ["1.0000", 1];
        yield [2, 2];
        yield [2, 3];
        yield [42, 43];
    }
}
