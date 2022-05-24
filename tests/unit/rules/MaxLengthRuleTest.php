<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use PHPUnit\Framework\TestCase;
use sndsgd\schema\exceptions\RuleValidationException;
use sndsgd\yaml\ParserContext;
use UnexpectedValueException;

/**
 * @coversDefaultClass \sndsgd\schema\rules\MaxLengthRule
 */
class MaxLengthRuleTest extends TestCase
{
    public function testGetName(): void
    {
        $this->assertSame("maxLength", MaxLengthRule::getName());
    }

    public function testGetAcceptableTypes(): void
    {
        $this->assertSame(["string"], MaxLengthRule::getAcceptableTypes());
    }

    public function testExecuteYamlCallback(): void
    {
        $result = MaxLengthRule::executeYamlCallback("!rule/maxLength", "123", 0, new ParserContext());
        $this->assertSame(["rule" => "maxLength", "maxLength" => 123], $result);
    }

    public function testExecuteYamlCallbackInvalidNameException(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("yaml tag name must be '!rule/maxLength'");
        MaxLengthRule::executeYamlCallback("nope", "", 0, new ParserContext());
    }

    public function testExecuteYamlCallbackValueException(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("the '!rule/maxLength' can only be used with an integer");
        MaxLengthRule::executeYamlCallback("!rule/maxLength", [], 0, new ParserContext());
    }

    public function testConstructorMaxLengthInvalid(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("'maxLength' must be greater than or equal to 1");
        new MaxLengthRule(0);
    }

    public function testGetSummary(): void
    {
        $rule = new MaxLengthRule(456);
        $this->assertSame("maxLength:456", $rule->getSummary());
    }

    public function testGetDescription(): void
    {
        $rule = new MaxLengthRule(123);
        $this->assertSame("must be no longer than 123 characters", $rule->getDescription());
    }

    public function testValidateFailure(): void
    {
        $rule = new MaxLengthRule(2);
        $this->expectException(RuleValidationException::class);
        $this->expectExceptionMessage($rule->getDescription());
        $rule->validate("abc");
    }

    /**
     * @dataProvider provideValidate
     */
    public function testValidate($maxLength, $value): void
    {
        $rule = new MaxLengthRule($maxLength);
        $this->assertSame($value, $rule->validate($value));
    }

    public function provideValidate(): array
    {
        return [
            [1, "a"],
            [2, "a"],
            [2, "ab"],
            [42, str_repeat("a", 42)],
        ];
    }
}
