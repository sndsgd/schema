<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use PHPUnit\Framework\TestCase;
use sndsgd\schema\exceptions\RuleValidationException;
use sndsgd\yaml\ParserContext;
use UnexpectedValueException;

/**
 * @coversDefaultClass \sndsgd\schema\rules\MinLengthRule
 */
class MinLengthRuleTest extends TestCase
{
    public function testGetName(): void
    {
        $this->assertSame("minLength", MinLengthRule::getName());
    }

    public function testGetAcceptableTypes(): void
    {
        $this->assertSame(["string"], MinLengthRule::getAcceptableTypes());
    }

        public function testExecuteYamlCallback(): void
    {
        $result = MinLengthRule::executeYamlCallback("!rule/minLength", "123", 0, new ParserContext());
        $this->assertSame(["rule" => "minLength", "minLength" => 123], $result);
    }

    public function testExecuteYamlCallbackInvalidNameException(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("yaml tag name must be '!rule/minLength'");
        MinLengthRule::executeYamlCallback("nope", "", 0, new ParserContext());
    }

    public function testExecuteYamlCallbackValueException(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("the '!rule/minLength' can only be used with an integer");
        MinLengthRule::executeYamlCallback("!rule/minLength", [], 0, new ParserContext());
    }

    public function testConstructorminLengthInvalid(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("'minLength' must be greater than or equal to 1");
        new MinLengthRule(0);
    }

    public function testGetSummary(): void
    {
        $rule = new MinLengthRule(456);
        $this->assertSame("minLength:456", $rule->getSummary());
    }

    public function testGetDescription(): void
    {
        $rule = new MinLengthRule(123);
        $this->assertSame("must be at least 123 characters", $rule->getDescription());
    }

    public function testValidateFailure(): void
    {
        $rule = new MinLengthRule(4);
        $this->expectException(RuleValidationException::class);
        $this->expectExceptionMessage($rule->getDescription());
        $rule->validate("abc");
    }

    /**
     * @dataProvider provideValidate
     */
    public function testValidate($minLength, $value): void
    {
        $rule = new MinLengthRule($minLength);
        $this->assertSame($value, $rule->validate($value));
    }

    public function provideValidate(): array
    {
        return [
            [1, "a"],
            [1, "ab"],
            [2, "ab"],
            [2, "abc"],
            [42, str_repeat("a", 42)],
            [42, str_repeat("a", 1000)],
        ];
    }
}
