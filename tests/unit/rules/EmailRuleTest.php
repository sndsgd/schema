<?php declare(strict_types=1);

namespace sndsgd\schema\rules;

use LogicException;
use PHPUnit\Framework\TestCase;
use sndsgd\schema\exceptions\RuleValidationException;
use sndsgd\yaml\ParserContext;
use UnexpectedValueException;

/**
 * @coversDefaultClass \sndsgd\schema\rules\EmailRule
 */
class EmailRuleTest extends TestCase
{
    public function testGetName(): void
    {
        $this->assertSame("email", EmailRule::getName());
    }

    public function testGetAcceptableTypes(): void
    {
        $this->assertSame(["string"], EmailRule::getAcceptableTypes());
    }

    public function testExecuteYamlCallback(): void
    {
        $result = EmailRule::executeYamlCallback("!rule/email", "", 0, new ParserContext());
        $this->assertSame(["rule" => "email"], $result);
    }

    public function testExecuteYamlCallbackInvalidNameException(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage("yaml tag name must be '!rule/email'");
        EmailRule::executeYamlCallback("nope", "", 0, new ParserContext());
    }

    public function testExecuteYamlCallbackValueException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("the '!rule/email' yaml callback cannot be used with a value");
        EmailRule::executeYamlCallback("!rule/email", "nope", 0, new ParserContext());
    }

    public function testGetSummary(): void
    {
        $rule = new EmailRule();
        $this->assertSame("email", $rule->getSummary());
    }

    public function testGetDescription(): void
    {
        $rule = new EmailRule();
        $this->assertSame("must be a valid email address", $rule->getDescription());
    }

    public function testValidateInvalidEmail(): void
    {
        $rule = new EmailRule();
        $this->expectException(RuleValidationException::class);
        $this->expectExceptionMessage($rule->getDescription());
        $rule->validate("not an email");
    }

    /**
     * @dataProvider provideValidate
     */
    public function testValidate($value): void
    {
        $rule = new EmailRule();
        $this->assertSame($value, $rule->validate($value));
    }

    public function provideValidate(): array
    {
        return [
            ["hi@test.com"],
            ["hello@example.net"],
        ];
    }
}
