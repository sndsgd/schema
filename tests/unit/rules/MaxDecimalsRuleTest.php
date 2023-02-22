<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use sndsgd\schema\exceptions\RuleValidationException;
use sndsgd\yaml\ParserContext;
use UnexpectedValueException;

class MaxDecimalsRuleTest extends TestCase
{
    public function testGetName(): void
    {
        $this->assertSame("maxDecimals", MaxDecimalsRule::getName());
    }

    public function testGetAcceptableTypes(): void
    {
        $this->assertSame(["float"], MaxDecimalsRule::getAcceptableTypes());
    }

        public function testExecuteYamlCallback(): void
    {
        $result = MaxDecimalsRule::executeYamlCallback("!rule/maxDecimals", "10", 0, new ParserContext());
        $this->assertSame(["rule" => "maxDecimals", "maxDecimals" => 10], $result);
    }

    public function testExecuteYamlCallbackInvalidNameException(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("yaml tag name must be '!rule/maxDecimals'");
        MaxDecimalsRule::executeYamlCallback("nope", "", 0, new ParserContext());
    }

    public function testExecuteYamlCallbackValueException(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("the '!rule/maxDecimals' can only be used with a single integer value");
        MaxDecimalsRule::executeYamlCallback("!rule/maxDecimals", [], 0, new ParserContext());
    }

    /**
     * @dataProvider provideConstructorMaxDecimalsInvalid
     */
    public function testConstructorMaxDecimalsInvalid(int $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("'maxDecimals' must be a greater than 0 and less than 24");
        new MaxDecimalsRule($value);
    }

    public function provideConstructorMaxDecimalsInvalid(): iterable
    {
        yield [-1];
        yield [25];
    }

    public function testGetSummary(): void
    {
        $rule = new MaxDecimalsRule(8);
        $this->assertSame("maxDecimals:8", $rule->getSummary());
    }

    public function testGetDescription(): void
    {
        $rule = new MaxDecimalsRule(17);
        $this->assertSame("must use no more than 17 decimal places", $rule->getDescription());
    }

    /**
     * @dataProvider provideValidateFailure
     */
    public function testValidateFailure($maxDecimals, $value): void
    {
        $rule = new MaxDecimalsRule($maxDecimals);
        $this->expectException(RuleValidationException::class);
        $this->expectExceptionMessage($rule->getDescription());
        $rule->validate($value);
    }

    public function provideValidateFailure(): iterable
    {
        yield [1, 0.01];
        yield [2, 0.002];
        yield [3, 0.0003];
    }

    /**
     * @dataProvider provideValidate
     */
    public function testValidate($maxDecimals, $value): void
    {
        $rule = new MaxDecimalsRule($maxDecimals);
        $this->assertSame($value, $rule->validate($value));
    }

    public function provideValidate(): iterable
    {
        yield [1, 1.1];
        yield [2, 2.02];
    }
}
