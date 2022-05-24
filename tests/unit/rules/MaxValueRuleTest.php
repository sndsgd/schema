<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use sndsgd\schema\exceptions\RuleValidationException;
use sndsgd\yaml\ParserContext;
use UnexpectedValueException;

class MaxValueRuleTest extends TestCase
{
    public function testGetName(): void
    {
        $this->assertSame("maxValue", MaxValueRule::getName());
    }

    public function testGetAcceptableTypes(): void
    {
        $this->assertSame(["integer", "float"], MaxValueRule::getAcceptableTypes());
    }

        public function testExecuteYamlCallback(): void
    {
        $result = MaxValueRule::executeYamlCallback("!rule/max", "123", 0, new ParserContext());
        $this->assertSame(["rule" => "maxValue", "maxValue" => "123"], $result);
    }

    public function testExecuteYamlCallbackInvalidNameException(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("yaml tag name must be '!rule/max'");
        MaxValueRule::executeYamlCallback("nope", "", 0, new ParserContext());
    }

    public function testExecuteYamlCallbackValueException(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("the '!rule/max' can only be used with a numeric value");
        MaxValueRule::executeYamlCallback("!rule/max", [], 0, new ParserContext());
    }

    /**
     * @dataProvider provideConstructorMaxValueInvalid
     */
    public function testConstructorMaxValueInvalid($value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("'maxValue' must be numeric");
        new MaxValueRule($value);
    }

    public function provideConstructorMaxValueInvalid(): iterable
    {
        yield [null];
        yield [[]];
        yield [(object) []];
        yield [""];
        yield ["nope"];
    }

    public function testGetSummary(): void
    {
        $rule = new MaxValueRule(456);
        $this->assertSame("max:456", $rule->getSummary());
    }

    public function testGetDescription(): void
    {
        $rule = new MaxValueRule(123);
        $this->assertSame("must be less than or equal to 123", $rule->getDescription());
    }

    /**
     * @dataProvider provideValidateFailure
     */
    public function testValidateFailure($maxValue, $value): void
    {
        $rule = new MaxValueRule($maxValue);
        $this->expectException(RuleValidationException::class);
        $this->expectExceptionMessage($rule->getDescription());
        $rule->validate($value);
    }

    public function provideValidateFailure(): iterable
    {
        yield [".01", 0.01000001];
        yield ["0.01", 0.01000001];
        yield [".999999999999", 1];

        yield [100, 100.000001];
    }

    /**
     * @dataProvider provideValidate
     */
    public function testValidate($maxValue, $value): void
    {
        $rule = new MaxValueRule($maxValue);
        $this->assertSame($value, $rule->validate($value));
    }

    public function provideValidate(): iterable
    {
        yield [1, 1];
        yield [1.0, 1];
        yield ["1", 1];
        yield ["1.0000", 1];
        yield [2, 1];
        yield [2, 2];
        yield [42, 41];
    }
}
